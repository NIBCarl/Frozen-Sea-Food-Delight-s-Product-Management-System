<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get current user profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('roles', 'permissions');
        
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Update user profile (name, username, email)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
        ]);

        $user->load('roles', 'permissions');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => [
                    'current_password' => ['The current password is incorrect.']
                ]
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Upload/Update user avatar
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        try {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            
            $user->update(['avatar' => $path]);

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar_url' => Storage::url($path),
                'user' => $user->load('roles', 'permissions')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove user avatar
     */
    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        try {
            // Delete avatar file if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->update(['avatar' => null]);

            return response()->json([
                'message' => 'Avatar removed successfully',
                'user' => $user->load('roles', 'permissions')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user preferences/settings
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        
        // For now, return default preferences
        // In future, you could store these in a user_preferences table
        $preferences = [
            'theme' => $user->preferences['theme'] ?? 'light',
            'notifications' => [
                'email_notifications' => $user->preferences['email_notifications'] ?? true,
                'low_stock_alerts' => $user->preferences['low_stock_alerts'] ?? true,
                'system_updates' => $user->preferences['system_updates'] ?? true,
            ],
            'dashboard' => [
                'show_charts' => $user->preferences['show_charts'] ?? true,
                'items_per_page' => $user->preferences['items_per_page'] ?? 15,
            ]
        ];

        return response()->json([
            'preferences' => $preferences
        ]);
    }

    /**
     * Update user preferences/settings
     */
    public function updatePreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'nullable|in:light,dark',
            'notifications.email_notifications' => 'boolean',
            'notifications.low_stock_alerts' => 'boolean', 
            'notifications.system_updates' => 'boolean',
            'dashboard.show_charts' => 'boolean',
            'dashboard.items_per_page' => 'integer|min:5|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        // Store preferences in JSON column or separate table
        // For now, let's store in a JSON column called 'preferences'
        $preferences = $user->preferences ?? [];
        
        if ($request->has('theme')) {
            $preferences['theme'] = $request->theme;
        }
        
        if ($request->has('notifications')) {
            $preferences = array_merge($preferences, $request->notifications);
        }
        
        if ($request->has('dashboard')) {
            $preferences = array_merge($preferences, $request->dashboard);
        }

        $user->update(['preferences' => $preferences]);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $preferences
        ]);
    }
}
