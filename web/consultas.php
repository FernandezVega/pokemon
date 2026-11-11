<?php
/*
 * ============================================================
 *  ACTIVIDAD PARA EL ALUMNADO: escribe aquí las sentencias SQL
 * ============================================================
 * Es el ÚNICO fichero que tienes que modificar. Cada constante es una sentencia SQL
 * con parámetros con nombre (:nombre). La aplicación ya se encarga de ejecutarla con
 * PDO (consultas preparadas) y de pintar el resultado.
 *
 * El catálogo (tablas generacion y pokemon) es de SOLO LECTURA: no se modifica.
 * El CRUD se hace sobre una tabla nueva, POKEDEX: los Pokémon que ha capturado el entrenador.
 *
 *  - Mientras una constante esté vacía ('') la web te avisa de cuál falta.
 *  - Usa TODOS los parámetros indicados y ninguno más (PDO falla si sobra o falta alguno).
 *  - Respeta los NOMBRES DE COLUMNA que devuelve cada SELECT (usa alias con AS si hace falta).
 *  - Tablas:  generacion(id, nombre, region, total_pokemon)
 *             pokemon(id, nombre, altura_m, peso_kg, tipo, imagen_url, generacion_id)
 *             pokedex(id, pokemon_id, apodo, nivel, fecha_captura, notas)
 *               · pokedex.pokemon_id es clave foránea de pokemon.id
 *               · apodo y notas pueden ser NULL; nivel va de 1 a 100
 *
 * Orden sugerido: 1) listar  2) capturar  3) obtener  4) actualizar  5) liberar  6) resumen.
 */

// ===== EJEMPLOS YA RESUELTOS (catálogo, solo lectura): léelos para inspirarte ==========

// Devuelve: id, nombre, region, total_pokemon
const SQL_LISTAR_GENERACIONES =
    'SELECT id, nombre, region, total_pokemon FROM generacion ORDER BY id';

// Parámetros: :q (texto del nombre, '' = sin filtro), :gen (id de generación, 0 = todas),
// :tipo (texto del tipo, '' = todos)
const SQL_BUSCAR_POKEMON = "
    SELECT p.id, p.nombre, p.altura_m, p.peso_kg, p.tipo, p.imagen_url,
           g.id AS gen_id, g.nombre AS gen_nombre, g.region
    FROM pokemon p
    JOIN generacion g ON g.id = p.generacion_id
    WHERE (:q = '' OR p.nombre LIKE CONCAT('%', :q, '%'))
    AND (:tipo = '' OR p.tipo = :tipo)
      AND (:gen = 0 OR p.generacion_id = :gen)
    ORDER BY p.id
    LIMIT 200";

    //mejora D2 -> listar por tipos
const SQL_LISTAR_TIPOS =
    'SELECT DISTINCT tipo FROM pokemon ORDER BY tipo';

// Parámetros: :id
const SQL_OBTENER_POKEMON =
    'SELECT id, nombre, altura_m, peso_kg, tipo, imagen_url, generacion_id FROM pokemon WHERE id = :id';

// ===== TU TRABAJO: CRUD sobre la tabla POKEDEX ===========================================

// 1. READ (listado) ------------------------------------------------------------------------
// Sin parámetros. Una fila por captura, con los datos del Pokémon (JOIN pokedex-pokemon-generacion).
// Devuelve:   id (el de la captura en pokedex), apodo, nivel, fecha_captura, notas,
//             pokemon_id, nombre, tipo, imagen_url, altura_m, peso_kg,
//             gen_nombre, region
// Orden:      la captura más reciente primero (fecha_captura DESC, luego id DESC).
// Se ve en:   página «Mi Pokédex».

const SQL_LISTAR_POKEDEX = 
'SELECT d.id, d.apodo, d.nivel, d.fecha_captura, d.notas,
        p.id AS pokemon_id, p.nombre, p.tipo, p.imagen_url,
        p.altura_m, p.peso_kg,
        g.nombre AS gen_nombre, g.region
FROM pokedex d
JOIN pokemon p ON d.pokemon_id = p.id
JOIN generacion g ON p.generacion_id = g.id
ORDER BY d.fecha_captura DESC, d.id DESC;';

// 2. CREATE ---------------------------------------------------------------------------------
// Parámetros: :pokemon_id, :apodo, :nivel, :fecha_captura, :notas
// (el id es AUTO_INCREMENT: no lo insertes)
// Se ve en:   botón «Capturar» del catálogo.
const SQL_CAPTURAR_POKEMON = 
'INSERT INTO pokedex (pokemon_id, apodo, nivel, fecha_captura, notas)
VALUES (:pokemon_id, :apodo, :nivel, :fecha_captura, :notas)';

// 3. READ (una captura) ---------------------------------------------------------------------
// Parámetros: :id (el de la tabla pokedex)
// Devuelve:   id, pokemon_id, apodo, nivel, fecha_captura, notas,
//             nombre, tipo, imagen_url        (estos tres, del Pokémon: necesita JOIN)
// Se ve en:   botón «Editar» (rellena el formulario).
const SQL_OBTENER_POKEDEX = 
'SELECT d.id, d.pokemon_id, d.apodo, d.nivel, d.fecha_captura, d.notas,
       p.nombre, p.tipo, p.imagen_url
FROM pokedex d
JOIN pokemon p ON d.pokemon_id = p.id
WHERE d.id = :id;';

// 4. UPDATE ---------------------------------------------------------------------------------
// Parámetros: :id, :apodo, :nivel, :notas     (el Pokémon capturado y la fecha NO cambian)
// ¡No olvides el WHERE!
// Se ve en:   botón «Editar» → «Guardar».
const SQL_ACTUALIZAR_POKEDEX =
'UPDATE pokedex
SET apodo = :apodo,
    nivel = :nivel,
    notas = :notas
WHERE id = :id;';

// 5. DELETE ---------------------------------------------------------------------------------
// Parámetros: :id
// ¡No olvides el WHERE!  Solo borra de pokedex: el Pokémon sigue existiendo en el catálogo.
// Se ve en:   botón «Liberar».
const SQL_LIBERAR_POKEMON = 
'DELETE FROM pokedex 
WHERE id = :id;';

// 6. AGREGADOS (GROUP BY) --------------------------------------------------------------------
// Sin parámetros. Una fila por tipo de Pokémon capturado (solo tipos que tengas):
//             tipo, total (nº de capturas), nivel_medio (media de nivel, 1 decimal)
// Orden:      total DESC, luego tipo.
// Pista:      JOIN + COUNT + AVG + ROUND + GROUP BY
// Se ve en:   chips de estadísticas de «Mi Pokédex».
const SQL_RESUMEN_POKEDEX = 
'SELECT 
    p.tipo, 
    COUNT(d.id) AS total, 
    ROUND(AVG(d.nivel), 1) AS nivel_medio 
FROM pokedex d 
JOIN pokemon p ON d.pokemon_id = p.id 
GROUP BY p.tipo 
ORDER BY total DESC, p.tipo;';
