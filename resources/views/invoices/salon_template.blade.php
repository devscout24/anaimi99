<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $booking->invoice_no }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 20px; background: #fff; }
        .invoice-box { max-width: 450px; margin: auto; padding: 20px; }
        
        .customer-header { display: flex; align-items: center; margin-bottom: 30px; position: relative; }
        .customer-img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-right: 15px; background: #eee; }
        .customer-info h2 { margin: 0; font-size: 20px; font-weight: 700; color: #333; }
        .customer-info p { margin: 4px 0 0; color: #999; font-size: 15px; letter-spacing: 0.5px; }
        
        .invoice-id-row { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .invoice-id { font-size: 22px; font-weight: 700; margin: 0; color: #000; }
        .paid-badge { background: #e8f7f0; color: #10b981; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }
        .invoice-date { color: #bbb; font-size: 15px; margin: 8px 0 25px 0; }
        
        .service-card { background: #f8f8f8; border-radius: 15px; padding: 20px; margin-bottom: 30px; border: 1px solid #f2f2f2; }
        .service-row { display: flex; align-items: flex-start; margin-bottom: 20px; }
        .service-row:last-child { margin-bottom: 0; }
        .icon-box { width: 35px; color: #ccc; font-size: 18px; margin-top: 2px; }
        .service-content { flex: 1; }
        .service-title-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .service-main-text { font-size: 16px; font-weight: 700; color: #333; margin: 0; }
        .service-price-text { color: #bbb; font-weight: 400; font-size: 15px; }
        .service-sub-text { color: #bbb; font-size: 13px; margin: 2px 0 0 0; font-weight: 400; }

        .bill-details { padding: 0 5px; }
        .bill-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 16px; color: #bbb; font-weight: 500; }
        .bill-value { color: #333; font-weight: 600; }
        .fee-value { color: #bbb; font-weight: 500; }
        
        .divider { border-top: 2px dashed #f0f0f0; margin: 25px 0; }
        
        .total-row { display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 20px; font-weight: 800; color: #000; }
        .total-amount { font-size: 22px; font-weight: 800; color: #c38b2d; }
        
        .footer { text-align: center; margin-top: 40px; color: #eee; font-size: 12px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="customer-header">
            @php
                $profileImage = $booking->customer->profile_image ? asset($booking->customer->profile_image) : null;
            @endphp
            @if($profileImage)
                <img src="{{ $profileImage }}" class="customer-img">
            @else
                <div class="customer-img" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0; color: #ccc; font-size: 24px; font-weight: bold;">{{ substr($booking->customer->name ?? 'C', 0, 1) }}</div>
            @endif
            <div class="customer-info">
                <h2>{{ $booking->customer->name ?? 'Thomas Smith' }}</h2>
                <p>{{ $booking->customer->phone ?? '+3********** 78' }}</p>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #f5f5f5; margin: 0;">

        <div class="invoice-id-row">
            <h1 class="invoice-id">#{{ $booking->invoice_no }}</h1>
            <span class="paid-badge">Paid</span>
        </div>
        <p class="invoice-date">{{ \Carbon\Carbon::parse($booking->created_at)->format('F d, Y') }}</p>

        <div class="service-card">
            @foreach($booking->items as $item)
            <div class="service-row">
                <div class="icon-box">✂️</div>
                <div class="service-content">
                    <div class="service-title-row">
                        <p class="service-main-text">{{ $item->service->service_name ?? 'Service' }} {{ $item->quantity > 1 ? ' x ' . $item->quantity : '' }}</p>
                    </div>
                    <p class="service-sub-text">{{ number_format($item->total, 0) }} €</p>
                </div>
            </div>
            @endforeach
            
            <div class="service-row">
                <div class="icon-box">📍</div>
                <div class="service-content">
                    <p class="service-main-text">{{ $booking->booking_type === 'home_barber' ? 'At home' : ($salon->salon_address ?? 'At Salon') }}</p>
                    <p class="service-sub-text">{{ $booking->booking_type === 'home_barber' ? 'At home' : 'Salon address' }}</p>
                </div>
            </div>

            @php
                $startTimeSlot = $booking->slots->sortBy(fn($s) => optional($s->scheduleTime)->scheduled_start_time)->first();
                $timeStr = 'N/A';
                $duration = 0;
                if ($startTimeSlot && $startTimeSlot->scheduleTime) {
                    $start = \Carbon\Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time);
                    $end = \Carbon\Carbon::parse($startTimeSlot->scheduleTime->scheduled_end_time);
                    $timeStr = $start->format('g:i A') . ' - ' . $end->format('g:i A');
                    $duration = $start->diffInMinutes($end);
                }
            @endphp
            <div class="service-row">
                <div class="icon-box">🕒</div>
                <div class="service-content">
                    <p class="service-main-text">{{ $timeStr }}</p>
                    <p class="service-sub-text">{{ $duration }} minutes</p>
                </div>
            </div>
        </div>

        <div class="bill-details">
            <div class="bill-row">
                <span>Service Amount</span>
                <span class="bill-value">{{ number_format($totalGenerated, 0) }} €</span>
            </div>
            <div class="bill-row">
                <span>Platform Fee ({{ round($commissionRate) }}%)</span>
                <span class="fee-value">-{{ number_format($adminCommission, 0) }} €</span>
            </div>
        </div>

        <div class="divider"></div>

        <div class="total-row">
            <span class="total-label">Amount Received</span>
            <span class="total-amount">{{ number_format($amountReceived, 0) }} €</span>
        </div>

        <div class="footer">
            <p>{{ $salon->business_name ?? $salon->name }}</p>
        </div>
    </div>
</body>
</html>
