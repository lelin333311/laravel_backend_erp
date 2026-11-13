<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f8; color: #172033; font: 16px/1.5 system-ui, sans-serif; }
        main { width: min(100% - 24px, 720px); margin: 40px auto; }
        section { background: white; border: 1px solid #e1e6ee; border-radius: 14px; padding: 24px; }
        h1 { margin-top: 0; }
        .links { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        a { display: block; border-radius: 9px; padding: 14px; background: #1457c5; color: white; text-align: center; text-decoration: none; font-weight: 700; }
        a.secondary { background: #eaf0fa; color: #1457c5; }
        @media (max-width: 500px) { main { width: min(100% - 16px, 720px); margin: 12px auto; } section { padding: 18px; } .links { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <section>
        <h1>Sales</h1>
        <p>Create an invoice, record payment, and keep product stock updated.</p>
        <div class="links">
            <a href="{{ route('sales.create') }}">Create a sale</a>
            <a class="secondary" href="{{ route('sales.index') }}">Sales history</a>
            <a class="secondary" href="{{ route('products.index') }}">Manage products</a>
        </div>
    </section>
</main>
</body>
</html>
