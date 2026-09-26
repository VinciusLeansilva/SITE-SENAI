/*
 * Service Worker do StockControl.
 *
 * O Service Worker é um script que o navegador roda "por trás" do site.
 * Aqui ele faz duas coisas:
 *   1. Guarda em cache os arquivos que não mudam (CSS, JS, imagens),
 *      deixando o app mais rápido.
 *   2. Se a internet cair, mostra a página offline.html em vez de erro.
 *
 * As páginas PHP NÃO ficam em cache, porque mostram dados do banco
 * que mudam o tempo todo.
 *
 * Dica: ao alterar CSS/JS, mude a VERSAO abaixo para o app baixar de novo.
 */
const VERSAO = 'stockcontrol-v1';

const ARQUIVOS_FIXOS = [
    'assets/css/style.css',
    'assets/js/app.js',
    'assets/img/logo.png',
    'assets/icons/icon-192.png',
    'assets/icons/icon-512.png',
    'offline.html',
];

// Instalação: baixa e guarda os arquivos fixos
self.addEventListener('install', (evento) => {
    evento.waitUntil(caches.open(VERSAO).then((cache) => cache.addAll(ARQUIVOS_FIXOS)));
    self.skipWaiting();
});

// Ativação: apaga caches de versões antigas
self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys().then((nomes) =>
            Promise.all(nomes.filter((n) => n !== VERSAO).map((n) => caches.delete(n)))
        )
    );
    self.clients.claim();
});

// Cada requisição do site passa por aqui
self.addEventListener('fetch', (evento) => {
    const req = evento.request;
    if (req.method !== 'GET') return; // formulários (POST) vão direto para o servidor

    // Páginas: tenta a internet; se falhar, mostra offline.html
    if (req.mode === 'navigate') {
        evento.respondWith(fetch(req).catch(() => caches.match('offline.html')));
        return;
    }

    // Arquivos fixos: usa o cache se tiver; senão busca na internet
    evento.respondWith(caches.match(req).then((resp) => resp || fetch(req)));
});
