<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;

class MakeFirstUserSuperadmin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $superadmin_users = User::role('superadmin')->get();
        if ( count( $superadmin_users ) === 0 ) {
            $event->user->assignRole('superadmin');
            $event->user->approved = true;
            $event->user->save();
        }
    }
}
