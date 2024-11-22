<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserPreferencesController extends Controller
{
    public function preferences(Request $request)
    {
        $user = $request->user();  // Get the logged-in user
        return response()->json([
            'preferred_sources' => $user->preferred_sources,
            'preferred_authors' => $user->preferred_authors,
        ]);
    }

    // Update the current user's preferences
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'preferred_sources' => 'nullable|array',
                'preferred_authors' => 'nullable|array',
            ]);
        } catch (ValidationException $th) {
            return $th->validator->errors();
        }

        $user = $request->user();
        $user->preferred_sources = $validated['preferred_sources'] ?? [];
        $user->preferred_authors = $validated['preferred_authors'] ?? [];
        $user->save();

        return response()->json(['message' => 'Preferences updated successfully']);
    }
}
