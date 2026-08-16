<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',

    'middleware' => ['web'],

    'views' => false,

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    'features' => [
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],
];
