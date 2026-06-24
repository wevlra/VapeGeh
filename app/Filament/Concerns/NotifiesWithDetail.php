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
        return $this->resourceLabel().' created successfully';
    }

    protected function getCreatedNotificationBody(): ?string
    {
        return null;
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
        return $this->resourceLabel().' updated successfully';
    }

    protected function getSavedNotificationBody(): ?string
    {
        $record = $this->getRecord();

        if (! $record instanceof Model) {
            return null;
        }

        return (string) ($record->getAttribute('name')
            ?? $record->getAttribute('sku')
            ?? $record->getKey());
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
        return $this->resourceLabel().' deleted successfully';
    }

    protected function getDeletedNotificationBody(): ?string
    {
        return null;
    }

    protected function getDeletedNotification(): ?Notification
    {
        return Notification::make()
            ->title($this->getDeletedNotificationTitle())
            ->body($this->getDeletedNotificationBody())
            ->danger();
    }
}
