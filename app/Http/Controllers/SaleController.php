<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = Sale::with(['party', 'items'])
            ->latest('id')
            ->paginate(25);

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $sales->items(),
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'total' => $sales->total(),
            ]);
        }

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::where('stock', '>', 0)->orderBy('name')->get();
        $parties = Party::whereIn('type', ['customer', 'both'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sales.create', compact('products', 'parties'));
    }

    public function show(Request $request, Sale $sale)
    {
        $sale->load(['party', 'items', 'transactions']);

        if ($this->isApiRequest($request)) {
            return response()->json(['data' => $sale]);
        }

        return view('sales.show', compact('sale'));
    }

    public function store(Request $request)
    {
        $sale = $this->recordSale($request);

        if ($this->isApiRequest($request)) {
            return response()->json(['data' => $sale], 201);
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Sale saved successfully.');
    }

    public function sync(Request $request)
    {
        return $this->store($request);
    }

    public function addPayment(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,bank,mobile_banking,other',
            'transaction_date' => 'nullable|date',
        ]);

        $sale = DB::transaction(function () use ($validated, $sale) {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $amountCents = $this->moneyToCents($validated['amount']);
            $dueCents = $this->moneyToCents($lockedSale->total_amount)
                - $this->moneyToCents($lockedSale->paid_amount);

            if ($amountCents > $dueCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the outstanding balance.',
                ]);
            }

            $lockedSale->paid_amount = $this->centsToMoney(
                $this->moneyToCents($lockedSale->paid_amount) + $amountCents,
            );
            $lockedSale->save();

            Transaction::create([
                'sale_id' => $lockedSale->id,
                'type' => 'in',
                'party_name' => $lockedSale->customer_name,
                'amount' => $this->centsToMoney($amountCents),
                'payment_method' => $validated['payment_method'],
                'category' => 'sale_payment',
                'description' => 'Payment for '.$lockedSale->sale_number,
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
            ]);

            return $lockedSale->load(['party', 'items', 'transactions']);
        });

        if ($this->isApiRequest($request)) {
            return response()->json(['data' => $sale]);
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Payment recorded successfully.');
    }

    private function recordSale(Request $request): Sale
    {
        $validated = $request->validate([
            'party_id' => 'nullable|integer|exists:parties,id',
            'customer_name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100000',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank,mobile_banking,other',
            'sale_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        return DB::transaction(function () use ($validated) {
            $party = null;

            if (! empty($validated['party_id'])) {
                $party = Party::query()
                    ->whereKey($validated['party_id'])
                    ->whereIn('type', ['customer', 'both'])
                    ->where('is_active', true)
                    ->first();

                if (! $party) {
                    throw ValidationException::withMessages([
                        'party_id' => 'Choose an active customer.',
                    ]);
                }
            }

            $productIds = collect($validated['items'])
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products no longer exist.',
                ]);
            }

            $totalCents = 0;
            $saleItems = [];

            foreach ($validated['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->name}. Available: {$product->stock}.",
                    ]);
                }

                $unitPriceCents = $this->moneyToCents($product->price);
                $lineTotalCents = $unitPriceCents * $quantity;
                $totalCents += $lineTotalCents;
                $saleItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $this->centsToMoney($unitPriceCents),
                    'line_total' => $this->centsToMoney($lineTotalCents),
                ];
            }

            $paidCents = $this->moneyToCents($validated['paid_amount'] ?? 0);

            if ($paidCents > $totalCents) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Payment cannot be greater than the sale total.',
                ]);
            }

            $saleDate = $validated['sale_date'] ?? now()->toDateString();
            $sale = Sale::create([
                'party_id' => $party?->id,
                'customer_name' => $party?->name ?? ($validated['customer_name'] ?? null),
                'total_amount' => $this->centsToMoney($totalCents),
                'paid_amount' => $this->centsToMoney($paidCents),
                'sale_date' => $saleDate,
                'notes' => $validated['notes'] ?? null,
            ]);
            $sale->sale_number = 'INV-'.date('Ymd', strtotime($saleDate)).'-'.str_pad(
                (string) $sale->id,
                6,
                '0',
                STR_PAD_LEFT,
            );
            $sale->save();

            foreach ($saleItems as $item) {
                $sale->items()->create([
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
                $item['product']->decrement('stock', $item['quantity']);
            }

            if ($paidCents > 0) {
                Transaction::create([
                    'sale_id' => $sale->id,
                    'type' => 'in',
                    'party_name' => $sale->customer_name,
                    'amount' => $this->centsToMoney($paidCents),
                    'payment_method' => $validated['payment_method'],
                    'category' => 'sale',
                    'description' => 'Payment for '.$sale->sale_number,
                    'transaction_date' => $saleDate,
                ]);
            }

            return $sale->load(['party', 'items', 'transactions']);
        });
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private function moneyToCents(string|int|float $amount): int
    {
        $formatted = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $formatted);

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function centsToMoney(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad(
            (string) ($amount % 100),
            2,
            '0',
            STR_PAD_LEFT,
        );
    }
}
