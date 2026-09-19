<?php
// ai/api-chat.php
header('Content-Type: application/json');

$secretsFile = __DIR__ . '/secrets.php';
if (!file_exists($secretsFile)) {
    echo json_encode(['error' => 'Secrets file missing.']); exit;
}
$secrets = include $secretsFile;

$apiKey  = $secrets['GROQ_API_KEY'] ?? '';
$model   = $secrets['GROQ_MODEL'] ?? 'llama-3.1-8b-instant';
$baseUrl = rtrim($secrets['GROQ_BASE'] ?? 'https://api.groq.com/openai/v1', '/');

if (!$apiKey) { echo json_encode(['error' => 'API key missing.']); exit; }

// Read input
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$messages = $input['messages'] ?? [['role' => 'user', 'content' => 'Hello']];

// Validate messages (basic)
if (!is_array($messages) || empty($messages)) {
    echo json_encode(['error' => 'Invalid messages payload.']); exit;
}

// Call Groq /chat/completions
$payload = json_encode([
    'model'    => $model,
    'messages' => $messages,
]);

$ch = curl_init($baseUrl . "/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json",
    ],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    echo json_encode(['error' => "Curl error: $err"]); exit;
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// Handle non-2xx
if ($httpCode < 200 || $httpCode >= 300) {
    $msg = $data['error']['message'] ?? "HTTP $httpCode";
    echo json_encode(['error' => "Upstream error: $msg", 'status' => $httpCode]); exit;
}

// Extract reply
$reply = $data['choices'][0]['message']['content'] ?? null;
if (!$reply) {
    echo json_encode(['error' => 'Invalid response from API', 'raw' => $data]); exit;
}

echo json_encode(['reply' => $reply]);
