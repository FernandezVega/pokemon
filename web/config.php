<?php
// Conexión PDO. Configuración por variables de entorno (ver ../.env.example).
function conectar(): PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'pokemon';
    $user = getenv('DB_USER') ?: 'pokemon';
    $pass = getenv('DB_PASSWORD');
    if ($pass === false) {
        throw new RuntimeException('Falta la variable de entorno DB_PASSWORD');
    }
    return new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Permite usar el mismo parámetro nombrado (:q, :gen...) más de una vez en una sentencia
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
}
