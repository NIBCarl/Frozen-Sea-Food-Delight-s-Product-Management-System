<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Enums\UserStatus;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        
        if ($user->status === UserStatus::PENDING_VERIFICATION) {
            return response()->json([
                'message' => 'Please verify your phone number',
                'requires_otp' => true,
                'email' => $user->email
            ], 403);
        }

        if (!$user->isActive()) {
            Auth::logout();
            return response()->json([
                'message' => 'Account is not active'
            ], 401);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        // Load only essential user data for faster response
        $user->load('roles:id,name');

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'roles' => $user->roles->pluck('name'),
                'status' => $user->status,
            ],
            'token' => $token
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'contact_number' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->password),
            'status' => UserStatus::PENDING_VERIFICATION,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // Assign default customer role for regular registrations
        $user->assignRole('customer');
        
        // Send OTP
        $this->sendOtp($user->contact_number, $otp);

        return response()->json([
            'message' => 'Registration successful. Please check your phone for the OTP.',
            'user_id' => $user->id,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'requires_otp' => true
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        // Rate Limiting: 5 attempts per 10 minutes per email
        $key = 'verify-otp:' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'Too many verification attempts. Please try again in ' . $seconds . ' seconds.'
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->status === UserStatus::ACTIVE) {
            return response()->json(['message' => 'User is already active'], 200);
        }

        if ($user->otp_code !== $request->otp) {
            RateLimiter::hit($key, 600); // Increment failed attempts
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            return response()->json(['message' => 'OTP has expired'], 400);
        }

        // Clear Rate Limiter on success
        RateLimiter::clear($key);

        // Verify User
        $user->update([
            'status' => UserStatus::ACTIVE,
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified_at' => now(), // Assuming OTP implies verification
        ]);

        // Create Token
        $token = $user->createToken('API Token')->plainTextToken;
        $user->load('roles', 'permissions');

        return response()->json([
            'message' => 'Account verified successfully',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Rate Limiting: 3 attempts per hour per email
        $key = 'resend-otp:' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'Too many OTP requests. Please try again in ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->status === UserStatus::ACTIVE) {
            return response()->json(['message' => 'User is already verified'], 400);
        }

        RateLimiter::hit($key, 3600); // Record attempt, expires in 1 hour

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->sendOtp($user->contact_number, $otp);

        return response()->json(['message' => 'OTP resent successfully']);
    }

    private function sendOtp($phoneNumber, $otp)
    {
        // TODO: Integrate with actual SMS gateway (e.g., Twilio, Vonage)
        // For now, we log the OTP for testing purposes
        Log::info("OTP for {$phoneNumber}: {$otp}");
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load('roles', 'permissions');
        
        return response()->json([
            'user' => $user
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }
}
