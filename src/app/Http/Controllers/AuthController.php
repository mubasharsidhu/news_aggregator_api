<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as Password_Rule;
use Illuminate\Validation\ValidationException;

use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        try {
            $validatedData = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
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

    public function login(Request $request)
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }

    public function forgotPassword(Request $request)
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

    public function resetPassword(Request $request, $token)
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
