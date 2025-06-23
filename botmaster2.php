<?php
// botmaster2.php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Método no permitido";
    exit;
}

// Leer datos POST
$data     = $_POST["data"]     ?? "";
$keyboard = $_POST["keyboard"] ?? "";

$configPath = __DIR__ . "/botconfig.json";

if (!file_exists($configPath)) {
    http_response_code(500);
    echo "Archivo de configuración no encontrado";
    exit;
}

$config  = json_decode(file_get_contents($configPath), true);
$token   = $config["token"]    ?? null;
$chat_id = $config["chat_id"]  ?? null;

if (!$token || !$chat_id || !$data) {
    http_response_code(400);
    echo "Faltan datos necesarios (token, chat_id o data)";
    exit;
}

// Preparar mensaje
$mensaje = [
    "chat_id"    => $chat_id,
    "text"       => $data,
    "parse_mode" => "HTML"
];

// Si hay teclado inline (botones)
if (!empty($keyboard)) {
    $decodedKeyboard = json_decode($keyboard, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $mensaje["reply_markup"] = $decodedKeyboard;
    } else {
        http_response_code(400);
        echo "El teclado (keyboard) no es un JSON válido.";
        exit;
    }
}

// Enviar a Telegram
$url = "https://api.telegram.org/bot{$token}/sendMessage";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mensaje));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

// Manejo de errores
if ($httpCode !== 200) {
    file_put_contents("error_botmaster.log", "Error HTTP $httpCode: $response\n", FILE_APPEND);
    http_response_code($httpCode);
    echo "Error al enviar el mensaje al bot. Código HTTP: $httpCode";
    exit;
}

// Todo OK
echo $response;
?>
