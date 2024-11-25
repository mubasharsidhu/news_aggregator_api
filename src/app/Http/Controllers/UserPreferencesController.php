<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class UserPreferencesController extends Controller
{
    /**
     * Retrieve the authenticated user's preferences.
     *
     * @param Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse JSON response containing the user's preferences.
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'message' => 'User preferences retrieved successfully.',
            'data'    => [
                'preferred_sources' => $user->preferred_sources,
                'preferred_authors' => $user->preferred_authors,
            ],
        ]);
    }

    /**
     * Update the authenticated user's preferences.
     *
     * @param Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse JSON response indicating the update status.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'preferred_sources' => 'nullable|array',
                'preferred_authors' => 'nullable|array',
            ]);
        } catch (ValidationException $th) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $th->validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->preferred_sources = $validated['preferred_sources'] ?? [];
        $user->preferred_authors = $validated['preferred_authors'] ?? [];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.',
            'data'    => [
                'preferred_sources' => $user->preferred_sources,
                'preferred_authors' => $user->preferred_authors,
            ],
        ]);
    }
}
