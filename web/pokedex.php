<?php
// Mi Pokédex: los Pokémon capturados (CRUD sobre la tabla pokedex).
require __DIR__ . '/repositorio.php';

$avisos = [];
$capturas = $resumen = [];
$consultaOk = true;   // false si algo falló: así no mostramos «vacío» cuando en realidad hay un error
try {
    $pdo = conectar();
    foreach (['capturas' => fn() => listarPokedex($pdo), 'resumen' => fn() => resumenPokedex($pdo)] as $var => $cargar) {
        try {
            $$var = $cargar();
        } catch (Throwable $e) {
            $avisos[] = mensajeError($e);
            $consultaOk = false;
        }
    }
} catch (Throwable $e) {
    $avisos[] = mensajeError($e);
    $consultaOk = false;
}
//MODIFICACION D6

$capturaNueva = !empty($_SESSION['captura']);
unset($_SESSION['captura']);

//MODIFICACION D6
// ORIGINAL: abrirPagina('Mi Pokédex', 'pokedex');
abrirPagina('Mi Pokédex', 'pokedex', false, $capturaNueva ? 'captura-nueva' : '');
?>
<div class="titulo-pagina">
  <h1>Mi Pokédex</h1>
  <p class="titulo-pagina__sub"><?= $consultaOk ? count($capturas) . ' Pokémon capturados · ' : '' ?><a href="index.php">Ir al catálogo para capturar más</a></p>
</div>

<?php pintarAvisos(flash(), $avisos); ?>

<?php if ($resumen): ?>
  <ul class="estadisticas" aria-label="Capturas por tipo">
    <?php foreach ($resumen as $r): ?>
      <li class="estadistica" data-tipo="<?= h($r['tipo']) ?>"><strong><?= (int) $r['total'] ?></strong>
        <?= h(ucfirst($r['tipo'])) ?> · nivel medio <?= h($r['nivel_medio']) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<section class="rejilla" aria-label="Capturas">
  <?php foreach ($capturas as $c): $nombreMostrado = $c['apodo'] ?: $c['nombre']; ?>
    <article class="tarjeta" data-tipo="<?= h($c['tipo']) ?>">
      <span class="tarjeta__num insignia">Nv. <?= (int) $c['nivel'] ?></span>
      <img class="tarjeta__imagen" src="<?= h($c['imagen_url']) ?>" alt="" loading="lazy" width="150" height="150">
      <h2 class="tarjeta__titulo"><?= h($nombreMostrado) ?></h2>
      <span class="chip" data-tipo="<?= h($c['tipo']) ?>"><?= h($c['tipo']) ?></span>
      <?php if ($c['apodo']): ?><p class="tarjeta__meta"><?= h($c['nombre']) ?> · #<?= str_pad($c['pokemon_id'], 4, '0', STR_PAD_LEFT) ?></p><?php endif; ?>
      <p class="tarjeta__suave">Capturado el <?= h(date('d/m/Y', strtotime($c['fecha_captura']))) ?> · <?= h($c['region']) ?></p>
      <?php if ($c['notas']): ?><p class="tarjeta__notas"><?= h($c['notas']) ?></p><?php endif; ?>
      <div class="tarjeta__acciones">
        <a class="boton boton--peq" href="ficha.php?id=<?= (int) $c['id'] ?>">Editar<span class="solo-lector"> a <?= h($nombreMostrado) ?></span></a>
        <form method="post" action="liberar.php" onsubmit="return confirm('¿Liberar a <?= h(addslashes($nombreMostrado)) ?>?')">
          <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
          <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
          <button class="boton boton--peq boton--peligro" type="submit">Liberar<span class="solo-lector"> a <?= h($nombreMostrado) ?></span></button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<?php if (!$capturas && $consultaOk): ?>
  <div class="vacio">
    <p class="vacio__titulo">Tu Pokédex está vacía</p>
    <p>Todavía no has capturado ningún Pokémon.</p>
    <a class="boton" href="index.php">Explorar el catálogo</a>
  </div>
<?php endif; ?>
<?php cerrarPagina();
