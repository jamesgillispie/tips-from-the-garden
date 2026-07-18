<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Photo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function show(string $token)
    {
        $article = Article::where('download_token', $token)->firstOrFail();

        return view('articles.show', ['article' => $article]);
    }

    /**
     * Stream a photo from the private photos disk. The entry token is the
     * whole trust model — a photo is only reachable through the journal
     * entry it belongs to, exactly like the entry itself.
     */
    public function photo(string $token, Photo $photo, ?string $size = null)
    {
        $article = Article::where('download_token', $token)->firstOrFail();

        abort_unless($photo->submission_id === $article->submission_id, 404);

        $disk = Storage::disk(config('pipeline.photos.disk'));
        $path = $size === 'thumb' ? $photo->thumb_path : $photo->path;

        abort_unless($disk->exists($path), 404);

        // Re-encoded photos never change, so let browsers and email clients
        // keep them — this is what makes app-proxying viable (ADR 0002).
        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
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

        // dompdf can't fetch the token-gated photo URLs, so the photos go in
        // as inline data URIs read straight off the private disk.
        $photoData = $article->photos()
            ->map(function ($photo) {
                try {
                    $bytes = Storage::disk(config('pipeline.photos.disk'))->get($photo->path);
                } catch (\Throwable) {
                    return null;
                }

                return $bytes === null ? null : 'data:image/jpeg;base64,'.base64_encode($bytes);
            })
            ->filter()
            ->values();

        return Pdf::loadView('articles.pdf', ['article' => $article, 'photoData' => $photoData])
            ->download("{$filename}.pdf");
    }
}
