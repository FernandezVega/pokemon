#!/usr/bin/env python3
"""Descarga generaciones y Pokémon de https://pokeapi.co y genera sql/02_datos.sql.

Solo usa la librería estándar (Python 3.8+). Con --imagenes-locales descarga también las imágenes a web/img/pokemon/ y el SQL usa rutas locales
(con Pillow instalado, `pip install pillow`, las reduce a 300 px).
Uso:
    python3 scripts/descargar_pokeapi.py            # las 9 generaciones (~1025 Pokémon)
    python3 scripts/descargar_pokeapi.py --gens 1 2 3
"""
import argparse
import socket
import json
import re
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

try:  # opcional: si Pillow está instalado, las imágenes se reducen a 300 px (≈ 24 MB en total en vez de ≈ 130 MB)
    from PIL import Image
except ImportError:
    Image = None

API = "https://pokeapi.co/api/v2"

# Algunas redes anuncian IPv6 pero no lo enrutan ("Network is unreachable"): forzamos IPv4.
_getaddrinfo = socket.getaddrinfo
socket.getaddrinfo = lambda host, *a, **k: [r for r in _getaddrinfo(host, *a, **k) if r[0] == socket.AF_INET]

CACHE = Path(__file__).resolve().parent / ".cache"   # permite reanudar si la red falla
IMG_DIR = Path(__file__).resolve().parent.parent / "web" / "img" / "pokemon"
SALIDA = Path(__file__).resolve().parent.parent / "sql" / "02_datos.sql"


LOCALES = False


def get_json(url, intentos=6):
    for i in range(intentos):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "clase-daw-pokemon"})
            with urllib.request.urlopen(req, timeout=20) as r:
                return json.load(r)
        except Exception:
            if i == intentos - 1:
                raise
            time.sleep(2 * (i + 1))


def sql(valor):
    """Escapa un valor para un INSERT."""
    if isinstance(valor, str):
        return "'" + valor.replace("\\", "\\\\").replace("'", "''") + "'"
    return str(valor)



def descargar_imagen(url, pid):
    """Guarda la imagen en web/img/pokemon/{id}.png (si no existe) y devuelve la ruta relativa web."""
    destino = IMG_DIR / f"{pid}.png"
    if not destino.exists() or destino.stat().st_size == 0:
        req = urllib.request.Request(url, headers={"User-Agent": "clase-daw-pokemon"})
        for i in range(6):
            try:
                with urllib.request.urlopen(req, timeout=30) as r:
                    datos = r.read()
                break
            except Exception:
                if i == 5:
                    raise
                time.sleep(2 * (i + 1))
        destino.write_bytes(datos)
        if Image:
            Image.open(destino).convert("RGBA").resize((300, 300), Image.LANCZOS).save(destino, optimize=True)
    return f"img/pokemon/{pid}.png"


def descargar_pokemon(item):
    url, gen_id = item
    pid = int(re.search(r"/pokemon-species/(\d+)/", url).group(1))
    fichero = CACHE / f"{pid}.json"
    if fichero.exists():
        d = json.loads(fichero.read_text())
    else:
        d = get_json(f"{API}/pokemon/{pid}")
        fichero.write_text(json.dumps(d))
    tipo = next(t["type"]["name"] for t in d["types"] if t["slot"] == 1)
    imagen = d["sprites"]["other"]["official-artwork"]["front_default"] or d["sprites"]["front_default"]
    if LOCALES:
        imagen = descargar_imagen(imagen, pid)
    return (pid, d["name"].capitalize(), d["height"] / 10, d["weight"] / 10, tipo, imagen, gen_id)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--imagenes-locales", action="store_true", help="descarga las imágenes a web/img/pokemon/")
    ap.add_argument("--gens", type=int, nargs="+", default=list(range(1, 10)))
    args = ap.parse_args()
    gens_pedidas = args.gens
    global LOCALES
    LOCALES = args.imagenes_locales
    IMG_DIR.mkdir(parents=True, exist_ok=True)
    CACHE.mkdir(exist_ok=True)

    generaciones, pendientes = [], []
    for g in gens_pedidas:
        d = get_json(f"{API}/generation/{g}")
        romano = d["name"].split("-")[1].upper()
        generaciones.append((g, f"Generación {romano}", d["main_region"]["name"].capitalize(),
                             len(d["pokemon_species"])))
        pendientes += [(s["url"], g) for s in d["pokemon_species"]]
        print(f"Generación {g}: {len(d['pokemon_species'])} Pokémon")

    with ThreadPoolExecutor(max_workers=4) as pool:
        pokemon = sorted(pool.map(descargar_pokemon, pendientes))

    with open(SALIDA, "w", encoding="utf-8") as f:
        f.write("-- Generado por scripts/descargar_pokeapi.py desde https://pokeapi.co\n")
        f.write("USE pokemon;\nSET NAMES utf8mb4;\n\n")
        f.write("INSERT INTO generacion (id, nombre, region, total_pokemon) VALUES\n")
        f.write(",\n".join("  (" + ", ".join(map(sql, g)) + ")" for g in generaciones) + ";\n\n")
        cols = "id, nombre, altura_m, peso_kg, tipo, imagen_url, generacion_id"
        for i in range(0, len(pokemon), 100):
            f.write(f"INSERT INTO pokemon ({cols}) VALUES\n")
            f.write(",\n".join("  (" + ", ".join(map(sql, p)) + ")" for p in pokemon[i:i + 100]) + ";\n\n")
    print(f"OK: {len(generaciones)} generaciones, {len(pokemon)} Pokémon -> {SALIDA}")


if __name__ == "__main__":
    main()
