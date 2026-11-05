<?php
// supabase.php - Supabase REST helpers (no secrets in this file)
// Credentials live in supabase-config.php (gitignored).
// To set up: copy supabase-config.example.php -> supabase-config.php and fill it in.

require_once __DIR__ . '/supabase-config.php';

function supabase_insert(string $table, array $row): bool {
    $ch = curl_init(SUPABASE_URL . "/rest/v1/{$table}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($row),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Prefer: return=minimal',
        ],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($res === false || $code >= 300) {
        error_log("Supabase insert failed: HTTP {$code} {$res}");
        return false;
    }
    return true;
}

function supabase_select(string $table, string $order = 'id.desc'): array {
    $ch = curl_init(SUPABASE_URL . "/rest/v1/{$table}?order={$order}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
        ],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '[]', true) ?: [];
}

function supabase_delete_all(string $table): bool {
    $ch = curl_init(SUPABASE_URL . "/rest/v1/{$table}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Prefer: return=minimal',
        ],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($res === false || $code >= 300) {
        error_log("Supabase delete failed: HTTP {$code} {$res}");
        return false;
    }
    return true;
}
