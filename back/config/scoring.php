<?php

declare(strict_types=1);

/**
 * Point constants for match results.
 * Change values here to adjust league scoring without touching business logic.
 */
return [
    // Same division (red vs red, yellow vs yellow)
    'same_division' => [
        '3-0' => ['winner' => 6.0, 'loser' => 1.0],
        '3-1' => ['winner' => 5.0, 'loser' => 2.0],
        '3-2' => ['winner' => 4.0, 'loser' => 3.0],
    ],

    // Red (higher) beats yellow (lower)
    'higher_vs_lower' => [
        '3-0' => ['winner' => 4.5, 'loser' => 2.5],
        '3-1' => ['winner' => 4.0, 'loser' => 3.0],
        '3-2' => ['winner' => 3.5, 'loser' => 2.5],
    ],

    // Yellow (lower) beats red (higher)
    'lower_vs_higher' => [
        '3-0' => ['winner' => 6.5, 'loser' => 0.5],
        '3-1' => ['winner' => 6.0, 'loser' => 1.0],
        '3-2' => ['winner' => 5.5, 'loser' => 2.5],
    ],

    'max_matches_same_division' => 2,
    'max_matches_cross_division' => 1,

    'allowed_scores' => ['3-0', '3-1', '3-2'],
    'higher_division' => 'red',
    'lower_division' => 'yellow',
];
