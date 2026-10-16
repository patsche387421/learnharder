<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $student = $data['kind'] === 'student';

        $user = new User;
        $user->forceFill([
            'name' => $student ? null : $data['name'],
            'email' => $data['email'],
            'gamer_tag' => $data['gamer_tag'] ?? null,
            // Ein Passwort, das niemand kennt. Die Person setzt ihr eigenes über „Passwort vergessen“.
            'password' => Str::random(64),
        ]);

        if ($student) {
            $user->forceFill(['parental_consent_at' => now(), 'parental_consent_by' => UserResource::actor()->id]);
        }

        $user->save();
        $user->syncRoles($student ? ['student'] : $data['roles']);

        return $user;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Konto angelegt')
            ->body('Das Passwort setzt die Person selbst: in der App über „Passwort vergessen“ mit ihrer E-Mail.')
            ->success();
    }
}
