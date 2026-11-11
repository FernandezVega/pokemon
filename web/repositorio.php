<?php
// Ejecuta las sentencias de consultas.php. El alumnado NO necesita tocar este fichero.
require __DIR__ . '/config.php';
require __DIR__ . '/consultas.php';

/** Se lanza cuando una sentencia SQL de consultas.php sigue vacía. */
class SqlPendiente extends RuntimeException
{
    public function __construct(public readonly string $constante)
    {
        parent::__construct("Falta escribir la sentencia $constante en web/consultas.php");
    }
}

function ejecutar(PDO $pdo, string $constante, string $sql, array $params = []): PDOStatement
{
    if (trim($sql) === '') {
        throw new SqlPendiente($constante);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function listarGeneraciones(PDO $pdo): array
{
    return ejecutar($pdo, 'SQL_LISTAR_GENERACIONES', SQL_LISTAR_GENERACIONES)->fetchAll();
}
//Mejora D2
function listarTipos(PDO $pdo): array
{
    return ejecutar($pdo, 'SQL_LISTAR_TIPOS', SQL_LISTAR_TIPOS)->fetchAll();
}

function buscarPokemon(PDO $pdo, string $q = '', int $gen = 0, string $tipo = ''): array
{
    return ejecutar($pdo, 'SQL_BUSCAR_POKEMON', SQL_BUSCAR_POKEMON, ['q' => $q, 'gen' => $gen, 'tipo' => $tipo])->fetchAll();
}

function obtenerPokemon(PDO $pdo, int $id): ?array
{
    $fila = ejecutar($pdo, 'SQL_OBTENER_POKEMON', SQL_OBTENER_POKEMON, ['id' => $id])->fetch();
    return $fila ?: null;
}

function listarPokedex(PDO $pdo): array
{
    return ejecutar($pdo, 'SQL_LISTAR_POKEDEX', SQL_LISTAR_POKEDEX)->fetchAll();
}

function obtenerPokedex(PDO $pdo, int $id): ?array
{
    $fila = ejecutar($pdo, 'SQL_OBTENER_POKEDEX', SQL_OBTENER_POKEDEX, ['id' => $id])->fetch();
    return $fila ?: null;
}

function capturarPokemon(PDO $pdo, array $d): int
{
    ejecutar($pdo, 'SQL_CAPTURAR_POKEMON', SQL_CAPTURAR_POKEMON, [
        'pokemon_id' => $d['pokemon_id'], 'apodo' => $d['apodo'], 'nivel' => $d['nivel'],
        'fecha_captura' => $d['fecha_captura'], 'notas' => $d['notas'],
    ]);
    return (int) $pdo->lastInsertId();
}

function actualizarPokedex(PDO $pdo, int $id, array $d): void
{
    ejecutar($pdo, 'SQL_ACTUALIZAR_POKEDEX', SQL_ACTUALIZAR_POKEDEX, [
        'id' => $id, 'apodo' => $d['apodo'], 'nivel' => $d['nivel'], 'notas' => $d['notas'],
    ]);
}

function liberarPokemon(PDO $pdo, int $id): int
{
    return ejecutar($pdo, 'SQL_LIBERAR_POKEMON', SQL_LIBERAR_POKEMON, ['id' => $id])->rowCount();
}

function resumenPokedex(PDO $pdo): array
{
    return ejecutar($pdo, 'SQL_RESUMEN_POKEDEX', SQL_RESUMEN_POKEDEX)->fetchAll();
}

// ---- utilidades de la aplicación (sesión, CSRF, mensajes, escape) ----
session_start();

function h(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrfValido(): bool
{
    $esperado = $_SESSION['csrf'] ?? '';
    return $esperado !== '' && hash_equals($esperado, (string) ($_POST['csrf'] ?? ''));
}

function flash(?string $msg = null, string $tipo = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'tipo' => $tipo];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Mensaje amigable para una excepción (SQL pendiente o error de SQL del alumno). */
function mensajeError(Throwable $e): string
{
    if ($e instanceof SqlPendiente) {
        return '✏️ ' . $e->getMessage();
    }
    if ($e instanceof PDOException) {
        return '⚠️ Error SQL: ' . $e->getMessage();
    }
    return '⚠️ ' . $e->getMessage();
}

/** Abre la página: <head>, cabecera con navegación y <main>. $activa: 'catalogo' | 'pokedex' | ''. */
//MEJORA D6
// ORIGINAL: function abrirPagina(string $titulo, string $activa = '', bool $estrecho = false): void
function abrirPagina(string $titulo, string $activa = '', bool $estrecho = false, string $claseCuerpo = ''): void
{
    $enlace = fn($pag, $txt, $url) => '<a class="nav__enlace" href="' . $url . '"' . ($activa === $pag ? ' aria-current="page"' : '') . '>' . $txt . '</a>';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . h($titulo) . ' · Pokédex DAW</title>'
        . '<link rel="icon" href="img/favicon.svg" type="image/svg+xml">'
        . '<link rel="stylesheet" href="css/style.css">'
        //MEJORA D6
        //ORIGINAL: '<script src="js/tema.js"></script></head><body>'
        . '<script src="js/tema.js"></script></head><body' . ($claseCuerpo !== '' ? ' class="' . h($claseCuerpo) . '"' : '') . '>'
        . '<a class="saltar" href="#contenido">Saltar al contenido</a>'
        . '<header class="cabecera">'
        . '<a class="marca" href="index.php"><span class="marca__bola" aria-hidden="true"></span>Pokédex <span class="marca__detalle">DAW</span></a>'
        . '<nav class="nav" aria-label="Principal">' . $enlace('catalogo', 'Catálogo', 'index.php') . $enlace('pokedex', 'Mi Pokédex', 'pokedex.php') . '</nav>'
        . '<div class="cabecera__herramientas"><button id="tema-boton" class="boton boton--icono" type="button" aria-pressed="false" aria-label="Cambiar tema">🌙</button></div>'
        . '</header><main id="contenido" class="contenedor' . ($estrecho ? ' contenedor--estrecho' : '') . '">';
}

/** Cierra <main>, pinta el pie y carga los scripts indicados. */
function cerrarPagina(array $scripts = []): void
{
    echo '</main><footer class="pie">Datos e imágenes de <a href="https://pokeapi.co/">PokéAPI</a> · Actividad de DAW · '
        . '<a href="guia-estilos.html">Guía de estilos</a></footer>';
    foreach ($scripts as $js) {
        echo '<script src="' . h($js) . '"></script>';
    }
    echo '</body></html>';
}

/** Avisos flash + errores/SQL pendiente. */
function pintarAvisos(?array $flash, array $avisos): void
{
    if ($flash) {
        echo '<p class="aviso aviso--' . ($flash['tipo'] === 'ok' ? 'ok' : 'error') . '" role="status">' . h($flash['msg']) . '</p>';
    }
    foreach ($avisos as $a) {
        echo '<p class="aviso aviso--error" role="alert">' . h($a) . '</p>';
    }
}
