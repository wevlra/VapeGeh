<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Invoice;

class InvoiceService
{
    public function generate(StockMovement $stockMovement): Invoice
    {
        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related', 'related.items.product']);

        $location = $stockMovement->location;
        $related = $stockMovement->related;
        $isSale = $related instanceof Sale;
        $isStockEntry = $related instanceof StockEntry;
        $isStockTransfer = $related instanceof StockTransfer;

        if ($isSale) {
            $related->loadMissing(['items.product']);
        }

        $sellerData = [
            'name' => config('store.name'),
            'address' => $location->address ?? config('store.address'),
            'phone' => config('store.phone'),
        ];

        $buyerData = ['name' => 'Walk-in Customer'];

        if ($isStockEntry) {
            $related->loadMissing(['vendor', 'buyer']);
            if ($related->type === 'in') {
                // Stock In: From Vendor, To Location
                if ($related->vendor) {
                    $sellerData['name'] = $related->vendor->name;
                    $sellerData['phone'] = $related->vendor->phone ?? '';
                    $sellerData['address'] = $related->vendor->address ?? '';
                }

                $buyerData['name'] = config('store.name').' - '.$location->name;
                $buyerData['address'] = $location->address ?? config('store.address');
            } else {
                // Stock Out: From Location, To Buyer
                $sellerData['name'] = config('store.name').' - '.$location->name;
                $sellerData['address'] = $location->address ?? config('store.address');

                if ($related->buyer) {
                    $buyerData['name'] = $related->buyer->name;
                    $buyerData['phone'] = $related->buyer->phone ?? '';
                    $buyerData['address'] = $related->buyer->address ?? '';
                }
            }
        } elseif ($isStockTransfer) {
            $related->loadMissing(['fromLocation', 'toLocation']);

            $sellerData['name'] = config('store.name').' - '.($related->fromLocation->name ?? '');
            $sellerData['address'] = $related->fromLocation->address ?? config('store.address');

            $buyerData['name'] = config('store.name').' - '.($related->toLocation->name ?? '');
            $buyerData['address'] = $related->toLocation->address ?? config('store.address');
        } else {
            // Default Sales/Other logic
            if ($isSale && $related->buyer) {
                $buyerData['name'] = $related->buyer->name;
                if ($related->buyer->phone) {
                    $buyerData['phone'] = $related->buyer->phone;
                }
                if ($related->buyer->email) {
                    $buyerData['address'] = $related->buyer->email;
                }
            } elseif ($stockMovement->buyer) {
                $buyerData['name'] = $stockMovement->buyer->name;
                if ($stockMovement->buyer->phone) {
                    $buyerData['phone'] = $stockMovement->buyer->phone;
                }
                if ($stockMovement->buyer->email) {
                    $buyerData['address'] = $stockMovement->buyer->email;
                }
            } elseif ($isSale && $related->user) {
                $buyerData['name'] = $related->user->name;
            }
        }

        $seller = new Party($sellerData);

        $paymentMethod = match (true) {
            $isSale => match ($related->payment_method) {
                'cash' => 'Cash',
                'transfer' => 'Bank Transfer',
                'qris' => 'QRIS',
                default => ucfirst($related->payment_method ?? '-'),
            },
            $isStockEntry && $related->payment_method => match ($related->payment_method) {
                'cash' => 'Cash',
                'transfer' => 'Bank Transfer',
                'qris' => 'QRIS',
                default => ucfirst($related->payment_method),
            },
            default => '-',
        };

        $buyerData['custom_fields'] = [
            'Metode Pembayaran' => $paymentMethod,
        ];

        $buyer = new Party($buyerData);

        $items = [];
        if ($isSale) {
            foreach ($related->items as $item) {
                $items[] = InvoiceItem::make($item->product->name ?? 'Product')
                    ->pricePerUnit((float) $item->price)
                    ->quantity((int) $item->qty);
            }
        } elseif ($isStockEntry) {
            foreach ($related->items as $item) {
                $items[] = InvoiceItem::make($item->product->name ?? 'Product')
                    ->pricePerUnit((float) $item->unit_price)
                    ->quantity((int) $item->qty);
            }

            if ($related->additional_costs) {
                foreach ($related->additional_costs as $cost) {
                    $items[] = InvoiceItem::make($cost['description'] ?? 'Biaya Tambahan')
                        ->pricePerUnit((float) ($cost['amount'] ?? 0))
                        ->quantity(1);
                }
            }
        } elseif ($isStockTransfer) {
            foreach ($related->items as $item) {
                $items[] = InvoiceItem::make($item->product->name ?? 'Product')
                    ->pricePerUnit((float) $item->price)
                    ->quantity((int) $item->qty);
            }
        } else {
            $items[] = InvoiceItem::make($stockMovement->product->name ?? 'Product')
                ->pricePerUnit((float) ($stockMovement->unit_price ?? 0))
                ->quantity(abs((int) $stockMovement->quantity));
        }

        $serialNumber = $isSale ? $related->invoice_number : ($isStockEntry ? '#SM-'.$stockMovement->id : '#SM-'.$stockMovement->id);

        $invoice = Invoice::make('invoice')
            ->serialNumberFormat('{SERIES}{SEQUENCE}')
            ->seller($seller)
            ->buyer($buyer)
            ->date($stockMovement->created_at)
            ->dateFormat('d M Y')
            ->currencySymbol('Rp')
            ->currencyCode('IDR')
            ->currencyFormat('{SYMBOL} {VALUE}')
            ->currencyThousandsSeparator('.')
            ->currencyDecimalPoint(',')
            ->currencyDecimals(0)
            ->filename($serialNumber)
            ->addItems($items)
            ->logo(public_path('assets/images/logo-stacked-dark-tr.png'))
            ->payUntilDays(0)
            ->notes($stockMovement->notes ?? '');

        if ($isSale) {
            $total = (float) $related->total;
            $invoice = $invoice->status($related->paid_amount >= $total ? 'PAID' : 'UNPAID');
        }

        return $invoice;
    }
}
