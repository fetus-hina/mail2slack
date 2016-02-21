<?php
return [
    'token' => 'xoxp-00000000000-11111111111-22222222222-3333333333',
    'postMessage' => [
        'channel'       => '#email',
        'username'      => 'Received Email',
        'icon_emoji'    => ':envelope_with_arrow:',
    ],

    // return false to skip notify to slack
    // function (PhpMimeMailParser\Parser $parser): bool { return false; }
    'filter' => null,
];
