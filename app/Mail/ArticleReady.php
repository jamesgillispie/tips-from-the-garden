<?php

namespace App\Mail;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArticleReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Article $article,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your article is ready: '.$this->article->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.article-ready',
            with: [
                'articleUrl' => $this->article->publicUrl(),
            ],
        );
    }
}
