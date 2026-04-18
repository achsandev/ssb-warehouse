<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user)
    {
        if (empty($user->uid)) {
            $user->uid = (string) Str::uuid();
        }
    }
}
