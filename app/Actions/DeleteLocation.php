<?php

namespace App\Actions;

use App\Models\Location;
use App\Models\Stock;
use App\Models\StockMovement;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;

class DeleteLocation
{
    public static function make(): Action
    {
        return Action::make('deleteLocation')
            ->label('Delete')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('Delete Location')
            ->modalDescription(fn (Location $record): string => "All stock at \"{$record->name}\" must be moved before deletion.")
            ->modalSubmitActionLabel('Continue')
            ->form([
                Select::make('destination_id')
                    ->label('Move stock to')
                    ->helperText('Choose where to transfer existing stock from this location.')
                    ->options(fn (Location $record) => Location::query()
                        ->where('id', '!=', $record->id)
                        ->where('status', 'active')
                        ->orderBy('type')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Location $loc) => [
                            $loc->id => ($loc->type === 'warehouse' ? '🏭 ' : '🏪 ').$loc->name,
                        ])
                        ->toArray())
                    ->searchable()
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data, Location $record): void {
                $destination = Location::findOrFail($data['destination_id']);

                if ($destination->id === $record->id) {
                    throw new DomainException('Destination must be different from the deleted location.');
                }

                $hasUsers = $record->users()->exists();
                if ($hasUsers) {
                    throw new DomainException("Cannot delete \"{$record->name}\" — staff users are still assigned to this location.");
                }

                DB::transaction(function () use ($record, $destination) {
                    $stocks = $record->stocks()->where('qty', '>', 0)->get();

                    foreach ($stocks as $stock) {
                        $destStock = Stock::where('product_id', $stock->product_id)
                            ->where('location_id', $destination->id)
                            ->lockForUpdate()
                            ->first();

                        if ($destStock) {
                            $destStock->increment('qty', $stock->qty);
                        } else {
                            Stock::create([
                                'product_id' => $stock->product_id,
                                'location_id' => $destination->id,
                                'qty' => $stock->qty,
                            ]);
                        }

                        StockMovement::create([
                            'product_id' => $stock->product_id,
                            'location_id' => $record->id,
                            'type' => 'transfer_out',
                            'quantity' => -$stock->qty,
                            'related_type' => Location::class,
                            'related_id' => $destination->id,
                            'notes' => "Transfer to {$destination->name} (location deletion)",
                            'created_by' => auth()->id(),
                        ]);

                        StockMovement::create([
                            'product_id' => $stock->product_id,
                            'location_id' => $destination->id,
                            'type' => 'transfer_in',
                            'quantity' => $stock->qty,
                            'related_type' => Location::class,
                            'related_id' => $record->id,
                            'notes' => "Transfer from {$record->name} (location deletion)",
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $record->stocks()->delete();

                    $record->delete();
                });
            });
    }

    /**
     * Move stock to warehouse explicitly (called from page if no choice needed).
     */
    public static function execute(Location $location, ?Location $destination = null): void
    {
        $destination ??= Location::where('type', 'warehouse')->first();

        if (! $destination) {
            throw new DomainException('No warehouse location configured.');
        }

        if ($destination->id === $location->id) {
            throw new DomainException('Destination cannot be the same as the deleted location.');
        }

        DB::transaction(function () use ($location, $destination) {
            foreach ($location->stocks()->where('qty', '>', 0)->get() as $stock) {
                $destStock = Stock::where('product_id', $stock->product_id)
                    ->where('location_id', $destination->id)
                    ->lockForUpdate()
                    ->first();

                if ($destStock) {
                    $destStock->increment('qty', $stock->qty);
                } else {
                    Stock::create([
                        'product_id' => $stock->product_id,
                        'location_id' => $destination->id,
                        'qty' => $stock->qty,
                    ]);
                }

                StockMovement::create([
                    'product_id' => $stock->product_id,
                    'location_id' => $location->id,
                    'type' => 'transfer_out',
                    'quantity' => -$stock->qty,
                    'related_type' => Location::class,
                    'related_id' => $destination->id,
                    'notes' => "Auto-moved to {$destination->name} (location deleted)",
                    'created_by' => auth()->id(),
                ]);

                StockMovement::create([
                    'product_id' => $stock->product_id,
                    'location_id' => $destination->id,
                    'type' => 'transfer_in',
                    'quantity' => $stock->qty,
                    'related_type' => Location::class,
                    'related_id' => $location->id,
                    'notes' => "Auto-received from {$location->name} (location deleted)",
                    'created_by' => auth()->id(),
                ]);
            }

            $location->stocks()->delete();
            $location->delete();
        });
    }
}
