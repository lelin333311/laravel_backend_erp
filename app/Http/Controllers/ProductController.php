<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // READ: সব Product দেখাবে
    public function index(Request $request)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            $perPage = $request->get('per_page', 20);
            $products = Product::orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'data' => $products->items(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ]);
        }

        $products = Product::latest()->get();

        return view('index', compact('products'));
    }

    // CREATE: Add Product Form দেখাবে
    public function create()
    {
        return view('create');
    }

    // CREATE: Database-এ নতুন Product Save করবে
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product added successfully!');
    }

    // UPDATE: Edit Form দেখাবে
    public function edit(Product $product)
    {
        return view('edit', compact('product'));
    }

    // UPDATE: Database-এ Product Update করবে
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully!');
    }

    // DELETE: Product Delete করবে
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $products = Product::where('name', 'LIKE', "%{$q}%")
            ->limit(50)
            ->get();

        return response()->json(['data' => $products]);
    }

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'nullable|integer|exists:products,id',
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.stock' => 'required|integer|min:0',
        ]);

        $products = DB::transaction(function () use ($validated) {
            return collect($validated['products'])->map(function (array $data) {
                if (! empty($data['id'])) {
                    $product = Product::query()->lockForUpdate()->findOrFail($data['id']);
                    $product->update([
                        'name' => $data['name'],
                        'price' => $data['price'],
                        'stock' => $data['stock'],
                    ]);

                    return $product->fresh();
                }

                return Product::create([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'stock' => $data['stock'],
                ]);
            });
        });

        return response()->json(['data' => $products]);
    }
}