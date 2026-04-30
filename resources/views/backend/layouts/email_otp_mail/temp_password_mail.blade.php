<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Temporary Password</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f7f9fc;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .email-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .email-header h2 {
            color: #4A90E2;
            margin-bottom: 10px;
        }

        .password-box {
            font-size: 24px;
            font-weight: bold;
            color: #4A90E2;
            text-align: center;
            border: 2px dashed #4A90E2;
            padding: 15px 20px;
            margin: 30px 0;
            letter-spacing: 3px;
            background-color: #f0f6ff;
            border-radius: 8px;
            word-break: break-all;
        }

        .email-body {
            font-size: 16px;
            line-height: 1.6;
            text-align: center;
        }

        .note {
            background-color: #fff8e1;
            border-left: 4px solid #f9a825;
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: left;
            font-size: 14px;
            color: #555;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

    <div class="email-container">
        <div class="email-header">
            <h2>Welcome to the Team!</h2>
            <p>Hello <strong>{{ $barber->name ?? 'Barber' }}</strong>,</p>
        </div>

        <div class="email-body">
            <p>You have been added to a salon. Below is your temporary password to log in:</p>

            <div class="password-box">
                {{ $tempPassword }}
            </div>

            <div class="note">
                ⚠️ <strong>Important:</strong> Please log in and change your password immediately after your first login. Do not share this password with anyone.
            </div>

            <p>Your login email: <strong>{{ $barber->email }}</strong></p>
            <p>If you did not expect this email, please contact the salon admin.</p>
        </div>

        <div class="footer">
            &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>

</body>
</html>
