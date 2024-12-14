<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class UserPreferencesController extends Controller
{

    /**
     * Get user detail
     *
     * @param Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse JSON response containing the user's data.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john.doe@example.com",
     *     "email_verified_at": "2024-01-01T12:00:00.000000Z",
     *     "created_at": "2023-12-01T08:30:00.000000Z",
     *     "updated_at": "2024-01-10T14:45:00.000000Z"
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated."
     * }
     *
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data'    => $user,
        ]);
    }

    /**
     * Retrieve the authenticated user's preferences.
     *
     * @param Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse JSON response containing the user's preferences.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User preferences retrieved successfully.",
     *   "data": {
     *     "preferred_sources": ["TechCrunch", "BBC News"],
     *     "preferred_authors": ["Jane Doe", "John Smith"]
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated."
     * }
     *
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
     *
     * @bodyParam preferred_sources array Optional. A list of preferred sources. Example: ["TechCrunch", "BBC News"]
     * @bodyParam preferred_authors array Optional. A list of preferred authors. Example: ["Jane Doe", "John Smith"]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Preferences updated successfully.",
     *   "data": {
     *     "preferred_sources": ["TechCrunch", "BBC News"],
     *     "preferred_authors": ["Jane Doe", "John Smith"]
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed.",
     *   "errors": {
     *     "preferred_sources": ["The preferred_sources field must be an array."],
     *     "preferred_authors": ["The preferred_authors field must be an array."]
     *   }
     * }
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
