<?php

use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('transactions');
    Schema::dropIfExists('sale_items');
    Schema::dropIfExists('sales');
    Schema::dropIfExists('parties');
    Schema::dropIfExists('products');

    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->decimal('price', 10, 2);
        $table->integer('stock')->default(0);
        $table->timestamps();
    });

    Schema::create('parties', function (Blueprint $table) {
        $table->id();
        $table->string('party_code')->unique();
        $table->string('name');
        $table->string('type');
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->text('address')->nullable();
        $table->decimal('opening_balance', 15, 2)->default(0);
        $table->string('balance_type')->default('debit');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->string('sale_number')->nullable()->unique();
        $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
        $table->string('customer_name')->nullable();
        $table->decimal('total_amount', 15, 2);
        $table->decimal('paid_amount', 15, 2)->default(0);
        $table->date('sale_date');
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('sale_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
        $table->string('product_name');
        $table->unsignedInteger('quantity');
        $table->decimal('unit_price', 10, 2);
        $table->decimal('line_total', 15, 2);
        $table->timestamps();
    });

    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
        $table->string('type');
        $table->string('party_name')->nullable();
        $table->decimal('amount', 15, 2);
        $table->string('payment_method')->default('cash');
        $table->string('category')->nullable();
        $table->text('description')->nullable();
        $table->date('transaction_date');
        $table->timestamps();
    });
});

it('creates an invoice, records payment, and deducts product stock', function () {
    $product = Product::create(['name' => 'Tea', 'price' => '12.50', 'stock' => 5]);

    $this->postJson('/api/v1/sales', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
        'paid_amount' => '10.00',
        'payment_method' => 'cash',
    ])
        ->assertCreated()
        ->assertJsonPath('data.total_amount', '25.00')
        ->assertJsonPath('data.paid_amount', '10.00')
        ->assertJsonPath('data.due_amount', '15.00')
        ->assertJsonPath('data.payment_status', 'due')
        ->assertJsonPath('data.items.0.product_name', 'Tea');

    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
    $this->assertDatabaseHas('transactions', [
        'type' => 'in',
        'amount' => '10.00',
        'category' => 'sale',
    ]);
    $this->assertDatabaseCount('sales', 1);
    $this->assertDatabaseCount('sale_items', 1);
});

it('rejects a sale when there is not enough stock without saving partial data', function () {
    $product = Product::create(['name' => 'Tea', 'price' => '12.50', 'stock' => 1]);

    $this->postJson('/api/v1/sales', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
        'paid_amount' => '0.00',
        'payment_method' => 'cash',
    ])->assertUnprocessable();

    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseCount('transactions', 0);
});

it('records additional payments and rejects overpayment', function () {
    $product = Product::create(['name' => 'Tea', 'price' => '12.50', 'stock' => 5]);
    $sale = DB::table('sales')->insertGetId([
        'sale_number' => 'INV-20261106-000001',
        'total_amount' => '25.00',
        'paid_amount' => '5.00',
        'sale_date' => '2026-11-06',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson("/api/v1/sales/{$sale}/payments", [
        'amount' => '20.00',
        'payment_method' => 'mobile_banking',
    ])
        ->assertOk()
        ->assertJsonPath('data.paid_amount', '25.00')
        ->assertJsonPath('data.due_amount', '0.00')
        ->assertJsonPath('data.payment_status', 'paid');

    $this->postJson("/api/v1/sales/{$sale}/payments", [
        'amount' => '0.01',
        'payment_method' => 'cash',
    ])->assertUnprocessable();

    $this->assertDatabaseHas('sales', ['id' => $sale, 'paid_amount' => '25.00']);
    $this->assertDatabaseCount('transactions', 1);
});

it('allows a sale to be created from the website', function () {
    $product = Product::create(['name' => 'Tea', 'price' => '12.50', 'stock' => 3]);

    $this->get('/sales/create')
        ->assertOk()
        ->assertSee('Save sale');

    $this->post('/sales', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
        'paid_amount' => '12.50',
        'payment_method' => 'cash',
    ])->assertRedirect();

    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
    $this->assertDatabaseHas('sales', ['total_amount' => '12.50', 'paid_amount' => '12.50']);
});

it('prevents sale payments from being changed outside the sale payment flow', function () {
    $sale = DB::table('sales')->insertGetId([
        'sale_number' => 'INV-20261106-000001',
        'total_amount' => '25.00',
        'paid_amount' => '5.00',
        'sale_date' => '2026-11-06',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $transaction = DB::table('transactions')->insertGetId([
        'sale_id' => $sale,
        'type' => 'in',
        'amount' => '5.00',
        'payment_method' => 'cash',
        'category' => 'sale',
        'transaction_date' => '2026-11-06',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->putJson("/api/transactions/{$transaction}", ['amount' => '8.00'])
        ->assertUnprocessable();
    $this->deleteJson("/api/transactions/{$transaction}")
        ->assertUnprocessable();

    $this->assertDatabaseHas('transactions', ['id' => $transaction, 'amount' => '5.00']);
    $this->assertDatabaseHas('sales', ['id' => $sale, 'paid_amount' => '5.00']);
});
