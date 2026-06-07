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
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e4ecdb; font-size: 10px; color: #8a8273; }
    </style>
</head>
<body>
    <h1>{{ $article->title }}</h1>
    <div class="meta">
        {{ $article->user->name ?: $article->user->email }} · {{ $article->created_at->format('F j, Y') }}
    </div>

    {!! $article->bodyHtml() !!}

    <div class="footer">Written from a garden voice memo · {{ config('app.name') }}</div>
</body>
</html>
