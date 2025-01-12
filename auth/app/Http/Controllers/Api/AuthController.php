<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'usertype' => 'required|in:owner,consumer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Use $validator->validated() to get the validated input
        $validated = $validator->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'usertype' => $validated['usertype'],
        ]);

        return response()->json(['message' => 'User registered', 'user' => $user], 201);
    }

    public function login(Request $request)
{
    // Manually validate using Validator
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ], [
        'email.required' => 'The email field is required.',
        'email.email' => 'Please provide a valid email address.',
        'password.required' => 'The password field is required.',
        'password.min' => 'The password must be at least 8 characters long.',
    ]);

    // Check if validation fails
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Get validated data
    $validated = $validator->validated();

    // Find user by email
    $user = User::where('email', $validated['email'])->first();

    // Verify user and password
    if (!$user || !Hash::check($validated['password'], $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials. Please check your email and password.',
        ], 401);
    }

    // Generate token for the user
    $token = $user->createToken('auth_token')->plainTextToken;

    // Return success response with user details and token
    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'usertype' => $user->usertype,
        ],
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
}


    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'No authenticated user found',
        ], 401);
    }


    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
