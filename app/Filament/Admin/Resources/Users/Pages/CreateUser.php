<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = UserResource::class;
}
