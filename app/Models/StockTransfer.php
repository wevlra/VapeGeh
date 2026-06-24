<?php

namespace App\Models;

use Database\Factories\StockTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['transfer_number', 'from_location_id', 'to_location_id', 'status', 'notes', 'completed_at', 'created_by'])]
class StockTransfer extends Model
{
    /** @use HasFactory<StockTransferFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (StockTransfer $transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = static::generateTransferNumber();
            }
        });
    }

    public static function generateTransferNumber(): string
    {
        $prefix = 'TRF-'.now()->format('ymd').'-';
        $lastTransfer = static::where('transfer_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastTransfer) {
            $lastSequence = (int) substr($lastTransfer->transfer_number, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<StockTransferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
