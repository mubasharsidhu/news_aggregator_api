<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as Password_Rule;
use Illuminate\Validation\ValidationException;

use App\Models\User;

use Illuminate\Support\Facades\DB;

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
            return $th->validator->errors();
        }

        $user = User::create([
            'name'     => $validatedData['name'],
            'email'    => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        return response()->json(['message' => 'User registered successfully']);
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email'    => 'required|string|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $th) {
            return $th->validator->errors();
        }

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('api-user-access-token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);
        } catch (ValidationException $th) {
            return $th->validator->errors();
        }

        $status = Password::sendResetLink($request->only('email'));








        $tt = DB::table('password_reset_tokens')->where('email', $request->input('email'))->first();
        exit($tt->token);






        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Password reset link sent'])
            : response()->json(['message' => 'Unable to send reset link'], 500);
    }

    public function resetPassword(Request $request, $token)
    {
        try {
            $validatedData = $request->validate([
                'password' => ['required', 'string', 'confirmed', Password_Rule::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            ]);
        } catch (ValidationException $th) {
            return $th->validator->errors();
        }

        // Attempt to reset the password using the token
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

        // Check if the password reset was successful
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successful']);
        } else {
            return response()->json(['error' => 'The provided token is invalid.'], 401);
        }
    }
}
