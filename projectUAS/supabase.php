<?php
define('SUPABASE_URL', 'https://mxajscgaszabmustddfq.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im14YWpzY2dhc3phYm11c3RkZGZxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk1Mjg5MTQsImV4cCI6MjA2NTEwNDkxNH0.jyueIxwoYJf3sbDra98uN3vD6MYrvX_ZWN6hwyPzD38');

// Helper request ke Supabase REST
function supabase_request($method, $endpoint, $data = null) {
    $url = SUPABASE_URL . $endpoint;
    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];
    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
        ]
    ];
    if ($data !== null) {
        $opts['http']['content'] = json_encode($data);
    }
    $context = stream_context_create($opts);
    $res = file_get_contents($url, false, $context);
    return json_decode($res, true);
}
?>
