<?php

arch('no debugging helpers are left in the code')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('enums live in App\Enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('actions are final, single-purpose classes')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal();

arch('temporary demo data stays out of domain code so it is easy to remove in Phases 4–6')
    ->expect('App\Support\Demo')
    ->toOnlyBeUsedIn([
        'App\Support\Demo',
        'App\Livewire',
        'App\Http\Controllers\Dev',
        'App\Http\Controllers\Auth',
        'App\Http\Middleware\RequireDemoCustomer',
        'App\Http\Middleware\RequireDemoDeliveryBoy',
    ]);

arch('models are only extended from Eloquent')
    ->expect('App\Models')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Model');
