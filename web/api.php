<?php
// Endpoint JSON para la búsqueda en vivo: api.php?q=pika&gen=1
require __DIR__ . '/repositorio.php';

header('Content-Type: application/json; charset=utf-8');
try {
    $q = trim($_GET['q'] ?? '');
    $gen = (int) ($_GET['gen'] ?? 0);
    $tipo = trim($_GET['tipo'] ?? '');
    echo json_encode(buscarPokemon(conectar(), $q, $gen, $tipo), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => mensajeError($e)], JSON_UNESCAPED_UNICODE);
}
