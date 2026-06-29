<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockEntry extends Model
{
    protected $fillable = [
        'type',
        'location_id',
        'vendor_id',
        'buyer_id',
        'notes',
        'additional_costs',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StockEntryItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'additional_costs' => 'array',
        ];
    }
}
