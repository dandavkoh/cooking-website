<?php

namespace Ddk\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Backend\Models\User as BackendUser;

/**
 * API Authentication Middleware
 *
 * Validates Bearer token authentication for API requests.
 * Tokens are stored in the backend_users table and can be
 * generated through the backend settings.
 */
class ApiAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('No API token provided.');
        }

        $user = $this->validateToken($token);

        if (!$user) {
            return $this->unauthorizedResponse('Invalid API token.');
        }

        // Check if user has API access permission
        if (!$user->hasAccess('ddk.api.access_api')) {
            return $this->forbiddenResponse('User does not have API access permission.');
        }

        // Attach validated user to request for use in controllers
        $request->attributes->set('api_user', $user);

        return $next($request);
    }

    /**
     * Extract Bearer token from Authorization header
     */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // Also check for X-API-Key header as fallback
        return $request->header('X-API-Key');
    }

    /**
     * Validate the API token and return the associated user
     */
    protected function validateToken(string $token): ?BackendUser
    {
        // Hash the token for comparison (tokens should be stored hashed)
        $hashedToken = hash('sha256', $token);

        // Find user with matching API token
        // This assumes you have an api_token column in backend_users
        // You may need to run a migration to add this column
        return BackendUser::where('api_token', $hashedToken)
            ->where('is_activated', true)
            ->first();
    }

    /**
     * Return unauthorized JSON response
     */
    protected function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
        ], 401);
    }

    /**
     * Return forbidden JSON response
     */
    protected function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'FORBIDDEN',
        ], 403);
    }
}
