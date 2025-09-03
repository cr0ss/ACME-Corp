<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Donation Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .receipt-container {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 25px;
            background-color: #f9fafb;
        }
        
        .receipt-title {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .receipt-title h2 {
            color: #059669;
            margin: 0;
            font-size: 24px;
        }
        
        .receipt-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .receipt-details .left, .receipt-details .right {
            flex: 1;
        }
        
        .detail-row {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .detail-label {
            font-weight: bold;
            color: #374151;
            min-width: 120px;
            margin-right: 10px;
        }
        
        .detail-value {
            color: #111827;
        }
        
        .amount-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #ecfdf5;
            border: 1px solid #10b981;
            border-radius: 8px;
        }
        
        .amount-label {
            font-size: 16px;
            color: #059669;
            margin-bottom: 10px;
        }
        
        .amount-value {
            font-size: 32px;
            font-weight: bold;
            color: #059669;
        }
        
        .campaign-section {
            background-color: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .campaign-title {
            font-size: 18px;
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 10px;
        }
        
        .organization-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #d1d5db;
            text-align: center;
        }
        
        .organization-name {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        
        .organization-details {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        
        .receipt-number {
            background-color: #f3f4f6;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ACME Corporation</h1>
        <p>Corporate Social Responsibility Platform</p>
        <p>Donation Receipt</p>
    </div>
    
    <div class="receipt-container">
        <div class="receipt-title">
            <h2>Thank You for Your Donation!</h2>
        </div>
        
        <div class="receipt-details">
            <div class="left">
                <div class="detail-row">
                    <span class="detail-label">Receipt #:</span>
                    <span class="detail-value receipt-number">{{ $receipt['receipt_number'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $receipt['date'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $receipt['payment_method'])) }}</span>
                </div>
                @if(isset($receipt['transaction_id']))
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value">{{ $receipt['transaction_id'] }}</span>
                </div>
                @endif
            </div>
            
            <div class="right">
                <div class="detail-row">
                    <span class="detail-label">Issued At:</span>
                    <span class="detail-value">{{ $receipt['issued_at'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Donor:</span>
                    <span class="detail-value">
                        @if($receipt['donor'] === 'Anonymous')
                            Anonymous
                        @else
                            {{ $receipt['donor']['name'] }}<br>
                            <small>(ID: {{ $receipt['donor']['employee_id'] }})</small>
                        @endif
                    </span>
                </div>
            </div>
        </div>
        
        <div class="amount-section">
            <div class="amount-label">Donation Amount</div>
            <div class="amount-value">${{ number_format($receipt['amount'], 2) }}</div>
        </div>
        
        <div class="campaign-section">
            <div class="campaign-title">Campaign Supported</div>
            <div class="detail-row">
                <span class="detail-label">Campaign:</span>
                <span class="detail-value">{{ $receipt['campaign']['title'] }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Category:</span>
                <span class="detail-value">{{ $receipt['campaign']['category'] }}</span>
            </div>
        </div>
        
        <div class="organization-section">
            <div class="organization-name">{{ $receipt['organization']['name'] }}</div>
            <div class="organization-details">
                {{ $receipt['organization']['address'] }}<br>
                Tax ID: {{ $receipt['organization']['tax_id'] }}
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>This receipt serves as proof of your charitable contribution to ACME Corporation's CSR initiatives.</p>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
