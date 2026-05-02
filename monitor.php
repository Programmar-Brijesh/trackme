<?php

$config = require 'config.php';
require 'mailer.php';
require 'clustering.php';
require 'tagging.php';

$address = $config['address'];
$stateFile = 'state.json';
$logFile   = 'monitor.log';

/**
 * COLORS (CLI)
 */
function green($text) { return "\033[32m$text\033[0m"; }
function red($text)   { return "\033[31m$text\033[0m"; }
function yellow($text){ return "\033[33m$text\033[0m"; }
function cyan($text)  { return "\033[36m$text\033[0m"; }



/**
 * LOCK SYSTEM
 */
$lock = fopen("monitor.lock", "c");
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Already running\n");
}

/**
 * API CALL
 */
function api($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ["User-Agent: Mozilla/5.0"]
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) return null;

    curl_close($ch);
    return json_decode($res, true);
}

/**
 * FETCH TX LIST
 */
$txs = api("https://blockstream.info/api/address/$address/txs");

if (!$txs) {
    die(red("API failed\n"));
}

/**
 * LOAD STATE
 */
$state = file_exists($stateFile)
    ? json_decode(file_get_contents($stateFile), true)
    : ['seen' => []];

$newCount = 0;

foreach ($txs as $tx) {

    $txid = $tx['txid'];

    if (isset($state['seen'][$txid])) continue;

    $newCount++;

    $details = api("https://blockstream.info/api/tx/$txid");
    if (!$details) continue;

    /**
     * DIRECTION
     */
    $isOutgoing = false;

    foreach ($details['vin'] as $vin) {
        if (($vin['prevout']['scriptpubkey_address'] ?? '') === $address) {
            $isOutgoing = true;
        }
    }

    /**
     * NEXT HOP
     */
    $max = 0;
    $next = null;

    foreach ($details['vout'] as $out) {
        $addr = $out['scriptpubkey_address'] ?? '';
        $val  = $out['value'];

        if ($addr !== $address && $val > $max) {
            $max = $val;
            $next = $addr;
        }
    }

    /**
     * CLUSTER + CHANGE
     */
    $cluster = extractClusterFromTx($details);
    $change  = detectChangeAddress($details, $address);

    /**
     * TAGGING
     */
    $tag = tagAddress($next);

    /**
     * LOG
     */
    $record = [
        'txid' => $txid,
        'type' => $isOutgoing ? 'OUTGOING' : 'INCOMING',
        'next' => $next,
        'amount' => $max / 100000000,
        'cluster' => $cluster,
        'change' => $change,
        'tag' => $tag
    ];

    file_put_contents($logFile, json_encode($record) . "\n", FILE_APPEND);

    /**
     * OUTPUT
     */
    echo cyan("\n==============================\n");

    if ($isOutgoing) {

        echo red("🚨 OUTGOING TRANSACTION 🚨\n");
        echo "TXID      : " . yellow($txid) . "\n";
        echo "NEXT WALLET: " . green($next) . "\n";
        echo "AMOUNT    : " . green(($max/100000000) . " BTC") . "\n";
        echo "CLUSTER   : " . count($cluster) . " wallets\n";
        echo "CHANGE    : " . $change . "\n";
        echo "TAG       : " . $tag . "\n";

        sendMailAlert("BTC ALERT", $txid . " -> " . $next, $config);

    } else {

        echo "Incoming TX : " . yellow($txid) . "\n";
        echo "Amount      : " . ($max/100000000) . " BTC\n";
    }

    echo cyan("==============================\n");

    $state['seen'][$txid] = time();
}

/**
 * SAVE STATE
 */
file_put_contents($stateFile, json_encode($state));

echo "\n" . green("Processed: $newCount transactions\n");