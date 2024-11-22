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

        // Filter by sources
        if (!empty($sources)) {
            $query->whereIn('source', $sources);
        }

        // Filter by authors
        if (!empty($authors)) {
            $query->whereIn('author', $authors);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                // Apply orWhere between title and description
                $q->where('title', 'like', '%' . $request->keyword . '%')
                    ->orWhere('description', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('publishedAt', $request->date);
        }

        // Paginate the results
        $articles = $query->paginate(10);

        return response()->json($articles);
    }

    public function article($id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        return response()->json($article);
    }

    public function uniqueSources()
    {
        $sources = Article::query()
            ->distinct()
            ->pluck('source')
            ->filter(); // Filter to remove any null or empty values

        return response()->json([
            'sources' => $sources,
        ]);
    }

    public function uniqueAuthors()
    {
        $authors = Article::query()
            ->distinct()
            ->pluck('author')
            ->filter(); // Filter to remove any null or empty values

        return response()->json([
            'authors' => $authors,
        ]);
    }
}
