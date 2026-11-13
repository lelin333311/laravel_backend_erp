<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales history</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f8; color: #172033; font: 16px/1.5 system-ui, sans-serif; }
        main { width: min(100% - 24px, 1000px); margin: 24px auto; }
        nav, .panel { background: white; border: 1px solid #e1e6ee; border-radius: 12px; }
        nav { display: flex; justify-content: space-between; padding: 14px 18px; margin-bottom: 18px; }
        .panel { padding: 18px; overflow-x: auto; }
        a { color: #1457c5; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 11px 8px; border-bottom: 1px solid #e8ebf0; text-align: left; }
        .status { font-weight: 700; }
        .paid { color: #167443; }
        .due { color: #a15a00; }
        .pagination { display: flex; justify-content: space-between; margin-top: 16px; }
        @media (max-width: 600px) { main { width: min(100% - 16px, 1000px); margin: 8px auto; } }
    </style>
</head>
<body>
<main>
    <nav>
        <strong>Sales history</strong>
        <span><a href="{{ route('sales.create') }}">New sale</a> · <a href="{{ route('products.index') }}">Products</a></span>
    </nav>
    @if(session('success'))<p>{{ session('success') }}</p>@endif
    <section class="panel">
        <table>
            <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->sale_number }}</a></td>
                    <td>{{ $sale->sale_date->format('Y-m-d') }}</td>
                    <td>{{ $sale->customer_name ?? 'Walk-in customer' }}</td>
                    <td>{{ $sale->total_amount }}</td>
                    <td>{{ $sale->paid_amount }}</td>
                    <td>{{ $sale->due_amount }}</td>
                    <td class="status {{ $sale->payment_status }}">{{ ucfirst($sale->payment_status) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No sales recorded yet. <a href="{{ route('sales.create') }}">Create your first sale</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $sales->links() }}</div>
    </section>
</main>
</body>
</html>
