<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->sale_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f8; color: #172033; font: 16px/1.5 system-ui, sans-serif; }
        main { width: min(100% - 24px, 900px); margin: 24px auto; }
        nav, .panel { background: white; border: 1px solid #e1e6ee; border-radius: 12px; }
        nav { display: flex; justify-content: space-between; padding: 14px 18px; margin-bottom: 18px; }
        .panel { padding: 20px; margin-bottom: 16px; }
        a { color: #1457c5; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 6px; border-bottom: 1px solid #e8ebf0; text-align: left; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .summary div { background: #f6f8fb; border-radius: 8px; padding: 12px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, select, button { width: 100%; font: inherit; padding: 10px 12px; border: 1px solid #cbd3df; border-radius: 8px; }
        button { border: 0; background: #1457c5; color: white; font-weight: 700; margin-top: 12px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .success { color: #167443; }
        .errors { color: #a32121; }
        @media (max-width: 600px) { main { width: min(100% - 16px, 900px); margin: 8px auto; } .summary { grid-template-columns: 1fr; } .panel { padding: 15px; } }
    </style>
</head>
<body>
<main>
    <nav><strong>Invoice {{ $sale->sale_number }}</strong><span><a href="{{ route('sales.create') }}">New sale</a> · <a href="{{ route('sales.index') }}">Sales history</a></span></nav>
    @if(session('success'))<p class="success">{{ session('success') }}</p>@endif
    @if($errors->any())<ul class="errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <section class="panel">
        <p><strong>Date:</strong> {{ $sale->sale_date->format('Y-m-d') }}</p>
        <p><strong>Customer:</strong> {{ $sale->customer_name ?? 'Walk-in customer' }}</p>
        <table>
            <thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead>
            <tbody>@foreach($sale->items as $item)
                <tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit_price }}</td><td>{{ $item->line_total }}</td></tr>
            @endforeach</tbody>
        </table>
        <div class="summary">
            <div><strong>Total</strong><br>{{ $sale->total_amount }}</div>
            <div><strong>Paid</strong><br>{{ $sale->paid_amount }}</div>
            <div><strong>Due</strong><br>{{ $sale->due_amount }} ({{ ucfirst($sale->payment_status) }})</div>
        </div>
        @if($sale->notes)<p><strong>Notes:</strong> {{ $sale->notes }}</p>@endif
    </section>

    @if($sale->transactions->isNotEmpty())
        <section class="panel">
            <h2>Payments</h2>
            <table><thead><tr><th>Date</th><th>Method</th><th>Amount</th></tr></thead><tbody>
                @foreach($sale->transactions as $transaction)
                    <tr><td>{{ $transaction->transaction_date->format('Y-m-d') }}</td><td>{{ ucfirst(str_replace('_', ' ', $transaction->payment_method)) }}</td><td>{{ $transaction->amount }}</td></tr>
                @endforeach
            </tbody></table>
        </section>
    @endif

    @if($sale->payment_status === 'due')
        <section class="panel">
            <h2>Record payment</h2>
            <form method="POST" action="{{ route('sales.payments.store', $sale) }}">
                @csrf
                <div class="grid">
                    <div><label for="amount">Amount (up to {{ $sale->due_amount }})</label><input id="amount" name="amount" type="number" min="0.01" max="{{ $sale->due_amount }}" step="0.01" value="{{ old('amount') }}" required></div>
                    <div><label for="payment_method">Method</label><select id="payment_method" name="payment_method" required><option value="cash">Cash</option><option value="mobile_banking">Mobile banking</option><option value="card">Card</option><option value="bank">Bank transfer</option><option value="other">Other</option></select></div>
                </div>
                <button type="submit">Save payment</button>
            </form>
        </section>
    @endif
</main>
</body>
</html>
