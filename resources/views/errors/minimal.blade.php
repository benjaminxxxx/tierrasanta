<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --background: #0f0f0f;
            --surface: #1a1a1a;
            --foreground: #ffffff;
            --text-muted: #888888;
            --border: #333333;
            --accent-primary: #00c853;
            --accent-warning: #fdb022;
            --error: #ff3b30;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background-color: var(--background);
        }

        body {
            background-color: var(--background);
            color: var(--foreground);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .error-container {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 3rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .error-code {
            font-size: clamp(3rem, 12vw, 9rem);
            font-weight: 900;
            color: var(--foreground);
            line-height: 1;
            margin-bottom: 1.5rem;
            letter-spacing: -0.05em;
        }

        .error-title {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .error-message {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent-primary);
            color: #000000;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: transparent;
            color: var(--foreground);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--text-muted);
        }

        .error-meta {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .dot-separator {
            width: 3px;
            height: 3px;
            background: var(--text-muted);
            border-radius: 50%;
            display: inline-block;
            margin: 0 0.5rem;
        }

        @media (max-width: 640px) {
            .error-container {
                padding: 2rem;
                margin: 1rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .error-code {
                margin-bottom: 1rem;
            }
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <div class="content">
        <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">
            <div class="error-container">
                <!-- Error Code -->
                <div class="error-code">
                    @yield('code')
                </div>

                <!-- Error Title -->
                <h1 class="error-title">
                    @yield('title')
                </h1>

                <!-- Error Message -->
                <p class="error-message">
                    @yield('message')
                </p>

                <!-- Action Buttons -->
                <div class="button-group">
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <span>← Volver al inicio</span>
                    </a>
                    <button class="btn btn-secondary" onclick="history.back()">
                        <span>Atrás</span>
                    </button>
                </div>

                <!-- Additional Info -->
                <div class="error-meta">
                    <span>Código de error: @yield('code')</span>
                    <span class="dot-separator"></span>
                    <span>{{ now()->format('H:i:s') }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
