<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Product;
use App\Models\Stock;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateStockOpname extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockOpnameResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['opname_number'] = 'SO-'.strtoupper(Str::random(8));
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';

        return $data;
    }

    public function updatedData($name): void
    {
        if ($name === 'location_id') {
            $locationId = $this->data['location_id'] ?? null;

            if ($locationId) {
                // Get all products and their current stocks at this location
                $products = Product::all();

                $items = [];
                foreach ($products as $product) {
                    $stock = Stock::where('product_id', $product->id)
                        ->where('location_id', $locationId)
                        ->first();

                    $systemQty = $stock ? $stock->qty : 0;

                    $items[] = [
                        'product_id' => $product->id,
                        'system_qty' => $systemQty,
                        'actual_qty' => $systemQty, // default to system qty
                        'difference' => 0,
                        'notes' => null,
                    ];
                }

                $this->data['items'] = $items;
            } else {
                $this->data['items'] = [];
            }
        }
    }
}
