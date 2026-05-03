<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $booking->invoice_no }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.5; margin: 0; padding: 40px; background: #fff; }
        .invoice-box { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header .logo h1 { margin: 0; color: #000; font-size: 28px; letter-spacing: -1px; }
        .header .status { background: #e6f7ef; color: #1fb36c; padding: 5px 15px; border-radius: 4px; font-weight: bold; font-size: 14px; text-transform: uppercase; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .info-block h3 { font-size: 12px; text-transform: uppercase; color: #999; margin-bottom: 10px; letter-spacing: 1px; }
        .info-block p { margin: 0; font-weight: 500; }
        
        .service-card { background: #f9f9f9; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .service-card .title { font-size: 18px; font-weight: bold; margin-bottom: 15px; display: flex; align-items: center; }
        .service-card .title i { margin-right: 10px; font-size: 20px; }
        .service-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; }
        .service-details div { display: flex; align-items: center; color: #666; }
        
        .summary-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .summary-table td { padding: 12px 0; border-bottom: 1px solid #eee; }
        .summary-table .label { color: #666; }
        .summary-table .value { text-align: right; font-weight: 600; }
        .summary-table .total { font-size: 24px; color: #000; font-weight: 800; border-bottom: none; padding-top: 25px; }
        
        .footer { margin-top: 50px; text-align: center; color: #aaa; font-size: 12px; }
        
        @media print {
            body { padding: 0; }
            .invoice-box { border: none; box-shadow: none; }
            .service-card { background: #f9f9f9 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="logo">
                <h1>ANAIMI</h1>
            </div>
            <div class="status">
                {{ ucfirst($booking->payment_status) }}
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Invoice Details</h3>
                <p>#{{ $booking->invoice_no }}</p>
                <p style="color: #666; font-size: 14px; font-weight: normal; margin-top: 5px;">{{ $booking->created_at->format('F d, Y') }}</p>
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Customer</h3>
                <p>{{ $booking->customer->name ?? 'N/A' }}</p>
                <p style="color: #666; font-size: 14px; font-weight: normal; margin-top: 5px;">{{ $booking->customer->phone ?? '' }}</p>
            </div>
        </div>

        <div class="service-card">
            <div class="title">
                Booking Information
            </div>
            <div class="service-details">
                <div>
                    <strong>Services:</strong>&nbsp;
                    {{ $booking->items->map(function ($item) { return $item->service->service_name ?? 'Unknown'; })->implode(' + ') }}
                </div>
                <div>
                    <strong>Type:</strong>&nbsp;
                    {{ ucfirst(str_replace('_', ' ', $booking->booking_type)) }}
                </div>
                <div>
                    <strong>Date:</strong>&nbsp;
                    {{ $booking->booking_date }}
                </div>
                <div>
                    <strong>Time:</strong>&nbsp;
                    @php
                        $startTimeSlot = $booking->slots->sortBy(function($slot) {
                            return optional($slot->scheduleTime)->scheduled_start_time;
                        })->first();
                        if ($startTimeSlot && $startTimeSlot->scheduleTime) {
                            $start = \Carbon\Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time);
                            $end = \Carbon\Carbon::parse($startTimeSlot->scheduleTime->scheduled_end_time);
                            echo $start->format('g:i A') . ' - ' . $end->format('g:i A');
                        } else {
                            echo 'N/A';
                        }
                    @endphp
                </div>
            </div>
        </div>

        <table class="summary-table">
            <tr>
                <td class="label">Service Amount</td>
                <td class="value">{{ number_format($booking->total_price, 2) }} €</td>
            </tr>
            <tr>
                <td class="label">Platform Fee (20%)</td>
                <td class="value">- {{ number_format($booking->total_price * 0.20, 2) }} €</td>
            </tr>
            <tr>
                <td class="total label">Amount Received</td>
                <td class="total value">{{ number_format($booking->total_price * 0.80, 2) }} €</td>
            </tr>
        </table>

        <div class="footer">
            <p>Thank you for using ANAIMI. If you have any questions, please contact our support.</p>
            <p>&copy; {{ date('Y') }} ANAIMI. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Auto-print when opened for downloading
        // window.print();
    </script>
</body>
</html>
