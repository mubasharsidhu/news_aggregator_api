<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Article;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    /**
     * Retrieve a list of articles based on filters such as sources, authors, keywords, and date.
     *
     * @param Request $request The HTTP request instance.
     * @return \Illuminate\Http\JsonResponse JSON response containing the filtered articles and pagination metadata.
     */
    public function articles(Request $request): JsonResponse
    {
        $currentRoute = Route::currentRouteName();
        $sources = $request->filled('sources') ? explode(',', $request->sources) : [];
        $authors = $request->filled('authors') ? explode(',', $request->authors) : [];
        if ($currentRoute==='personalized.feed') {
            $user    = $request->user();
            $sources = $user->preferred_sources ?? [];
            $authors = $user->preferred_authors ?? [];
        }

        $query = Article::query();

        if (!empty($sources)) {
            $query->whereIn('source', $sources);
        }

        if (!empty($authors)) {
            $query->whereIn('author', $authors);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->keyword . '%')
                    ->orWhere('description', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('publishedAt', $request->date);
        }

        $articles = $query->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Articles retrieved successfully.',
            'data'    => $articles->items(),
            'meta'    => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'per_page'     => $articles->perPage(),
                'total'        => $articles->total(),
            ],
        ]);
    }

    /**
     * Retrieve details of a specific article by its ID.
     *
     * @param int $id The ID of the article.
     * @return \Illuminate\Http\JsonResponse JSON response containing the article details or an error message.
     */
    public function article(int $id): JsonResponse
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article retrieved.',
            'data'    => $article,
        ]);
    }

    /**
     * Retrieve a list of unique article sources.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the unique sources.
     */
    public function uniqueSources(): JsonResponse
    {
        $sources = Article::query()
            ->distinct()
            ->pluck('source')
            ->filter();

        return response()->json([
            'success' => true,
            'message' => 'Unique sources retrieved successfully.',
            'data'    => $sources,
        ]);
    }

    /**
     * Retrieve a list of unique article authors.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the unique authors.
     */
    public function uniqueAuthors(): JsonResponse
    {
        $authors = Article::query()
            ->distinct()
            ->pluck('author')
            ->filter();

        return response()->json([
            'success' => true,
            'message' => 'Unique authors retrieved successfully.',
            'data'    => $authors,
        ]);
    }
}
