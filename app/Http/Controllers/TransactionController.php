<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // সব transaction দেখাবে
    public function index()
    {
        $transactions = Transaction::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    // নতুন Payment In / Payment Out save করবে
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'party_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $transaction = Transaction::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction,
        ], 201);
    }

    // একটি transaction দেখাবে
    public function show(string $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    // transaction update করবে
    public function update(Request $request, string $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if ($transaction->sale_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Sale payments must be managed through the sale payment endpoint.',
            ], 422);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:in,out',
            'party_name' => 'nullable|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|string|max:50',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'transaction_date' => 'sometimes|date',
        ]);

        $transaction->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $transaction->fresh(),
        ]);
    }

    // transaction delete করবে
    public function destroy(string $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if ($transaction->sale_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Sale payments must be managed through the sale payment endpoint.',
            ], 422);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }

    // Dashboard-এর হিসাব
    public function summary()
    {
        $totalIn = Transaction::where('type', 'in')->sum('amount');

        $totalOut = Transaction::where('type', 'out')->sum('amount');

        $balance = $totalIn - $totalOut;

        return response()->json([
            'success' => true,
            'data' => [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'balance' => $balance,
            ],
        ]);
    }
}