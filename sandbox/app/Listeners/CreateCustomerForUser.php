<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Lunar\Models\Customer;

class CreateCustomerForUser
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Split name into first/last
        $nameParts = explode(' ', $user->name, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Create customer
        $customer = Customer::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        // Link to user via pivot table
        $user->customers()->attach($customer->id);
    }
}
