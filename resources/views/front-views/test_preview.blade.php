{{-- Frontend display of the saved editor content --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $doc->title ?? 'Document' }}</title>
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            max-width: 820px; margin: 48px auto; padding: 0 24px;
            line-height: 1.7; font-size: 16px; color: #1f2937;
        }
        h1.doc-title { font-size: 2rem; margin: 0 0 .6em; color: #0f172a; }
        .content h1 { font-size: 1.8rem; margin: .6em 0 .4em; }
        .content h2 { font-size: 1.45rem; margin: .6em 0 .4em; }
        .content h3 { font-size: 1.2rem; margin: .6em 0 .4em; }
        .content p { margin: 0 0 .8em; }
        .content ul, .content ol { padding-left: 1.6em; margin: 0 0 .8em; }
        .content blockquote {
            border-left: 3px solid #4f46e5; margin: .8em 0; padding: 6px 16px;
            background: #f5f3ff; color: #4338ca; border-radius: 0 6px 6px 0;
        }
        .content pre {
            background: #0f172a; color: #a5f3fc; padding: 14px 16px; border-radius: 8px;
            overflow: auto; font-family: ui-monospace, Consolas, monospace; font-size: 14px;
        }
        .content a { color: #4f46e5; }
        .content img { max-width: 100%; height: auto; border-radius: 6px; }
        .content::after { content: ''; display: block; clear: both; }
        .content hr { border: none; border-top: 1px solid #e2e8f0; margin: 1.2em 0; }
        .content table { border-collapse: collapse; width: 100%; }
        .content td, .content th { border: 1px solid #ddd; padding: 8px; }
    </style>
</head>
<body>
    @if ($doc && $doc->title)
        <h1 class="doc-title">{{ $doc->title }}</h1>
    @endif

    <article class="content">
        @if ($doc && $doc->content)
            {!! $doc->content !!}
        @else
            <p style="color:#94a3b8;">No content saved yet. Open the editor and click Save.</p>
        @endif
    </article>
</body>
</html>
