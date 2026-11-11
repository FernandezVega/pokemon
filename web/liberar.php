<?php
require __DIR__ . '/repositorio.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValido()) {
    http_response_code(400);
    exit('Petición no válida');
}
try {
    $n = liberarPokemon(conectar(), (int) ($_POST['id'] ?? 0));
    flash($n > 0 ? 'Pokémon liberado.' : 'Esa captura no existe.', $n > 0 ? 'ok' : 'error');
} catch (Throwable $e) {
    flash(mensajeError($e), 'error');
}
header('Location: pokedex.php');
