<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['location_id', 'category', 'description', 'amount', 'date'])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (is_null($expense->created_by)) {
                $expense->created_by = auth()->id();
            }
        });
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'date' => 'date',
        ];
    }

    public function isManual(): bool
    {
        if (blank($this->description)) {
            return true;
        }

        $desc = $this->description;

        if (
            str_contains($desc, 'Stok Masuk') ||
            str_contains($desc, 'Stock Opname') ||
            str_contains($desc, '(Entry #')
        ) {
            return false;
        }

        return true;
    }
}
