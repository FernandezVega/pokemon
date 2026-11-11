-- Esquema de la actividad Pokémon (MySQL 8 / MariaDB 10.5+)
CREATE DATABASE IF NOT EXISTS pokemon
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pokemon;

DROP TABLE IF EXISTS pokedex;
DROP TABLE IF EXISTS pokemon;
DROP TABLE IF EXISTS generacion;

CREATE TABLE generacion (
  id            TINYINT UNSIGNED PRIMARY KEY,
  nombre        VARCHAR(30) NOT NULL,          -- "Generación I"
  region        VARCHAR(30) NOT NULL,          -- "Kanto"
  total_pokemon SMALLINT UNSIGNED NOT NULL     -- cuántos Pokémon introdujo
) ENGINE=InnoDB;

CREATE TABLE pokemon (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(50) NOT NULL,
  altura_m      DECIMAL(4,1) NOT NULL,
  peso_kg       DECIMAL(6,1) NOT NULL,
  tipo          VARCHAR(20) NOT NULL,          -- tipo principal
  imagen_url    VARCHAR(255) NOT NULL,
  generacion_id TINYINT UNSIGNED NOT NULL,
  INDEX idx_pokemon_nombre (nombre),
  CONSTRAINT fk_pokemon_generacion FOREIGN KEY (generacion_id)
    REFERENCES generacion (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabla de trabajo de la actividad: los Pokémon que ha capturado el entrenador.
-- Las tablas generacion y pokemon son el catálogo (solo lectura); el CRUD se hace aquí.
CREATE TABLE pokedex (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pokemon_id     INT UNSIGNED NOT NULL,
  apodo          VARCHAR(30) NULL,
  nivel          TINYINT UNSIGNED NOT NULL DEFAULT 5,   -- 1..100
  fecha_captura  DATE NOT NULL,
  notas          VARCHAR(200) NULL,
  INDEX idx_pokedex_pokemon (pokemon_id),
  CONSTRAINT fk_pokedex_pokemon FOREIGN KEY (pokemon_id)
    REFERENCES pokemon (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
