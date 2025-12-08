<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use App\Services\SmsGateway;

class SocialAuthController extends Controller
{
    protected $smsGateway;

    public function __construct(SmsGateway $smsGateway)
    {
        $this->smsGateway = $smsGateway;
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        // Check if Google returned an error
        if ($request->has('error')) {
            Log::warning('Google OAuth denied: ' . $request->get('error'));
            return redirect()->to(
                config('app.frontend_url', '') . '/login?error=' . urlencode('Google sign-in was cancelled.')
            );
        }

        // Ensure we have the authorization code
        if (!$request->has('code')) {
            Log::warning('Google OAuth callback called without code parameter');
            return redirect()->to(
                config('app.frontend_url', '') . '/login?error=' . urlencode('Invalid OAuth response. Please try again.')
            );
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Check if user already exists with this email
            $existingUser = User::where('email', $googleUser->getEmail())->first();
            
            if ($existingUser) {
                // If user exists but doesn't have google_id, link the account
                if (!$existingUser->google_id) {
                    $existingUser->update([
                        'google_id' => $googleUser->getId(),
                        'avatar_url' => $googleUser->getAvatar(),
                    ]);
                }
                
                // Check if user is active
                if ($existingUser->status === UserStatus::INACTIVE) {
                    return redirect()->to(
                        config('app.frontend_url', '') . '/login?error=' . urlencode('Your account has been deactivated.')
                    );
                }
                
                // Generate token
                $token = $existingUser->createToken('API Token')->plainTextToken;
                $existingUser->load('roles:id,name');
                
                // Check if profile is completed
                $needsOnboarding = !$existingUser->profile_completed || 
                                   !$existingUser->username || 
                                   !$existingUser->contact_number;
                
                return redirect()->to(
                    config('app.frontend_url', '') . '/oauth/callback?' . http_build_query([
                        'token' => $token,
                        'needs_onboarding' => $needsOnboarding ? '1' : '0',
                    ])
                );
            }
            
            // Create new user
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'password' => null, // No password for OAuth users
                'status' => UserStatus::PENDING_VERIFICATION, // Will become ACTIVE after onboarding
                'profile_completed' => false,
            ]);
            
            // Assign customer role
            $user->assignRole('customer');
            
            // Generate token
            $token = $user->createToken('API Token')->plainTextToken;
            
            Log::info("New Google OAuth user created: {$user->email}");
            
            return redirect()->to(
                config('app.frontend_url', '') . '/oauth/callback?' . http_build_query([
                    'token' => $token,
                    'needs_onboarding' => '1',
                ])
            );
            
        } catch (\Exception $e) {
            Log::error('Google OAuth error: ' . $e->getMessage());
            
            return redirect()->to(
                config('app.frontend_url', '') . '/login?error=' . urlencode('Google authentication failed. Please try again.')
            );
        }
    }

    /**
     * Complete profile for OAuth users (onboarding)
     */
    public function completeProfile(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'username' => 'required|string|min:3|max:255|unique:users,username,' . $user->id,
            'contact_number' => 'required|string|max:20|unique:users,contact_number,' . $user->id,
        ], [
            'name.required' => 'Display name is required',
            'name.min' => 'Display name must be at least 2 characters',
            'username.required' => 'Username is required',
            'username.unique' => 'This username is already taken',
            'username.min' => 'Username must be at least 3 characters',
            'contact_number.required' => 'Phone number is required for delivery updates',
            'contact_number.unique' => 'This phone number is already registered',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate OTP for phone verification
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'contact_number' => $request->contact_number,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // Send OTP
        $this->sendOtp($user->contact_number, $otp);

        return response()->json([
            'message' => 'Profile updated. Please verify your phone number.',
            'requires_otp' => true,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
        ]);
    }

    /**
     * Verify OTP and complete onboarding
     */
    public function verifyOnboardingOtp(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        if ($user->otp_code !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            return response()->json(['message' => 'OTP has expired'], 400);
        }

        // Complete profile verification
        $user->update([
            'status' => UserStatus::ACTIVE,
            'profile_completed' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified_at' => now(),
        ]);

        $user->load('roles', 'permissions');

        return response()->json([
            'message' => 'Profile completed successfully! Welcome to Seafood Delight.',
            'user' => $user,
        ]);
    }

    /**
     * Resend OTP for onboarding
     */
    public function resendOnboardingOtp(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->contact_number) {
            return response()->json(['message' => 'Phone number not set'], 400);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->sendOtp($user->contact_number, $otp);

        return response()->json(['message' => 'OTP resent successfully']);
    }

    /**
     * Send OTP via SMS
     */
    protected function sendOtp(string $phoneNumber, string $otp)
    {
        try {
            $message = "Your verification code is: {$otp}";
            $this->smsGateway->send($phoneNumber, $message);
            Log::info("OTP sent to {$phoneNumber}");
        } catch (\Exception $e) {
            Log::error("Failed to send OTP to {$phoneNumber}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if user needs onboarding
     */
    public function checkOnboardingStatus(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $needsOnboarding = !$user->profile_completed || 
                          !$user->username || 
                          !$user->contact_number;

        return response()->json([
            'needs_onboarding' => $needsOnboarding,
            'profile_completed' => $user->profile_completed,
            'has_username' => !empty($user->username),
            'has_contact_number' => !empty($user->contact_number),
            'is_google_user' => !empty($user->google_id),
        ]);
    }
}
