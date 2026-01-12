<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('mail.upload_link.subject', ['reference' => $orderReference]) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: #4f46e5;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 32px 24px;
        }
        .content p {
            margin: 0 0 16px;
        }
        .product-info {
            background: #f9fafb;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
        }
        .product-info h3 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #374151;
        }
        .product-info p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 24px 0;
        }
        .cta-button:hover {
            background: #4338ca;
        }
        .expiry-notice {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 24px 0;
            font-size: 14px;
            color: #92400e;
        }
        .footer {
            padding: 24px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: #4f46e5;
            text-decoration: none;
        }
        .link-fallback {
            margin-top: 24px;
            padding: 16px;
            background: #f3f4f6;
            border-radius: 6px;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>{{ __('mail.upload_link.title') }}</h1>
            </div>

            <div class="content">
                <p>{{ __('mail.upload_link.greeting') }}</p>

                <p>{{ __('mail.upload_link.intro', ['reference' => $orderReference]) }}</p>

                <div class="product-info">
                    <h3>{{ $productName }}</h3>
                    @if($orderLine->quantity > 1)
                        <p>{{ __('mail.upload_link.quantity', ['qty' => $orderLine->quantity]) }}</p>
                    @endif
                </div>

                <p>{{ __('mail.upload_link.instructions') }}</p>

                <div style="text-align: center;">
                    <a href="{{ $uploadUrl }}" class="cta-button">
                        {{ __('mail.upload_link.cta') }}
                    </a>
                </div>

                <div class="expiry-notice">
                    <strong>{{ __('mail.upload_link.expiry_warning') }}</strong>
                    {{ __('mail.upload_link.expiry_date', ['date' => $expiresAt]) }}
                </div>

                <p>{{ __('mail.upload_link.help_text') }}</p>

                <div class="link-fallback">
                    <strong>{{ __('mail.upload_link.link_fallback') }}</strong><br>
                    <a href="{{ $uploadUrl }}">{{ $uploadUrl }}</a>
                </div>
            </div>

            <div class="footer">
                <p>{{ __('mail.upload_link.footer_text') }}</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
