// Tema claro/oscuro. Se carga en <head> (sin defer) para aplicar el tema guardado antes de pintar.
(() => {
  const raiz = document.documentElement;
  const guardado = (() => { try { return localStorage.getItem('tema'); } catch { return null; } })();
  if (guardado === 'light' || guardado === 'dark') raiz.dataset.tema = guardado;

  const actual = () => raiz.dataset.tema || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

  document.addEventListener('DOMContentLoaded', () => {
    const boton = document.getElementById('tema-boton');
    if (!boton) return;
    const pintar = () => {
      const oscuro = actual() === 'dark';
      boton.setAttribute('aria-pressed', String(oscuro));
      boton.textContent = oscuro ? '☀️' : '🌙';
      boton.setAttribute('aria-label', oscuro ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
    };
    boton.addEventListener('click', () => {
      const nuevo = actual() === 'dark' ? 'light' : 'dark';
      raiz.dataset.tema = nuevo;
      try { localStorage.setItem('tema', nuevo); } catch { /* sin almacenamiento: solo esta visita */ }
      pintar();
    });
    pintar();
  });
})();
