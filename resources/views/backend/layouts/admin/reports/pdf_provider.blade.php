<!DOCTYPE html>
<html>
<head>
    <title>Financial Report - {{ $user->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2 f2 f2; }
        .summary { margin-top: 30px; }
        .summary-item { margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Financial Report</h2>
        <p><strong>Provider:</strong> {{ $user->name }} ({{ ucfirst($user->role) }})</p>
        <p><strong>Period:</strong> {{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Booking ID</th>
                <th>Type</th>
                <th>Total Amount (€)</th>
                <th>Admin Comm. (€)</th>
                <th>Provider Earn (€)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->created_at->format('Y-m-d') }}</td>
                <td>{{ $payment->booking_id }}</td>
                <td>{{ ucfirst($payment->payment_type) }}</td>
                <td>{{ number_format($payment->amount, 2) }}</td>
                <td>{{ number_format($payment->admin_commission, 2) }}</td>
                <td>{{ number_format($payment->provider_earnings, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Summary Statistics</h3>
        <div class="summary-item">Total Online Payments: <strong>€{{ number_format($stats['online'], 2) }}</strong></div>
        <div class="summary-item">Total COD: <strong>€{{ number_format($stats['cod'], 2) }}</strong></div>
        <div class="summary-item">Total Onsite: <strong>€{{ number_format($stats['onsite'], 2) }}</strong></div>
        <div class="summary-item">Total Custom: <strong>€{{ number_format($stats['custom'], 2) }}</strong></div>
        <hr>
        <div class="summary-item">Gross Total: <strong>€{{ number_format($stats['total_amount'], 2) }}</strong></div>
        <div class="summary-item">Total Admin Commission (Revenue): <strong style="color: red;">€{{ number_format($stats['total_commission'], 2) }}</strong></div>
        <div class="summary-item">Total Provider Net Earnings: <strong style="color: green;">€{{ number_format($stats['provider_earnings'], 2) }}</strong></div>
    </div>
</body>
</html>
