<?php
// Capturar (ficha.php?pokemon_id=25) o editar una captura (ficha.php?id=3).
require __DIR__ . '/repositorio.php';

$id = (int) ($_GET['id'] ?? 0);                 // captura existente (editar)
$pokemonId = (int) ($_GET['pokemon_id'] ?? 0);  // Pokémon del catálogo (capturar)
$errores = [];
$fatal = null;
$captura = ['apodo' => '', 'nivel' => 5, 'notas' => ''];
$pokemon = null;                                 // datos del Pokémon para mostrar (nombre, tipo, imagen_url)

try {
    $pdo = conectar();
    if ($id > 0) {
        $fila = obtenerPokedex($pdo, $id);
        if (!$fila) {
            flash('Esa captura no existe.', 'error');
            header('Location: pokedex.php');
            exit;
        }
        $pokemon = $fila;
        $captura = ['apodo' => $fila['apodo'] ?? '', 'nivel' => $fila['nivel'], 'notas' => $fila['notas'] ?? ''];
    } else {
        $pokemon = obtenerPokemon($pdo, $pokemonId);
        if (!$pokemon) {
            flash('Ese Pokémon no existe en el catálogo.', 'error');
            header('Location: index.php');
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrfValido()) {
            http_response_code(400);
            exit('Petición no válida');
        }
        $captura = [
            'apodo' => trim($_POST['apodo'] ?? ''),
            'nivel' => $_POST['nivel'] ?? '',
            'notas' => trim($_POST['notas'] ?? ''),
        ];
        // Validación en servidor: el SQL solo recibe datos ya comprobados
        if (mb_strlen($captura['apodo']) > 30) $errores[] = 'El apodo admite como máximo 30 caracteres.';
        if (filter_var($captura['nivel'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) === false) $errores[] = 'El nivel debe ser un entero entre 1 y 100.';
        if (mb_strlen($captura['notas']) > 200) $errores[] = 'Las notas admiten como máximo 200 caracteres.';

        if (!$errores) {
            $datos = [
                'apodo' => $captura['apodo'] === '' ? null : $captura['apodo'],   // '' -> NULL
                'nivel' => (int) $captura['nivel'],
                'notas' => $captura['notas'] === '' ? null : $captura['notas'],
            ];
            if ($id > 0) {
                actualizarPokedex($pdo, $id, $datos);
                flash('Captura actualizada.');
            } else {
                capturarPokemon($pdo, $datos + ['pokemon_id' => $pokemonId, 'fecha_captura' => date('Y-m-d')]);
                //MEJORA D6
                $_SESSION['captura'] = true;
                flash('¡' . $pokemon['nombre'] . ' capturado!');
            }
            header('Location: pokedex.php');
            exit;
        }
    }
} catch (Throwable $e) {
    $fatal = mensajeError($e);
}

abrirPagina($id ? 'Editar captura' : 'Capturar', $id ? 'pokedex' : 'catalogo', true);
?>
<div class="titulo-pagina">
  <h1><?= $id ? 'Editar captura' : 'Capturar Pokémon' ?></h1>
</div>
<?php
pintarAvisos(null, array_filter([$fatal, ...$errores]));
if ($pokemon): ?>
  <div class="ficha" data-tipo="<?= h($pokemon['tipo']) ?>">
    <img class="ficha__imagen" src="<?= h($pokemon['imagen_url']) ?>" alt="" width="96" height="96">
    <div><p class="ficha__nombre"><?= h($pokemon['nombre']) ?></p><span class="chip" data-tipo="<?= h($pokemon['tipo']) ?>"><?= h($pokemon['tipo']) ?></span></div>
  </div>

  <form method="post" class="formulario">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <label class="campo">Apodo (opcional)
      <input class="control" name="apodo" maxlength="30" value="<?= h($captura['apodo']) ?>">
    </label>
    <label class="campo">Nivel
      <input class="control" name="nivel" type="number" min="1" max="100" required value="<?= h($captura['nivel']) ?>">
      <span class="campo__ayuda">Entre 1 y 100.</span>
    </label>
    <label class="campo">Notas (opcional)
      <textarea class="control" name="notas" maxlength="200" rows="3"><?= h($captura['notas']) ?></textarea>
    </label>
    <div class="formulario__acciones">
      <button class="boton" type="submit">Guardar</button>
      <a class="boton boton--secundario" href="<?= $id ? 'pokedex.php' : 'index.php' ?>">Cancelar</a>
    </div>
  </form>
<?php endif;
cerrarPagina();
