<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'department' => $user->department,
                'role' => $user->role,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'department' => 'sometimes|required|string|max:255',
        ]);

        $oldValues = $user->only(['name', 'email', 'department']);
        
        $user->update($request->only(['name', 'email', 'department']));

        $newValues = $user->only(['name', 'email', 'department']);

        // Log the profile update
        AuditLog::createLog(
            $user->id,
            'profile_updated',
            'App\Models\User',
            $user->id,
            $oldValues,
            $newValues,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'department' => $user->department,
                'role' => $user->role,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => ['current_password' => ['The current password is incorrect.']],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Log the password change
        AuditLog::createLog(
            $user->id,
            'password_changed',
            'App\Models\User',
            $user->id,
            null,
            null,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }
}