<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>Privacy Policy &mdash; {{ $apk->name }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                         Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            max-width: 720px;
            margin: 0 auto;
            padding: 2rem 1.25rem 4rem;
            color: #1f2937;
            background: #ffffff;
        }
        @media (prefers-color-scheme: dark) {
            body { color: #e5e7eb; background: #0b0f17; }
            a    { color: #60a5fa; }
            code { background: #1f2937; }
        }
        header { border-bottom: 1px solid rgba(127,127,127,.25); padding-bottom: 1rem; margin-bottom: 1.5rem; }
        h1 { margin: 0 0 .25rem; font-size: 1.5rem; }
        h2, h3 { margin-top: 1.75rem; }
        code { padding: .1rem .35rem; border-radius: 4px; background: #f3f4f6; font-size: .9em; }
        .muted { color: #6b7280; font-size: .9rem; }
        footer { margin-top: 3rem; font-size: .85rem; color: #6b7280; border-top: 1px solid rgba(127,127,127,.25); padding-top: 1rem; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $apk->name }}</h1>
        <div class="muted">
            <code>{{ $apk->packagename }}</code>
            @if ($apk->privacy_policy_generated_at)
                &middot; Last updated {{ $apk->privacy_policy_generated_at->toFormattedDateString() }}
            @endif
        </div>
    </header>

    <article>
        {!! $apk->privacy_policy !!}
    </article>

    <footer>
        Privacy policy for {{ $apk->name }}.
    </footer>
</body>
</html>
