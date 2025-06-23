<?php
// --- CONFIGURACIÓN ---
$configFile = __DIR__ . "/botconfig.json";
$token = "";
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    $token = $config["token"] ?? "";
}
if (!$token) exit;

$api = "https://api.telegram.org/bot$token";

// --- RECIBIR ACTUALIZACIÓN ---
$raw = file_get_contents("php://input");
file_put_contents("log_hook.txt", $raw); // Opcional para depurar

$update = json_decode($raw, true);
if (!$update) exit;

// --- CALLBACK QUERY (BOTÓN INTERACTIVO) ---
if (isset($update["callback_query"])) {
    $callback = $update["callback_query"];
    $data = $callback["data"] ?? "";
    $callback_id = $callback["id"];
    $chat_id = $callback["message"]["chat"]["id"];

    // Confirmar botón (quita "loading" en Telegram)
    file_get_contents("$api/answerCallbackQuery?callback_query_id=$callback_id");

    // Separar acción y transactionId
    $parts = explode(":", $data);
    if (count($parts) !== 2) exit;

    list($accion, $txid) = $parts;

    // Crear carpeta si no existe
    $estadoDir = __DIR__ . "/status";
    if (!is_dir($estadoDir)) mkdir($estadoDir);

    // Guardar estado en archivo
    $filename = "$estadoDir/{$txid}.json";
    file_put_contents($filename, json_encode(["status" => $accion]));

    // Confirmar acción al usuario
    $mensaje = "✅ Acción recibida: *$accion*\n🔗 ID: `$txid`";
    $params = [
        "chat_id" => $chat_id,
        "text" => $mensaje,
        "parse_mode" => "Markdown"
    ];
    file_get_contents("$api/sendMessage?" . http_build_query($params));

    exit;
}

// --- MENSAJE NORMAL ---
if (isset($update["message"])) {
    $chat_id = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"] ?? "";

    // Mensaje de eco
    $mensaje = "👋 Hola, escribiste: \"$text\"";
    $params = [
        "chat_id" => $chat_id,
        "text" => $mensaje
    ];
    file_get_contents("$api/sendMessage?" . http_build_query($params));

    exit;
}
?>
