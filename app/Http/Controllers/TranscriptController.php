<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Routing\Controller;

class TranscriptController extends Controller
{
    /**
     * Hand a signed-in gardener her own memo transcript as a Markdown file.
     * The raw transcript is plain prose — already valid Markdown — so we just
     * top it with the article title (if one exists) and a dated note.
     */
    public function download(Submission $submission)
    {
        abort_unless($submission->user_id === auth()->id(), 403);

        $transcript = $submission->transcript;

        abort_if($transcript === null, 404);

        $date = $submission->created_at->format('Y-m-d');
        $title = $submission->article?->title;

        $markdown = ($title ? '# '.$title."\n\n" : '')
            .'> Voice memo from '.$submission->created_at->format('F j, Y')."\n\n"
            .trim($transcript->raw_text)."\n";

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"memo-{$date}.md\"",
        ]);
    }
}
