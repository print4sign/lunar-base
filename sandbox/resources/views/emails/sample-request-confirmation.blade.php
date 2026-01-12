<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bevestiging sample aanvraag - Drukhoek</title>
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
        .success-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .section {
            margin-bottom: 24px;
        }
        .section h3 {
            margin: 0 0 12px;
            font-size: 16px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .address-box {
            background: #f9fafb;
            border-radius: 6px;
            padding: 16px;
            font-size: 14px;
        }
        .samples-list {
            background: #f9fafb;
            border-radius: 6px;
            padding: 16px;
        }
        .samples-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .samples-list li {
            padding: 4px 0;
            font-size: 14px;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin: 24px 0;
            font-size: 14px;
            color: #1e40af;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Drukhoek</h1>
            </div>

            <div class="content">
                <div style="text-align: center;">
                    <span class="success-badge">Aanvraag ontvangen</span>
                </div>

                <p>Beste {{ $sampleRequest->contact_person ?: 'klant' }},</p>

                <p>Bedankt voor je sample aanvraag! We hebben je verzoek ontvangen en gaan deze zo snel mogelijk voor je klaarmaken.</p>

                <div class="section">
                    <h3>Bezorgadres</h3>
                    <div class="address-box">
                        @if($sampleRequest->company_name)
                            <strong>{{ $sampleRequest->company_name }}</strong><br>
                        @endif
                        @if($sampleRequest->contact_person)
                            {{ $sampleRequest->contact_person }}<br>
                        @endif
                        {{ $sampleRequest->street }} {{ $sampleRequest->house_number }}<br>
                        {{ $sampleRequest->postal_code }} {{ $sampleRequest->city }}<br>
                        {{ $sampleRequest->country }}
                    </div>
                </div>

                <div class="section">
                    <h3>Jouw samples ({{ count($sampleRequest->selected_samples) }})</h3>
                    <div class="samples-list">
                        <ul>
                            @foreach($sampleRequest->selected_samples as $sampleKey)
                                <li>{{ $sampleLabels[$sampleKey] ?? $sampleKey }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Verwachte levertijd:</strong> Je samples worden binnen 3-5 werkdagen gratis bij je bezorgd.
                </div>

                <p>Heb je vragen over je aanvraag? Neem gerust contact met ons op via <a href="mailto:info@drukhoek.nl">info@drukhoek.nl</a> of bel ons.</p>

                <p>Met vriendelijke groet,<br>
                <strong>Team Drukhoek</strong></p>
            </div>

            <div class="footer">
                <p>Drukhoek - Opvallen begint hier</p>
                <p><a href="https://drukhoek.nl">www.drukhoek.nl</a></p>
                <p>&copy; {{ date('Y') }} Drukhoek. Alle rechten voorbehouden.</p>
            </div>
        </div>
    </div>
</body>
</html>
