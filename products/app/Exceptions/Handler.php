<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Always return JSON response for API routes
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or expired JWT token',
                'message' => 'Authentication required. Please provide a valid bearer token.'
            ], 401);
        }

        // Fallback for web routes (if any)
        return redirect()->guest(route('login'));
    }

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}