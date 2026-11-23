<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['error' => 'Method not allowed']);
}

$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody === false ? '' : $rawBody, true);
if (!is_array($body)) {
    respond(400, ['error' => 'Invalid JSON body']);
}

$message = $body['message'] ?? null;
if (!is_string($message) || trim($message) === '') {
    respond(400, ['error' => 'Message required']);
}

$message = trim($message);
if (strlen($message) > 12000) {
    respond(413, ['error' => 'Message is too long']);
}

$apiKey = getenv('GROQ_API_KEY');
if ($apiKey === false || $apiKey === '') {
    $envPath = dirname(__DIR__, 2) . '/.env';
    if (is_readable($envPath)) {
        $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines ?: [] as $line) {
            if (preg_match('/^\s*GROQ_API_KEY\s*=\s*(.*?)\s*$/', $line, $matches)) {
                $apiKey = trim($matches[1], " \t\n\r\0\x0B\"'");
                break;
            }
        }
    }
}

if (!is_string($apiKey) || $apiKey === '') {
    respond(500, ['error' => 'API key not configured']);
}

if (!function_exists('curl_init')) {
    respond(500, ['error' => 'The server is missing the PHP cURL extension']);
}

$system = <<<'PROMPT'
You are VLAP (Vasl Language Analysis Platform), a culturally-competent NLP system trained on BIPOC, LGBTQ+, and underserved youth language to detect behavioral health distress signals.

Analyze the user message and return ONLY a valid JSON object - no preamble, no markdown, no explanation:

{
  "risk_score": <float 0.0-1.0>,
  "risk_tier": <"crisis"|"high"|"moderate"|"clear">,
  "signals": [{"code": "<code>", "label": "<label>"}],
  "cultural_context": "<cultural/linguistic patterns detected, or null>",
  "member_response": "<warm human response the member sees - no AI mention, no clinical language>",
  "recommended_action": "<action for the clinical team>"
}

Signal taxonomy:
SHA-01 Direct suicidal ideation | SHA-02 Passive ideation | SHA-03 Coded ideation (e.g. "unaliving") | SHA-04 Self-harm reference
CRS-01 Acute crisis | CRS-02 Crisis escalation risk
HOP-01 Learned helplessness | HOP-02 Future foreclosure | HOP-03 Existential hopelessness
ISO-01 Social withdrawal | ISO-02 Disconnection | ISO-03 Abandonment fear
CCM-01 Stigma barrier | CCM-02 Pre-disclosure hedge | CCM-03 AAVE minimization inversion | CCM-04 Family/community pressure

Risk tiers: crisis (score 0.80-1.0) | high (0.55-0.79) | moderate (0.30-0.54) | clear (0.0-0.29)
Return ONLY the JSON object.
PROMPT;

$requestBody = json_encode([
    'model' => 'openai/gpt-oss-20b',
    'max_tokens' => 1024,
    'response_format' => ['type' => 'json_object'],
    'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $message],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($requestBody === false) {
    respond(500, ['error' => 'Could not encode request']);
}

$curl = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => $requestBody,
]);

$response = curl_exec($curl);
$curlError = curl_errno($curl);
$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

if ($response === false || $curlError !== 0) {
    respond(502, ['error' => 'Could not reach the analysis provider']);
}

$providerResponse = json_decode($response, true);
if ($status < 200 || $status >= 300) {
    respond($status >= 400 && $status <= 599 ? $status : 502, [
        'error' => $providerResponse['error']['message'] ?? 'Groq API error',
    ]);
}

$content = $providerResponse['choices'][0]['message']['content'] ?? null;
$result = is_string($content) ? json_decode($content, true) : null;
$tiers = ['crisis', 'high', 'moderate', 'clear'];

if (
    !is_array($result)
    || !isset($result['risk_score'])
    || !is_numeric($result['risk_score'])
    || (float) $result['risk_score'] < 0
    || (float) $result['risk_score'] > 1
    || !in_array($result['risk_tier'] ?? null, $tiers, true)
    || !is_array($result['signals'] ?? null)
) {
    respond(502, ['error' => 'Unexpected analysis response']);
}

foreach ($result['signals'] as $signal) {
    if (!is_array($signal) || !is_string($signal['code'] ?? null) || !is_string($signal['label'] ?? null)) {
        respond(502, ['error' => 'Unexpected analysis response']);
    }
}

respond(200, $result);
