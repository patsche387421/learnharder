<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exceptions\AccountRuleViolation;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Support\Accounts;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getRecord();

        return [...$data, 'roles' => $user->getRoleNames()->all()];
    }

    /** Rollen nur über Accounts::syncRoles, damit niemand sich selbst die Admin-Rolle nimmt (E-027). */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $roles = $data['roles'];
        unset($data['roles']);

        try {
            DB::transaction(function () use ($record, $data, $roles) {
                Accounts::syncRoles($record, $roles, UserResource::actor());
                $record->forceFill($data)->save();
            });
        } catch (AccountRuleViolation $e) {
            Notification::make()->title('Nicht gespeichert')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return $record;
    }
}
