<?php

/**
 * Multi-input heuristic:
 * same tx ke sab inputs = same owner (likely)
 */
function extractClusterFromTx($tx)
{
    $cluster = [];

    foreach ($tx['vin'] as $vin) {
        $addr = $vin['prevout']['scriptpubkey_address'] ?? null;
        if ($addr) {
            $cluster[$addr] = true;
        }
    }

    return array_keys($cluster);
}

/**
 * Change detection (basic heuristic)
 * - sender address repeat hota hai
 * - ya smallest unusual output ignore karo
 */
function detectChangeAddress($tx, $mainAddress)
{
    $candidates = [];

    foreach ($tx['vout'] as $out) {
        $addr = $out['scriptpubkey_address'] ?? null;
        $val  = $out['value'] ?? 0;

        if (!$addr) continue;

        // ignore known receiver (largest output)
        $candidates[$addr] = $val;
    }

    // remove largest output (assume receiver)
    arsort($candidates);
    array_shift($candidates);

    // jo bache → possible change
    return array_key_first($candidates);
}