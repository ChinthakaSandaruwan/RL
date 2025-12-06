<?php

require_once __DIR__ . '/../config/db.php';

function smslenz_send_sms(string $contact, string $message): bool
{
    $userId   = env('SMSLENZ_USER_ID', '');
    $apiKey   = env('SMSLENZ_API_KEY', '');
    $baseUrl  = rtrim(env('SMSLENZ_BASE_URL', 'https://smslenz.lk/api'), '/');
    $senderId = env('SMSLENZ_SENDER_ID', 'RentalLanka');

    if ($userId === '' || $apiKey === '') {
        return false;
    }

    $url = $baseUrl . '/send-sms';

    $payload = http_build_query([
        'user_id'   => $userId,
        'api_key'   => $apiKey,
        'sender_id' => $senderId,
        'contact'   => $contact,
        'message'   => $message,
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 10,
        ],
    ]);

    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        error_log("SMSLenz API Error: Failed to connect to $url");
        return false;
    }

    $data = json_decode($result, true);
    if (!is_array($data)) {
        error_log("SMSLenz API Error: Invalid JSON response: $result");
        return false;
    }

    if (isset($data['status']) && $data['status'] === 'error') {
         error_log("SMSLenz API Error: " . ($data['data'] ?? 'Unknown error'));
    }

    // Check for success based on observed API response
    if (isset($data['data']['status']) && $data['data']['status'] === 'success') {
        return true;
    }
    
    // Fallback for previous expected format
    return isset($data['success']) && $data['success'] === true;
}
