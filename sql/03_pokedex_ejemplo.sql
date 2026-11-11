-- Capturas de ejemplo para que la Pokédex no empiece vacía.
USE pokemon;
SET NAMES utf8mb4;

INSERT INTO pokedex (pokemon_id, apodo, nivel, fecha_captura, notas) VALUES
  (25,  'Rayo',   12, '2026-09-14', 'Mi primer Pokémon. Muy rápido.'),
  (4,   NULL,      8, '2026-09-20', NULL),
  (133, 'Eevee',  15, '2026-10-02', 'Puede evolucionar de muchas formas.'),
  (143, 'Siesta', 30, '2026-10-10', 'Duerme todo el día; bloquea caminos.');
