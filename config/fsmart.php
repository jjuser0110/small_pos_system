<?php

return [
    /*
    | Fixed staff shifts. A shift whose end is earlier than its start runs past
    | midnight (PM: 22:00 today to 10:00 tomorrow) and belongs to the day it starts.
    */
    'shifts' => [
        'AM' => ['start' => '10:00', 'end' => '22:00'],
        'PM' => ['start' => '22:00', 'end' => '10:00'],
    ],

    /*
    | Staff may log in this many minutes before their shift starts and are logged
    | out automatically this many minutes after it ends.
    */
    'grace_minutes' => 15,
];
