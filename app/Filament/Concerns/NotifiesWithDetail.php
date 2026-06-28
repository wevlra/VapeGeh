<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait NotifiesWithDetail
{
    protected function resourceLabel(): string
    {
        $model = $this->getResource()::getModel();
        $basename = class_basename($model);

        return Str::headline($basename);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->resourceLabel().' dibuat';
    }

    protected function getCreatedNotificationBody(): ?string
    {
        return $this->getRecordIdentifier().' berhasil dibuat.';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title($this->getCreatedNotificationTitle())
            ->body($this->getCreatedNotificationBody())
            ->success();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->resourceLabel().' diperbarui';
    }

    protected function getSavedNotificationBody(): ?string
    {
        return $this->getRecordIdentifier().' berhasil diperbarui.';
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title($this->getSavedNotificationTitle())
            ->body($this->getSavedNotificationBody())
            ->success();
    }

    protected function getDeletedNotificationTitle(): ?string
    {
        return $this->resourceLabel().' dihapus';
    }

    protected function getDeletedNotificationBody(): ?string
    {
        return $this->getRecordIdentifier().' telah dihapus permanen.';
    }

    protected function getDeletedNotification(): ?Notification
    {
        return Notification::make()
            ->title($this->getDeletedNotificationTitle())
            ->body($this->getDeletedNotificationBody())
            ->danger();
    }

    protected function getRecordIdentifier(): string
    {
        $record = $this->record ?? null;

        if (! $record instanceof Model) {
            return 'Catatan ini';
        }

        $label = (string) ($record->getAttribute('name')
            ?? $record->getAttribute('title')
            ?? $record->getAttribute('invoice_number')
            ?? $record->getAttribute('transfer_number')
            ?? $record->getAttribute('sku')
            ?? $record->getKey());

        return Str::headline(class_basename($record)).' "'.$label.'"';
    }
}
