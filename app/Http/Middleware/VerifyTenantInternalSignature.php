<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTenantInternalSignature
{
    /**
     * Handle an incoming internal request from a tenant node.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'unauthorized',
                'message' => 'Missing Bearer Token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Validate the bearer token against the Project (Tenant)
        // Since public_api_key and remote_admin_api_key may be encrypted in the database,
        // search projects in memory.
        $project = Project::all()->first(function ($p) use ($bearerToken) {
            return $p->public_api_key === $bearerToken || $p->remote_admin_api_key === $bearerToken;
        });

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'forbidden',
                'message' => 'Invalid Authority or Tenant Token',
            ], Response::HTTP_FORBIDDEN);
        }

        // Verify optional HMAC signature if X-Tenant-Signature is provided
        $signature = $request->header('X-Tenant-Signature');
        $timestamp = $request->header('X-Tenant-Timestamp');

        if ($signature && $timestamp) {
            // Check replay attacks (within 5 minutes)
            if (abs(time() - (int) $timestamp) > 300) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'signature_expired',
                    'message' => 'Request signature timestamp is expired',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $secret = $project->remote_admin_api_key ?: $project->public_api_key;
            $payload = $timestamp . '.' . $request->getContent();
            $expectedSignature = hash_hmac('sha256', $payload, $secret);

            if (! hash_equals($expectedSignature, $signature)) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'invalid_signature',
                    'message' => 'HMAC signature verification failed',
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        // Attach resolved project to the request
        $request->attributes->set('project', $project);

        return $next($request);
    }
}
