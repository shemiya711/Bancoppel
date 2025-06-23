<?php
header("Content-Type: application/json");

$estadoDir = __DIR__ . "/status";

// Crear carpeta si no existe
if (!is_dir($estadoDir)) mkdir($estadoDir);

// Método: GET → revisar estado actual
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["txid"])) {
    $txid = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET["txid"]);
    $file = "$estadoDir/{$txid}.json";

    if (file_exists($file)) {
        echo file_get_contents($file);
    } else {
        echo json_encode(["status" => "esperando"]);
    }
    exit;
}

// Método: POST → guardar estado manual (opcional)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $status = $input["status"] ?? "sin_status";
    $txid = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET["txid"] ?? uniqid("manual_"));
    $file = "$estadoDir/{$txid}.json";

    file_put_contents($file, json_encode(["status" => $status]));
    echo json_encode(["ok" => true, "txid" => $txid]);
    exit;
}

// Método no permitido
http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
?>
