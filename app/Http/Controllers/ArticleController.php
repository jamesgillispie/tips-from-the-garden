<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function show(string $token)
    {
        $article = Article::where('download_token', $token)->firstOrFail();

        return view('articles.show', ['article' => $article]);
    }

    public function download(string $token, string $format)
    {
        $article = Article::where('download_token', $token)->firstOrFail();

        $filename = Str::slug($article->title) ?: 'article';

        if ($format === 'md') {
            return response('# '.$article->title."\n\n".$article->body_md, 200, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.md\"",
            ]);
        }

        return Pdf::loadView('articles.pdf', ['article' => $article])
            ->download("{$filename}.pdf");
    }
}
