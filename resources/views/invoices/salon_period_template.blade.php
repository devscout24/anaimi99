<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Period {{ $period }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 20px; background: #fff; }
        .invoice-box { max-width: 500px; margin: auto; padding: 10px; }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .invoice-id { font-size: 24px; font-weight: 800; margin: 0; }
        .paid-badge { background: #e8f7f0; color: #10b981; padding: 4px 12px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .period-text { color: #bbb; font-size: 16px; margin: 5px 0 30px 0; font-weight: 500; }
        
        .summary-card { background: #fcfcfc; border-radius: 20px; padding: 25px; margin-bottom: 35px; border: 1px solid #f2f2f2; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 17px; color: #999; font-weight: 500; }
        .summary-value { color: #333; font-weight: 600; }
        .negative { color: #f43f5e; font-weight: 500; }
        
        .divider { border-top: 2px dashed #f0f0f0; margin: 20px 0; }
        .net-received { display: flex; justify-content: space-between; align-items: center; }
        .net-label { font-size: 20px; font-weight: 800; color: #000; }
        .net-amount { font-size: 24px; font-weight: 800; color: #000; }

        .section-title { font-size: 20px; font-weight: 800; margin: 0 0 20px 0; }
        .service-list { margin-bottom: 30px; }
        .service-item { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 5px; }
        .service-info h3 { margin: 0; font-size: 17px; font-weight: 700; color: #1a1a1a; }
        .service-info p { margin: 4px 0; color: #bbb; font-size: 14px; font-weight: 500; }
        .service-price { font-size: 17px; font-weight: 700; color: #1a1a1a; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header-row">
            <h1 class="invoice-id">Invoice Details</h1>
            <button style="border: none; background: #f5f5f5; border-radius: 50%; width: 35px; height: 35px; cursor: pointer;">✕</button>
        </div>

        <div class="invoice-id-row" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <h2 style="font-size: 22px; font-weight: 800; margin: 0;">{{ $invoice_no }}</h2>
            <span class="paid-badge">Paid</span>
        </div>
        <p class="period-text">{{ $period }}</p>

        <div class="summary-card">
            <div class="summary-row">
                <span>Total Generated</span>
                <span class="summary-value">€{{ number_format($totalGenerated, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Online Payment Commission ({{ $rate }}%)</span>
                <span class="negative">-€{{ number_format($onlineComm, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>COD Commission ({{ $rate }}%)</span>
                <span class="negative">-€{{ number_format($codComm, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Custom Order Commission ({{ $rate }}%)</span>
                <span class="negative">-€{{ number_format($customComm, 2) }}</span>
            </div>
            
            <div class="divider"></div>
            
            <div class="net-received">
                <span class="net-label">Net Amount Received</span>
                <span class="net-amount">€{{ number_format($netAmount, 2) }}</span>
            </div>
        </div>

        <h2 class="section-title">Services for the Period</h2>
        <div class="service-list">
            @foreach($bookings as $booking)
            @php
                $startTimeSlot = $booking->slots->sortBy(function($slot) {
                    return optional($slot->scheduleTime)->scheduled_start_time;
                })->first();
                $timeStr = '';
                if ($startTimeSlot && $startTimeSlot->scheduleTime) {
                    $timeStr = ', ' . \Carbon\Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time)->format('g:i A');
                }
            @endphp
            <div class="service-item">
                <div class="service-info">
                    <h3>{{ $booking->customer->name ?? 'Unknown' }}</h3>
                    <p>{{ $booking->items->map(fn($i) => ($i->service->service_name ?? '') . ($i->quantity > 1 ? ' x ' . $i->quantity : ''))->implode(' + ') }}</p>
                    <p>{{ \Carbon\Carbon::parse($booking->booking_date)->format('F d') }}{{ $timeStr }}</p>
                </div>
                <div class="service-price">€{{ number_format($booking->total_price, 2) }}</div>
            </div>
            @endforeach
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #bbb; font-size: 14px; font-weight: 500;">{{ $salon->business_name ?? $salon->name }}</p>
        </div>
    </div>
</body>
</html>
