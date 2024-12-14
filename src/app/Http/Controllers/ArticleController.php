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
     *
     * @queryParam sources string Optional. Comma-separated list of sources to filter articles by. Example: "TechCrunch,BBC News".
     * @queryParam authors string Optional. Comma-separated list of authors to filter articles by. Example: "Jane Doe,John Smith".
     * @queryParam keyword string Optional. A keyword to search for in the article title or description. Example: "AI".
     * @queryParam date string Optional. Date to filter articles by their published date (format: YYYY-MM-DD). Example: "2024-12-01".
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Articles retrieved successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Exploring AI Innovations",
     *       "description": "An in-depth look at the latest advancements in AI technology.",
     *       "source": "TechCrunch",
     *       "author": "Jane Doe",
     *       "publishedAt": "2024-12-01"
     *     },
     *     {
     *       "id": 2,
     *       "title": "The Future of Quantum Computing",
     *       "description": "How quantum computing is shaping the tech industry.",
     *       "source": "BBC News",
     *       "author": "John Smith",
     *       "publishedAt": "2024-12-01"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "last_page": 5,
     *     "per_page": 10,
     *     "total": 50
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthorized."
     * }
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
     *
     * @urlParam id int required The ID of the article to retrieve. Example: 123
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Article retrieved.",
     *   "data": {
     *     "id": 123,
     *     "title": "Exploring AI Innovations",
     *     "description": "An in-depth look at the latest advancements in AI technology.",
     *     "source": "TechCrunch",
     *     "author": "Jane Doe",
     *     "publishedAt": "2024-12-01",
     *     "content": "The world of AI continues to evolve at a rapid pace...",
     *     "created_at": "2024-11-30T12:00:00Z",
     *     "updated_at": "2024-11-30T12:00:00Z"
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Article not found."
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "message": "An unexpected error occurred. Please try again later."
     * }
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
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Unique sources retrieved successfully.",
     *   "data": [
     *     "TechCrunch",
     *     "BBC News",
     *     "The Verge",
     *     "Wired"
     *   ]
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "message": "An unexpected error occurred. Please try again later."
     * }
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
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Unique authors retrieved successfully.",
     *   "data": [
     *     "John Doe",
     *     "Jane Smith",
     *     "Alice Johnson",
     *     "Bob Williams"
     *   ]
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "message": "An unexpected error occurred. Please try again later."
     * }
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
