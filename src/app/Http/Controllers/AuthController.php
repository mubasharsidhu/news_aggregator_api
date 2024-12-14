<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as Password_Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

use App\Models\User;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * Validates user input for name, email, and password.
     * Hashes the password and stores user data in the database.
     * Returns a success response with the new user's ID or an error response if validation fails.
     *
     * @param Request $request Incoming HTTP request containing user data.
     * @return \Illuminate\Http\JsonResponse JSON response with the result of the operation.
     *
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam email string required The email address of the user. Example: john@example.com
     * @bodyParam password string required The password for the user. Must be at least 8 characters and include letters, numbers, and symbols. Example: P@ssw0rd!
     * @bodyParam password_confirmation string required Must match the password. Example: P@ssw0rd!
     *
     * @response 201 {
     *   "success": true,
     *   "message": "User registered successfully.",
     *   "data": {
     *     "user_id": 1
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed.",
     *   "errors": {
     *     "email": ["The email has already been taken."],
     *     "password": ["The password does not meet the required rules."]
     *   }
     * }
     *
     * @unauthenticated
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                // Name of the User
                'name'     => 'required|string|max:255',
                // Email of the User
                'email'    => 'required|string|email|max:255|unique:users',
                // Password of the User
                'password' => ['required','string','confirmed',Password_Rule::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            ]);
        } catch (ValidationException $th) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $th->validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $validatedData['name'],
            'email'    => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data'    => ['user_id' => $user->id]
        ], 201);
    }

    /**
     * Authenticate a user and issue an API token.
     *
     * Validates the provided email and password credentials.
     * If credentials are valid, generates a personal access token for the user.
     * Returns the user's details and token upon successful login or an error response for invalid credentials.
     *
     * @param Request $request Incoming HTTP request containing login credentials.
     * @return \Illuminate\Http\JsonResponse JSON response with the login result.
     *
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required The user's password. Example: P@ssw0rd!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john.doe@example.com"
     *     },
     *     "token": "token_string_here"
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid credentials",
     *   "errors": "Invalid credentials"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed.",
     *   "errors": {
     *     "email": ["The email field is required."],
     *     "password": ["The password field is required."]
     *   }
     * }
     *
     * @unauthenticated
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email'    => 'required|string|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $th) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $th->validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'errors'  => 'Invalid credentials',
            ], 401);
        }

        $user  = $request->user();
        $token = $user->createToken('api-user-access-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Log out the authenticated user.
     *
     * Deletes the user's current API access token.
     * Returns a success response upon successful logout.
     *
     * @param Request $request Incoming HTTP request containing the authenticated user.
     * @return \Illuminate\Http\JsonResponse JSON response indicating logout success.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Logged out successfully."
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated user.",
     *   "errors": "Unauthenticated."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }

    /**
     * Send a password reset link to the user's email.
     *
     * Validates the provided email address.
     * Sends a password reset email if the email is valid and registered.
     * Returns a success response if the link is sent or an error response otherwise.
     *
     * @param Request $request Incoming HTTP request containing the user's email.
     * @return \Illuminate\Http\JsonResponse JSON response indicating the result of the operation.
     *
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password reset link sent."
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed.",
     *   "errors": {
     *     "email": ["The email field is required.", "The email must be a valid email address."]
     *   }
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "message": "Unable to send reset link."
     * }
     *
     * @unauthenticated
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate(['email' => 'required|email']);
        } catch (ValidationException $th) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $th->validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send reset link.',
        ], 500);
    }

    /**
     * Reset the user's password using a valid reset token.
     *
     * Validates the new password and the reset token.
     * Updates the user's password in the database upon successful validation.
     * Returns a success response or an error response for invalid token or validation failure.
     *
     * @param Request $request Incoming HTTP request containing the reset token and new password.
     * @param string $token Password reset token.
     *
     * @return \Illuminate\Http\JsonResponse JSON response indicating the result of the operation.
     *
     * @bodyParam password string required The new password. Must be at least 8 characters, with letters, numbers, symbols, and mixed case. Example: Password@123
     * @bodyParam password_confirmation string required Confirmation of the new password. Example: Password@123
     * @queryParam token string required The reset token sent to the user's email. Example: abc123resetToken
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password reset successful."
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "The provided token is invalid."
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed.",
     *   "errors": {
     *     "password": [
     *       "The password must be at least 8 characters.",
     *       "The password must contain letters, numbers, and symbols."
     *     ]
     *   }
     * }
     *
     * @unauthenticated
     */
    public function resetPassword(Request $request, $token): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'password' => ['required', 'string', 'confirmed', Password_Rule::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            ]);
        } catch (ValidationException $th) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $th->validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            [
                'token'    => $token,
                'email'    => $request->email,
                'password' => $validatedData['password'],
            ],
            function ($user) use ($validatedData) {
                $user->forceFill([
                    'password' => Hash::make($validatedData['password']),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successful.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'The provided token is invalid.',
        ], 401);
    }
}
