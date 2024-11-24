<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Article;

class ArticleController extends Controller
{
    public function articles(Request $request)
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

    public function article($id)
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

    public function uniqueSources()
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

    public function uniqueAuthors()
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
