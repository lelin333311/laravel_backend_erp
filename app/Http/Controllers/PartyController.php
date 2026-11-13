<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartyController extends Controller {
    public function index(Request $r) {
        $q = Party::query();
        if ($s = $r->query('search')) {
            $q->where(fn($x) => $x->where('name','like',"%$s%")
                ->orWhere('party_code','like',"%$s%")
                ->orWhere('phone','like',"%$s%"));
        }
        if (($t = $r->query('type')) && $t !== 'all') {
            $q->where(fn($x) => $x->where('type',$t)->orWhere('type','both'));
        }
        return response()->json(['data' => $q->orderByDesc('id')->get()]);
    }

    public function show(Party $party) {
        return response()->json(['data' => $party]);
    }

    public function store(Request $r) {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'party_code' => 'nullable|string',
            'type' => 'required|in:customer,supplier,both',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'opening_balance' => 'numeric',
            'balance_type' => 'in:debit,credit',
            'is_active' => 'boolean',
        ]);
        $party = Party::create($data);
        return response()->json(['data' => $party], 201);
    }

    public function update(Request $r, Party $party) {
        $party->update($r->all());
        return response()->json(['data' => $party->fresh()]);
    }

    public function destroy(Party $party) {
        $party->delete();
        return response()->json(['ok' => true]);
    }

    public function sync(Request $request) {
        $validated = $request->validate([
            'parties' => 'required|array|min:1',
            'parties.*.party_code' => 'required|string|max:30',
            'parties.*.name' => 'required|string|max:255',
            'parties.*.type' => 'required|in:customer,supplier,both',
            'parties.*.phone' => 'nullable|string|max:30',
            'parties.*.email' => 'nullable|email|max:255',
            'parties.*.address' => 'nullable|string',
            'parties.*.opening_balance' => 'nullable|numeric',
            'parties.*.balance_type' => 'nullable|in:debit,credit',
            'parties.*.is_active' => 'nullable|boolean',
        ]);

        $parties = DB::transaction(function () use ($validated) {
            return collect($validated['parties'])->map(function (array $data) {
                return Party::updateOrCreate(
                    ['party_code' => $data['party_code']],
                    [
                        'name' => $data['name'],
                        'type' => $data['type'],
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'address' => $data['address'] ?? null,
                        'opening_balance' => $data['opening_balance'] ?? 0,
                        'balance_type' => $data['balance_type'] ?? 'debit',
                        'is_active' => $data['is_active'] ?? true,
                    ],
                );
            });
        });

        return response()->json(['data' => $parties]);
    }
}