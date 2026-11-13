<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'sale_number',
        'party_id',
        'customer_name',
        'total_amount',
        'paid_amount',
        'sale_date',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'sale_date' => 'date',
    ];

    protected $appends = ['due_amount', 'payment_status'];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getDueAmountAttribute(): string
    {
        $dueCents = $this->amountToCents($this->total_amount)
            - $this->amountToCents($this->paid_amount);

        return intdiv($dueCents, 100).'.'.str_pad(
            (string) ($dueCents % 100),
            2,
            '0',
            STR_PAD_LEFT,
        );
    }

    public function getPaymentStatusAttribute(): string
    {
        return $this->amountToCents($this->total_amount)
            <= $this->amountToCents($this->paid_amount)
            ? 'paid'
            : 'due';
    }

    private function amountToCents(string|int|float $amount): int
    {
        $formatted = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $formatted);

        return ((int) $whole * 100) + (int) $fraction;
    }
}
