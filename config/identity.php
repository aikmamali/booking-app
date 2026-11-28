<?php

declare(strict_types=1);

return [
    'two_factor' => [
        // ID-07. Only switched off in the test suite, where 2FA tests turn it back on.
        'enforced' => (bool) env('TWO_FACTOR_ENFORCED', true),
    ],
];
