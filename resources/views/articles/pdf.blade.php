<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $article->title }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; color: #2d2a24; margin: 48px; line-height: 1.6; }
        h1 { font-size: 26px; color: #2d4226; margin-bottom: 4px; }
        h2 { font-size: 18px; color: #3a5530; margin-top: 24px; }
        .meta { font-size: 11px; color: #8a8273; margin-bottom: 28px; border-bottom: 1px solid #e4ecdb; padding-bottom: 12px; }
        .ai-label { display: inline-block; margin-bottom: 12px; padding: 5px 9px; border: 1px solid #ddddda; border-radius: 12px; font-family: Arial, sans-serif; font-size: 9px; color: #5f5f59; }
        .ai-label img { width: 14px; height: 14px; margin-right: 5px; vertical-align: middle; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e4ecdb; font-size: 10px; color: #8a8273; }
    </style>
</head>
<body>
    @if ($article->is_ai_assisted)
        <!-- AI-assisted content; model: {{ $article->ai_model ?: 'unspecified' }} -->
        <div class="ai-label">
            <img src="{{ public_path('icons/eu-ai-labels/ai-label-black.png') }}" alt="">
            AI-assisted article
        </div>
    @endif
    <h1>{{ $article->title }}</h1>
    <div class="meta">
        {{ $article->user->name ?: $article->user->email }} · {{ $article->created_at->format('F j, Y') }}
    </div>

    {!! $article->bodyHtml() !!}

    @if ($photoData->isNotEmpty())
        <div class="photos">
            @foreach ($photoData as $src)
                <img src="{{ $src }}" style="width: 100%; margin: 10px 0;" alt="A photo from the garden">
            @endforeach
        </div>
    @endif

    <div class="footer">
        Written from a garden voice memo · {{ config('app.name') }}
        @if ($article->is_ai_assisted)
            · AI-assisted
        @endif
    </div>
</body>
</html>
