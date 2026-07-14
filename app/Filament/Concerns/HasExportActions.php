<?php

namespace App\Filament\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

trait HasExportActions
{
    public ?string $generatedPdfUrl = null;

    public ?string $generatedPdfFilename = null;

    public ?string $generatedPdfTitle = null;

    protected function getExportActionGroup(string $exportClass, string $pdfView, string $fileNamePrefix): ActionGroup
    {
        $downloadFileName = $fileNamePrefix.'-'.date('Y-m-d-H-i');

        return ActionGroup::make([
            Action::make('export_pdf')
                ->label('PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action(function () {
                    // Handled by mountUsing and modal
                })
                ->mountUsing(function () use ($pdfView, $fileNamePrefix, $exportClass, $downloadFileName) {
                    $query = method_exists($this, 'buildExportQuery')
                        ? $this->buildExportQuery()
                        : $this->getFilteredTableQuery();

                    if (method_exists($exportClass, 'getEagerLoads')) {
                        $query->with($exportClass::getEagerLoads());
                    }

                    $title = method_exists($this, 'getExportTitle')
                        ? $this->getExportTitle()
                        : str_replace('-', ' ', Str::title($fileNamePrefix));

                    $filterInfo = method_exists($this, 'getFilterInfo')
                        ? $this->getFilterInfo()
                        : '';

                    $records = $query->get();

                    $pdf = Pdf::loadView($pdfView, [
                        'records' => $records,
                        'title' => $title,
                        'filterInfo' => $filterInfo,
                    ])->setPaper('a4', 'portrait');

                    $fileName = "{$downloadFileName}.pdf";
                    Storage::disk('public')->put("reports/{$fileName}", $pdf->output());

                    $this->generatedPdfUrl = asset("storage/reports/{$fileName}");
                    $this->generatedPdfFilename = $fileName;
                    $this->generatedPdfTitle = $title;
                })
                ->modalHeading(false)
                ->modalWidth('sm')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(fn () => view('exports.pdf.modal-preview', [
                    'url' => $this->generatedPdfUrl,
                    'filename' => $this->generatedPdfFilename,
                    'title' => $this->generatedPdfTitle,
                ])),

            Action::make('export_excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () use ($exportClass, $downloadFileName) {
                    $query = method_exists($this, 'buildExportQuery')
                        ? $this->buildExportQuery()
                        : $this->getFilteredTableQuery();

                    if (method_exists($exportClass, 'getEagerLoads')) {
                        $query->with($exportClass::getEagerLoads());
                    }

                    $export = method_exists($this, 'getExportInstance')
                        ? $this->getExportInstance($exportClass, $query)
                        : new $exportClass($query);

                    return Excel::download($export, "{$downloadFileName}.xlsx");
                }),

            Action::make('export_csv')
                ->label('CSV')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->action(function () use ($exportClass, $downloadFileName) {
                    $query = method_exists($this, 'buildExportQuery')
                        ? $this->buildExportQuery()
                        : $this->getFilteredTableQuery();

                    if (method_exists($exportClass, 'getEagerLoads')) {
                        $query->with($exportClass::getEagerLoads());
                    }

                    $export = method_exists($this, 'getExportInstance')
                        ? $this->getExportInstance($exportClass, $query)
                        : new $exportClass($query);

                    return Excel::download($export, "{$downloadFileName}.csv", \Maatwebsite\Excel\Excel::CSV);
                }),
        ])
            ->label('Ekspor')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('primary')
            ->button();
    }
}
