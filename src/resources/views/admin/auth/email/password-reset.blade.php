<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(145deg, #111827 0%, #172554 52%, #312e81 100%);
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: #e0e7ff;
        }
        .content {
            padding: 32px 24px;
        }
        .content h2 {
            color: #1a1a1a;
            font-size: 20px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .content p {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            background-color: #1a73e8;
            color: white;
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 6px;
            display: inline-block;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .button:hover {
            background-color: #1558b0;
        }
        .link-container {
            background-color: #f8fafd;
            padding: 16px;
            border-radius: 6px;
            margin: 24px 0;
            word-break: break-all;
        }
        .link-container p {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #718096;
            font-weight: 600;
        }
        .link-container a {
            color: #1a73e8;
            font-size: 13px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #fbbf24;
            padding: 16px;
            margin: 24px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            font-size: 13px;
            color: #92400e;
        }
        .footer {
            background-color: #f8fafd;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 0;
            font-size: 12px;
            color: #718096;
        }
        .footer a {
            color: #1a73e8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset</h1>
            <p>Anugerah3D Admin Portal</p>
        </div>

        <div class="content">
            <h2>Reset Your Password</h2>
            <p>Hi there,</p>
            <p>We received a request to reset the password for your Anugerah3D admin account. Click the button below to set a new password:</p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>

            <p>Or copy and paste this link in your browser:</p>

            <div class="link-container">
                <p>Reset Link:</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </div>

            <div class="warning">
                <p>⏰ <strong>This link expires in 1 hour.</strong> If you didn't request a password reset, please ignore this email or contact support.</p>
            </div>

            <p>For security reasons, we'll never ask you to provide your password via email.</p>
        </div>

        <div class="footer">
            <p>© 2026 Anugerah3D. All rights reserved.</p>
            <p><a href="https://anugerah3d.com">Visit Website</a> • <a href="mailto:support@anugerah3d.com">Contact Support</a></p>
        </div>
    </div>
</body>
</html>
