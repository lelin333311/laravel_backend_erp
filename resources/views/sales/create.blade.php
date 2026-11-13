<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New sale</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f8; color: #172033; font: 16px/1.5 system-ui, sans-serif; }
        main { width: min(100% - 24px, 900px); margin: 24px auto; }
        nav, .panel, .product { background: white; border: 1px solid #e1e6ee; border-radius: 12px; }
        nav { display: flex; justify-content: space-between; gap: 12px; padding: 14px 18px; margin-bottom: 18px; }
        nav a, a { color: #1457c5; }
        .panel { padding: 20px; margin-bottom: 16px; }
        h1, h2, p { margin-top: 0; }
        h1 { font-size: 1.5rem; }
        h2 { font-size: 1.1rem; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, select, textarea, button { font: inherit; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd3df; border-radius: 8px; background: white; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .products { display: grid; gap: 10px; }
        .product { display: grid; grid-template-columns: 24px 1fr 100px 100px; align-items: center; gap: 12px; padding: 12px; }
        .product input[type="checkbox"] { width: 20px; height: 20px; }
        .product input[type="number"] { padding: 8px; }
        .muted { color: #667085; font-size: .9rem; }
        .total { display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700; }
        button { border: 0; border-radius: 8px; padding: 11px 16px; background: #1457c5; color: white; font-weight: 700; cursor: pointer; }
        .errors { color: #a32121; background: #fff0f0; border-radius: 8px; padding: 12px 18px; margin-bottom: 16px; }
        @media (max-width: 600px) {
            main { width: min(100% - 16px, 900px); margin: 8px auto; }
            .panel { padding: 15px; }
            .grid { grid-template-columns: 1fr; }
            .product { grid-template-columns: 24px 1fr 84px; gap: 8px; }
            .product .stock { display: none; }
        }
    </style>
</head>
<body>
<main>
    <nav>
        <strong>Sales</strong>
        <span><a href="{{ route('sales.index') }}">Sales history</a> · <a href="{{ route('products.index') }}">Products</a></span>
    </nav>

    <h1>New sale</h1>

    @if($errors->any())
        <div class="errors">
            <strong>Please correct the following:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sales.store') }}" id="sale-form">
        @csrf
        <section class="panel">
            <h2>Customer and date</h2>
            <div class="grid">
                <div>
                    <label for="party_id">Customer</label>
                    <select name="party_id" id="party_id">
                        <option value="">Walk-in customer</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" @selected(old('party_id') == $party->id)>{{ $party->name }}{{ $party->phone ? ' · '.$party->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="customer_name">Walk-in customer name (optional)</label>
                    <input name="customer_name" id="customer_name" value="{{ old('customer_name') }}" maxlength="255">
                </div>
                <div>
                    <label for="sale_date">Sale date</label>
                    <input type="date" name="sale_date" id="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" required>
                </div>
                <div>
                    <label for="notes">Notes (optional)</label>
                    <input name="notes" id="notes" value="{{ old('notes') }}" maxlength="5000">
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Products</h2>
            @if($products->isEmpty())
                <p>No products with stock are available. <a href="{{ route('products.create') }}">Add products</a> or update stock first.</p>
            @else
                <div class="products">
                    @foreach($products as $product)
                        <label class="product">
                            <input type="checkbox" class="product-choice" data-id="{{ $product->id }}" data-price="{{ $product->price }}">
                            <span>{{ $product->name }}<br><span class="muted">Price: {{ number_format((float) $product->price, 2) }}</span></span>
                            <input class="quantity" type="number" min="1" max="{{ $product->stock }}" value="1" aria-label="Quantity for {{ $product->name }}" disabled>
                            <span class="muted stock">Stock: {{ $product->stock }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
            <div id="item-inputs"></div>
        </section>

        <section class="panel">
            <h2>Payment</h2>
            <div class="grid">
                <div>
                    <label for="paid_amount">Amount received</label>
                    <input type="number" name="paid_amount" id="paid_amount" min="0" step="0.01" value="{{ old('paid_amount', '0.00') }}" required>
                    <p class="muted">Enter 0 for a fully unpaid sale. You can record more payments later.</p>
                </div>
                <div>
                    <label for="payment_method">Payment method</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="mobile_banking">Mobile banking</option>
                        <option value="card">Card</option>
                        <option value="bank">Bank transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <p class="total"><span>Sale total</span><span id="sale-total">0.00</span></p>
            <button type="submit" @disabled($products->isEmpty())>Save sale</button>
        </section>
    </form>
</main>
<script>
    const form = document.getElementById('sale-form');
    const choices = [...document.querySelectorAll('.product-choice')];
    const itemInputs = document.getElementById('item-inputs');
    const totalLabel = document.getElementById('sale-total');

    function updateSaleTotal() {
        let total = 0;
        choices.forEach((choice) => {
            const quantity = choice.closest('.product').querySelector('.quantity');
            quantity.disabled = !choice.checked;
            if (choice.checked) total += Number(choice.dataset.price) * Number(quantity.value || 0);
        });
        totalLabel.textContent = total.toFixed(2);
    }

    choices.forEach((choice) => {
        choice.addEventListener('change', updateSaleTotal);
        choice.closest('.product').querySelector('.quantity').addEventListener('input', updateSaleTotal);
    });

    form.addEventListener('submit', (event) => {
        itemInputs.replaceChildren();
        let count = 0;
        choices.filter((choice) => choice.checked).forEach((choice) => {
            const quantity = choice.closest('.product').querySelector('.quantity');
            if (!quantity.value || Number(quantity.value) < 1 || Number(quantity.value) > Number(quantity.max)) {
                quantity.reportValidity();
                event.preventDefault();
                return;
            }
            [['product_id', choice.dataset.id], ['quantity', quantity.value]].forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${count}][${key}]`;
                input.value = value;
                itemInputs.append(input);
            });
            count++;
        });
        if (count === 0) {
            event.preventDefault();
            alert('Select at least one product.');
        }
    });

    updateSaleTotal();
</script>
</body>
</html>
