<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait NotifiesWithDetail
{
    /**
     * Membangun objek notifikasi sukses.
     */
    public static function buildSuccessNotification(string $title, ?string $description = null): Notification
    {
        $notification = Notification::make()->title($title)->success();
        if ($description) {
            $notification->body($description);
        }

        return $notification;
    }

    /**
     * Membangun objek notifikasi error/danger.
     */
    public static function buildErrorNotification(string $title, ?string $description = null): Notification
    {
        $notification = Notification::make()->title($title)->danger();
        if ($description) {
            $notification->body($description);
        }

        return $notification;
    }

    /**
     * Membangun objek notifikasi warning.
     */
    public static function buildWarningNotification(string $title, ?string $description = null): Notification
    {
        $notification = Notification::make()->title($title)->warning();
        if ($description) {
            $notification->body($description);
        }

        return $notification;
    }

    /**
     * Membangun objek notifikasi info.
     */
    public static function buildInfoNotification(string $title, ?string $description = null): Notification
    {
        $notification = Notification::make()->title($title)->info();
        if ($description) {
            $notification->body($description);
        }

        return $notification;
    }

    /**
     * Mengirim notifikasi sukses secara instan.
     */
    protected function notifySuccess(string $title, ?string $description = null): void
    {
        static::buildSuccessNotification($title, $description)->send();
    }

    /**
     * Mengirim notifikasi error secara instan.
     */
    protected function notifyError(string $title, ?string $description = null): void
    {
        static::buildErrorNotification($title, $description)->send();
    }

    /**
     * Mengirim notifikasi warning secara instan.
     */
    protected function notifyWarning(string $title, ?string $description = null): void
    {
        static::buildWarningNotification($title, $description)->send();
    }

    /**
     * Mengirim notifikasi info secara instan.
     */
    protected function notifyInfo(string $title, ?string $description = null): void
    {
        static::buildInfoNotification($title, $description)->send();
    }

    protected function resourceLabel(): string
    {
        $model = $this->getResource()::getModel();
        $basename = class_basename($model);

        return Str::headline($basename);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->resourceLabel().' Berhasil Ditambahkan';
    }

    protected function getCreatedNotificationBody(): ?string
    {
        return 'Data '.$this->getRecordIdentifier().' baru telah berhasil direkam ke dalam sistem.';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return static::buildSuccessNotification(
            $this->getCreatedNotificationTitle(),
            $this->getCreatedNotificationBody()
        );
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan Berhasil Disimpan';
    }

    protected function getSavedNotificationBody(): ?string
    {
        return 'Data '.$this->getRecordIdentifier().' telah berhasil diperbarui di sistem.';
    }

    protected function getSavedNotification(): ?Notification
    {
        return static::buildSuccessNotification(
            $this->getSavedNotificationTitle(),
            $this->getSavedNotificationBody()
        );
    }

    protected function getDeletedNotificationTitle(): ?string
    {
        return 'Data Berhasil Dihapus';
    }

    protected function getDeletedNotificationBody(): ?string
    {
        return 'Data '.$this->getRecordIdentifier().' telah berhasil dihapus dari sistem.';
    }

    protected function getDeletedNotification(): ?Notification
    {
        return static::buildErrorNotification(
            $this->getDeletedNotificationTitle(),
            $this->getDeletedNotificationBody()
        );
    }

    protected function getRecordIdentifier(): string
    {
        $record = $this->record ?? null;

        if (! $record instanceof Model) {
            return 'catatan ini';
        }

        $label = (string) ($record->getAttribute('name')
            ?? $record->getAttribute('title')
            ?? $record->getAttribute('invoice_number')
            ?? $record->getAttribute('opname_number')
            ?? $record->getAttribute('transfer_number')
            ?? $record->getKey());

        return Str::headline(class_basename($record)).' "'.$label.'"';
    }
}
