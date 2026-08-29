<?php

namespace App\Filament\Actions;

use App\Models\Location;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class PrintReportAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cetak Laporan')
            ->icon('heroicon-o-printer')
            ->color('primary')
            ->visible(fn (StockMovement $record): bool => in_array($record->type, ['in', 'transfer', 'opname']))
            ->action(function () {})
            ->mountUsing(function (StockMovement $record) {
                $related = $record->related;
                $timestamp = date('Y-m-d-H-i');

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
                    $record->load(['product', 'location', 'creator']);
                    $typeLabel = match ($record->type) {
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'transfer' => 'Transfer',
                        'opname' => 'Penyesuaian Stok',
                        default => 'Riwayat Stok',
                    };
                    $title = 'Laporan '.$typeLabel;
                    $view = 'exports.pdf.history';
                    $siblings = $record->sibling_movements->load(['product', 'location', 'creator', 'related']);
                    if ($siblings->isEmpty()) {
                        $siblings = collect([$record]);
                    }
                    $data = ['records' => $siblings, 'title' => $title, 'filterInfo' => ''];
                    $fileName = "report-{$record->type}-{$record->id}-{$timestamp}.pdf";
                }

                $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');

                Storage::disk('public')->put("reports/{$fileName}", $pdf->output());

                $url = asset("storage/reports/{$fileName}");

                session()->put("report_url_{$record->id}", $url);
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
            ]));
    }
}
