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

arch('models are only extended from Eloquent')
    ->expect('App\Models')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Model');
