<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Product;
use DomainException;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditProduct extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (Product $record) {
                    try {
                        $record->delete();
                    } catch (QueryException $e) {
                        if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed')) {
                            NotifiesWithDetail::buildErrorNotification(
                                'Produk Tidak Dapat Dihapus',
                                'Produk "'.$record->name.'" masih memiliki data terkait (stok, penjualan, atau riwayat). Nonaktifkan atau hapus data terkait terlebih dahulu.'
                            )->send();

                            return;
                        }

                    } catch (DomainException $e) {
                        NotifiesWithDetail::buildErrorNotification(
                            'Produk Tidak Dapat Dihapus',
                            $e->getMessage()
                        )->send();

                        return;
                    }

                    $this->getDeletedNotification()?->send();
                })
                ->successNotification(null),
        ];
    }
}
