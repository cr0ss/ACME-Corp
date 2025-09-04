<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Donation Received</title>
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
            background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
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
        .greeting {
            text-align: center;
            margin-bottom: 30px;
        }
        .greeting h2 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .greeting p {
            color: #718096;
            font-size: 16px;
            margin: 0;
        }
        .donation-alert {
            background-color: #f0fff4;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #38a169;
            text-align: center;
        }
        .donation-alert h3 {
            margin: 0 0 15px 0;
            color: #22543d;
            font-size: 20px;
        }
        .amount {
            font-size: 32px;
            color: #38a169;
            font-weight: 700;
            margin: 15px 0;
        }
        .donor-info {
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        .donor-info h3 {
            margin: 0 0 20px 0;
            color: #2d3748;
            font-size: 18px;
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
        .campaign-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #3182ce;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #4a5568;
            font-size: 14px;
        }
        .message-section {
            background-color: #fffaf0;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #ed8936;
        }
        .message-section h3 {
            margin: 0 0 15px 0;
            color: #744210;
            font-size: 18px;
        }
        .donor-message {
            font-style: italic;
            color: #744210;
            background-color: #fef5e7;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #ed8936;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
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
            .campaign-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 New Donation!</h1>
            <p>Your campaign has received a generous contribution</p>
        </div>

        <div class="content">
            <div class="greeting">
                <h2>Hello {{ $campaignOwner->name }},</h2>
                <p>Great news! Your campaign has received a new donation.</p>
            </div>

            <div class="donation-alert">
                <h3>New Donation Received</h3>
                <div class="amount">${{ number_format($donation->amount, 2) }}</div>
                <p style="margin: 0; color: #22543d;">
                    @if($donation->anonymous)
                        From an anonymous donor
                    @else
                        From {{ $donor->name }}
                    @endif
                </p>
            </div>

            <div class="donor-info">
                <h3>Donation Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value">${{ number_format($donation->amount, 2) }}</span>
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
            </div>

            <div class="campaign-info">
                <h3>Campaign Information</h3>
                <div class="campaign-title">{{ $campaign->title }}</div>
                <div style="color: #4a5568; line-height: 1.6; margin-bottom: 20px;">
                    {{ $campaign->description }}
                </div>
                <div class="campaign-stats">
                    <div class="stat-item">
                        <div class="stat-number">{{ number_format($campaign->current_amount, 2) }}</div>
                        <div class="stat-label">Total Raised</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{ $campaign->donations_count ?? 0 }}</div>
                        <div class="stat-label">Total Donations</div>
                    </div>
                </div>
            </div>

            @if($donation->message)
            <div class="message-section">
                <h3>Message from Donor</h3>
                <div class="donor-message">
                    "{{ $donation->message }}"
                </div>
            </div>
            @endif

            <div class="cta-section">
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/campaigns/{{ $campaign->id }}" class="cta-button">
                    View Campaign Details
                </a>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <p style="color: #4a5568; font-size: 14px;">
                    Keep up the great work! Your campaign is making a real difference in our community.
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>ACME Corporation CSR Platform</strong></p>
            <p>Empowering employees to make a difference</p>
            <p>For support: <a href="mailto:csr@acme.com">csr@acme.com</a></p>
        </div>
    </div>
</body>
</html>
