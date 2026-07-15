<?php

namespace App\Filament\Staff\Resources\History\Pages;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Staff\Resources\History\HistoryResource;
use App\Models\Location;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewHistory extends ViewRecord
{
    protected static string $resource = HistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PrintReceiptAction::make('print_receipt'),
            PrintInvoiceAction::make('print_invoice'),
            Action::make('print_report')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->action(function () {})
                ->mountUsing(function (StockMovement $record) {
                    $related = $record->related;
                    $timestamp = date('Y-m-d-H-i');
                    $sessionKey = 'report_url_'.$record->id;

                    session()->forget($sessionKey);

                    if ($related instanceof StockOpname) {
                        $related->load(['items.product', 'location', 'creator']);
                        $title = 'Laporan Penyesuaian Stok - '.$related->opname_number;
                        $view = 'exports.pdf.stock-opname';
                        $data = ['opname' => $related, 'title' => $title, 'filterInfo' => ''];
                        $fileName = "stock-opname-{$related->opname_number}-{$timestamp}.pdf";
                    } elseif ($related instanceof StockEntry) {
                        $related->load(['items.product', 'location', 'creator', 'vendor']);
                        $title = 'Laporan Stok Masuk';
                        $view = 'exports.pdf.stock-entry';
                        $data = ['entry' => $related, 'title' => $title, 'filterInfo' => ''];
                        $fileName = "stock-entry-{$related->id}-{$timestamp}.pdf";
                    } elseif ($related instanceof StockTransfer) {
                        $related->load(['items.product', 'fromLocation', 'toLocation', 'creator']);
                        $title = 'Laporan Transfer Stok - '.$related->transfer_number;
                        $view = 'exports.pdf.stock-transfer-detail';
                        $data = ['transfer' => $related, 'title' => $title, 'filterInfo' => ''];
                        $fileName = "stock-transfer-{$related->transfer_number}-{$timestamp}.pdf";
                    } elseif ($related === null && $record->related_type === Location::class) {
                        $siblings = $record->sibling_movements->load('product');
                        $destLocation = $record->location?->name ?? '-';
                        $firstNote = $siblings->first()?->notes ?? '';
                        $sourceLocation = '';
                        if (preg_match('/^Transfer from (.+?) \(location deletion\)$/', $firstNote, $m)) {
                            $sourceLocation = $m[1];
                        }
                        $title = 'Laporan Pemindahan Stok (Hapus Lokasi)';
                        $view = 'exports.pdf.history';
                        $data = ['records' => $siblings, 'title' => $title, 'filterInfo' => ($sourceLocation ? 'Dari: '.$sourceLocation.' | ' : '').'Ke: '.$destLocation];
                        $fileName = "location-deletion-{$record->id}-{$timestamp}.pdf";
                    } else {
                        return;
                    }

                    $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');

                    Storage::disk('public')->put("reports/{$fileName}", $pdf->output());

                    $url = asset("storage/reports/{$fileName}");

                    session()->put($sessionKey, $url);
                    session()->put("report_filename_{$record->id}", $fileName);
                })
                ->modalHeading(false)
                ->modalWidth('sm')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(fn (StockMovement $record) => view('exports.pdf.modal-preview', [
                    'url' => session()->get("report_url_{$record->id}"),
                    'filename' => session()->get("report_filename_{$record->id}"),
                    'title' => 'Cetak Laporan',
                ]))
                ->visible(fn (StockMovement $record): bool => in_array($record->type, ['in', 'transfer', 'opname'])),
        ];
    }
}
