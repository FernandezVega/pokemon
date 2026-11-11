<?php
// Catálogo de Pokémon (solo lectura): buscador, filtro por generación y botón «Capturar».
require __DIR__ . '/repositorio.php';

$q = trim($_GET['q'] ?? '');
$gen = (int) ($_GET['gen'] ?? 0);
$tipo = trim($_GET['tipo'] ?? '');
$avisos = [];
$generaciones = $tipos = $pokemon = [];
try {
    $pdo = conectar();
    $generaciones = listarGeneraciones($pdo);
     $tipos = listarTipos($pdo);
    // Solo aceptamos un tipo que exista de verdad en el catálogo
    if ($tipo !== '' && !in_array($tipo, array_column($tipos, 'tipo'), true)) {
        $tipo = '';
    }
    $pokemon = buscarPokemon($pdo, $q, $gen, $tipo);
} catch (Throwable $e) {
    $avisos[] = mensajeError($e);
}

//Mejora D2 -> Helper de enlaces, enlace a cada chip: conserva la búsqueda y la generación, cambia solo el tipo

$urlFiltro = static function (string $nuevoTipo) use ($q, $gen): string {
    $params = array_filter([
        'q'    => $q,
        'gen'  => $gen ?: null,
        'tipo' => $nuevoTipo !== '' ? $nuevoTipo : null,
    ], static fn ($v) => $v !== null && $v !== '');
    return $params ? '?' . http_build_query($params) : '?';
};

abrirPagina('Catálogo', 'catalogo');
?>
<div class="titulo-pagina">
  <h1>Catálogo</h1>
  <p class="titulo-pagina__sub">Busca un Pokémon y captúralo para tu Pokédex.</p>
</div>

<?php pintarAvisos(flash(), $avisos); ?>

<form id="filtros" class="buscador" method="get" role="search">
  <label class="solo-lector" for="q">Nombre</label>
  <input class="control" id="q" name="q" type="search" placeholder="Buscar por nombre…" value="<?= h($q) ?>" autocomplete="off">
  <label class="solo-lector" for="gen">Generación</label>
  <select class="control" id="gen" name="gen">
    <option value="0">Todas las generaciones</option>
    <?php foreach ($generaciones as $g): ?>
      <option value="<?= (int) $g['id'] ?>" <?= $gen === (int) $g['id'] ? 'selected' : '' ?>><?= h($g['nombre']) ?> · <?= h($g['region']) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="boton" type="submit">Buscar</button></noscript>
</form>

    <!--Mejora D2 Estamos creando los chips con los tipos de pokemon -->

<?php if ($tipos): ?>
<nav class="estadisticas filtros-tipo" aria-label="Filtrar por tipo">
  <a class="estadistica" href="<?= h($urlFiltro('')) ?>" aria-current="<?= $tipo === '' ? 'true' : 'false' ?>">Todos</a>
  <?php foreach ($tipos as $t): ?>
    <a class="estadistica" data-tipo="<?= h($t['tipo']) ?>" href="<?= h($urlFiltro($t['tipo'])) ?>" aria-current="<?= $tipo === $t['tipo'] ? 'true' : 'false' ?>">
      <span class="chip" data-tipo="<?= h($t['tipo']) ?>"><?= h($t['tipo']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<p id="estado" class="estado" aria-live="polite"><?= $pokemon ? count($pokemon) . ' Pokémon' . ($tipo !== '' ? ' de tipo ' . h($tipo) : ' en el catálogo') : '' ?></p>
<section id="rejilla" class="rejilla" aria-label="Resultados">
  <?php foreach ($pokemon as $p): ?>
    <article class="tarjeta" data-tipo="<?= h($p['tipo']) ?>">
      <span class="tarjeta__num insignia">#<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span>
      <img class="tarjeta__imagen" src="<?= h($p['imagen_url']) ?>" alt="" loading="lazy" width="150" height="150">
      <h2 class="tarjeta__titulo"><?= h($p['nombre']) ?></h2>
      <span class="chip" data-tipo="<?= h($p['tipo']) ?>"><?= h($p['tipo']) ?></span>
      <p class="tarjeta__meta"><?= h($p['altura_m']) ?> m · <?= h($p['peso_kg']) ?> kg</p>
      <p class="tarjeta__suave"><?= h($p['gen_nombre']) ?> · <?= h($p['region']) ?></p>
      <div class="tarjeta__acciones">
        <a class="boton boton--peq" href="ficha.php?pokemon_id=<?= (int) $p['id'] ?>">Capturar<span class="solo-lector"> a <?= h($p['nombre']) ?></span></a>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$pokemon && !$avisos): ?>
    <div class="vacio"><p class="vacio__titulo">Ningún Pokémon coincide</p><p>Prueba con otro nombre o con «Todas las generaciones».</p></div>
  <?php endif; ?>
</section>
<?php cerrarPagina(['js/app.js']);
