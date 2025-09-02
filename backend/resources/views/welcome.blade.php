<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ACME CSR Platform') }} API</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .api-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            border-left: 4px solid #667eea;
        }
        
        .api-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        
        .status {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 8px;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .links {
                grid-template-columns: 1fr;
            }
        }
            </style>
    </head>
<body>
    <div class="container">
        <div class="logo">ACME CSR Platform</div>
        <div class="subtitle">Corporate Social Responsibility API Server</div>
        
        <div class="api-info">
            <h3>🚀 API Server Status</h3>
            <p>Welcome to the ACME CSR Platform API. This backend provides RESTful endpoints for campaign management, donations, user authentication, and administrative functions.</p>
            
            <div class="status">
                Server Status: <span class="status-badge">Running</span>
            </div>
                </div>
        
        <div class="links">
            <a href="/docs/api" class="btn btn-primary">📖 API Documentation</a>
            <a href="http://localhost:3000" class="btn btn-secondary">🖥️ Access Application</a>
        </div>

        <div class="status">
            <small>{{ config('app.name') }} v1.0.0 | Environment: {{ config('app.env') }}</small>
        </div>
    </div>
    </body>
</html>
