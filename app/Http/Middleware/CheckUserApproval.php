<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserApproval
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Restricted roles
            $restrictedRoles = ['salon', 'home_barbar'];

            if (in_array($user->role, $restrictedRoles)) {
                if ($user->status !== 'approved') {
                    $status = $user->status;

                    $message = 'Your account is currently ' . ($status ?? 'pending') . '. Please contact admin for approval.';
                    if ($status == 'cancel' || $status == 'blocked') {
                        $message = 'Your account has been blocked. Please contact support.';
                    }

                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'success' => false,
                            'message' => $message,
                            'status' => $status
                        ], 403);
                    }

                    auth()->logout();
                    return redirect()->route('login')->with('error', $message);
                }
            }
        }

        return $next($request);
    }
}
