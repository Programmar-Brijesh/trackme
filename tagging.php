<?php

/**
 * Simple local tagging DB
 * (expand kar sakta hai future me)
 */
$KNOWN_TAGS = [
    // sample known prefixes / addresses
    'bc1q' => 'unknown',
];

/**
 * Known exchange addresses (example)
 * NOTE: real me tu CSV/DB use karega
 */
$EXCHANGE_ADDRESSES = [
    'binance' => [
        'bc1q...sample1',
        'bc1q...sample2'
    ],
    'coinbase' => [
        'bc1q...sample3'
    ]
];

function tagAddress($address)
{
    global $EXCHANGE_ADDRESSES;

    foreach ($EXCHANGE_ADDRESSES as $exchange => $list) {
        if (in_array($address, $list)) {
            return strtoupper($exchange);
        }
    }

    return "UNKNOWN";
}   