<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Handle employee logout request.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Log the logout action
        AuditLog::createLog(
            $user->id,
            'user_logout',
            null,
            null,
            null,
            null,
            $request->ip(),
            $request->userAgent()
        );

        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Revoke all tokens for the user.
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();

        // Log the logout all action
        AuditLog::createLog(
            $user->id,
            'user_logout_all',
            null,
            null,
            null,
            null,
            $request->ip(),
            $request->userAgent()
        );

        // Revoke all tokens
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'All sessions terminated successfully',
        ]);
    }
}