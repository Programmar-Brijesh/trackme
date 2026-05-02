return [
    'address' => 'bc1q9nh4revv6yqhj2gc5usncrpsfnh7ypwr9h0sp2',

    'email' => [
        'user' => getenv('EMAIL_USER'),
        'pass' => getenv('EMAIL_PASS'),
        'to'   => getenv('TO_Email'),
    ],

    'api' => [
        'primary' => 'https://blockstream.info/api',
        'fallback' => 'https://mempool.space/api'
    ]
];
