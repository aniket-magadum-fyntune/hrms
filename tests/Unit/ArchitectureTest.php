<?php

arch('app classes follow psr-4 casing')
    ->expect('App')
    ->toBeCasedCorrectly();

arch('debugging helpers are not used')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('models extend eloquent models')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('concerns are traits')
    ->expect('App\Concerns')
    ->toBeTraits();
