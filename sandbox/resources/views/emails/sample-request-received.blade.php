<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nieuwe sample aanvraag #{{ $sampleRequest->id }}</title>
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
            background: #dc2626;
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
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 4px 12px 4px 0;
            color: #6b7280;
            font-size: 14px;
            width: 140px;
        }
        .info-value {
            display: table-cell;
            padding: 4px 0;
            font-size: 14px;
            color: #111827;
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
        .footer {
            padding: 24px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Nieuwe Sample Aanvraag</h1>
            </div>

            <div class="content">
                <p>Er is een nieuwe sample aanvraag binnengekomen via de website.</p>

                <div class="section">
                    <h3>Klantgegevens</h3>
                    <div class="info-grid">
                        @if($sampleRequest->company_name)
                        <div class="info-row">
                            <span class="info-label">Bedrijfsnaam:</span>
                            <span class="info-value">{{ $sampleRequest->company_name }}</span>
                        </div>
                        @endif
                        @if($sampleRequest->contact_person)
                        <div class="info-row">
                            <span class="info-label">Contactpersoon:</span>
                            <span class="info-value">{{ $sampleRequest->contact_person }}</span>
                        </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">E-mail:</span>
                            <span class="info-value"><a href="mailto:{{ $sampleRequest->email }}">{{ $sampleRequest->email }}</a></span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>Bezorgadres</h3>
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="info-label">Straat:</span>
                            <span class="info-value">{{ $sampleRequest->street }} {{ $sampleRequest->house_number }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Postcode/Plaats:</span>
                            <span class="info-value">{{ $sampleRequest->postal_code }} {{ $sampleRequest->city }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Land:</span>
                            <span class="info-value">{{ $sampleRequest->country }}</span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>Aangevraagde samples ({{ count($sampleRequest->selected_samples) }})</h3>
                    <div class="samples-list">
                        <ul>
                            @foreach($sampleRequest->selected_samples as $sampleKey)
                                <li>{{ $sampleLabels[$sampleKey] ?? $sampleKey }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="section">
                    <h3>Details</h3>
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="info-label">Aanvraag ID:</span>
                            <span class="info-value">#{{ $sampleRequest->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Aangevraagd op:</span>
                            <span class="info-value">{{ $sampleRequest->created_at->format('d-m-Y H:i') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value">{{ ucfirst($sampleRequest->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer">
                <p>Dit is een automatisch gegenereerde e-mail van de Drukhoek webshop.</p>
            </div>
        </div>
    </div>
</body>
</html>
