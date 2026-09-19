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

arch('temporary demo data stays out of domain code so it is easy to remove in Phases 8–10')
    ->expect('App\Support\Demo')
    ->toOnlyBeUsedIn([
        'App\Support\Demo',
        'App\Livewire',
        'App\Http\Controllers\Dev',
        // Signing out empties the bag, which still lives in the session until
        // the cart moves onto the database (Phase 7).
        'App\Http\Controllers\Auth',
        // Resolves the admin's homepage picks against the sample catalogue until
        // the shop reads products from the database (Phase 5).
        'App\Support\Home\HomeContent',
        // The seeders turn the prototype's sample content into database rows;
        // both disappear together once the shop reads from the database.
        'Database\Seeders',
    ]);

arch('models are only extended from Eloquent')
    ->expect('App\Models')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Model');
