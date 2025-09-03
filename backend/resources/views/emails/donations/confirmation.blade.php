<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Confirmation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .thank-you {
            text-align: center;
            margin-bottom: 30px;
        }
        .thank-you h2 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .thank-you p {
            color: #718096;
            font-size: 16px;
            margin: 0;
        }
        .donation-details {
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: center;
        }
        .detail-row:last-child {
            margin-bottom: 0;
        }
        .detail-label {
            font-weight: 500;
            color: #4a5568;
        }
        .detail-value {
            font-weight: 600;
            color: #2d3748;
        }
        .amount {
            font-size: 24px;
            color: #38a169;
            font-weight: 700;
        }
        .campaign-info {
            background-color: #ebf8ff;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #3182ce;
        }
        .campaign-info h3 {
            margin: 0 0 15px 0;
            color: #2c5282;
            font-size: 18px;
        }
        .campaign-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        .campaign-description {
            color: #4a5568;
            line-height: 1.6;
        }
        .receipt-section {
            background-color: #f0fff4;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #38a169;
        }
        .receipt-section h3 {
            margin: 0 0 15px 0;
            color: #22543d;
            font-size: 18px;
        }
        .receipt-id {
            font-family: 'Courier New', monospace;
            background-color: #e6fffa;
            padding: 10px;
            border-radius: 4px;
            color: #234e52;
            font-weight: 600;
        }
        .footer {
            background-color: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .footer p {
            margin: 0 0 15px 0;
            opacity: 0.8;
        }
        .footer a {
            color: #63b3ed;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .social-links {
            margin-top: 20px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #a0aec0;
            text-decoration: none;
        }
        .social-links a:hover {
            color: #63b3ed;
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 0;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Thank You!</h1>
            <p>Your donation has been received and processed successfully</p>
        </div>

        <div class="content">
            <div class="thank-you">
                <h2>Dear {{ $user->name }},</h2>
                <p>Thank you for your generous donation to our CSR platform. Your contribution makes a real difference!</p>
            </div>

            <div class="donation-details">
                <h3 style="margin: 0 0 20px 0; color: #2d3748; font-size: 18px;">Donation Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value amount">${{ number_format($donation->amount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $donation->payment_method)) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $donation->created_at->format('F j, Y \a\t g:i A') }}</span>
                </div>
                @if($donation->transaction_id)
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value">{{ $donation->transaction_id }}</span>
                </div>
                @endif
                @if($donation->message)
                <div class="detail-row">
                    <span class="detail-label">Your Message:</span>
                    <span class="detail-value">{{ $donation->message }}</span>
                </div>
                @endif
            </div>

            <div class="campaign-info">
                <h3>Campaign Information</h3>
                <div class="campaign-title">{{ $campaign->title }}</div>
                <div class="campaign-description">{{ $campaign->description }}</div>
            </div>

            <div class="receipt-section">
                <h3>Receipt Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Receipt ID:</span>
                    <span class="receipt-id">{{ $receipt['receipt_id'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tax Deductible:</span>
                    <span class="detail-value">Yes</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tax Year:</span>
                    <span class="detail-value">{{ $receipt['tax_year'] }}</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <p style="color: #4a5568; font-size: 14px;">
                    Please keep this email for your tax records. If you have any questions about your donation, 
                    please contact our support team.
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>ACME Corporation CSR Platform</strong></p>
            <p>Making a difference together through corporate social responsibility</p>
            <p>For support: <a href="mailto:csr@acme.com">csr@acme.com</a></p>
            <div class="social-links">
                <a href="#">Website</a>
                <a href="#">LinkedIn</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </div>
</body>
</html>
