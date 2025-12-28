<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

$input = json_decode(file_get_contents('php://input'), true);
$apiKey = '';
// Method 1: Direct environment variable (for production servers)
$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
$text = $input['text'] ?? '';
$tone = $input['tone'] ?? 'professional'; // Get the tone parameter

$model = "gemini-2.5-flash";
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

// Different prompts based on tone
$prompts = [
    'professional' => "Return ONLY a single professional rephrasing of this text. Do not give options, explanations, or additional text. Just return the rephrased version. Text: ",
    'friendly' => "Return ONLY a single friendly rephrasing of this text. Do not give options, explanations, or additional text. Just return the rephrased version. Text: "
];

$prompt = $prompts[$tone] ?? $prompts['professional'];

$payload = [
    "contents" => [[
        "parts" => [["text" => $prompt . $text]]
    ]],
    "generationConfig" => [
        "temperature" => 0.7,
        "topK" => 40,
        "topP" => 0.95,
        "maxOutputTokens" => 2048,
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $apiKey
]);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode(['newText' => trim($result['candidates'][0]['content']['parts'][0]['text'])]);
} else {
    echo json_encode(['newText' => "Error", 'details' => $result]);
}
