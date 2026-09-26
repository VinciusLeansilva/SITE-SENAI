/*
 * StockControl - JavaScript do site (JS puro, sem bibliotecas).
 * Este arquivo é carregado com "defer", ou seja, só roda depois
 * que o HTML inteiro foi lido. Por isso podemos buscar elementos direto.
 */

/* ------------------------------------------------------------
 * 1) Tema claro/escuro
 *    Guardamos a escolha no localStorage (memória do navegador).
 * ---------------------------------------------------------- */
const chaveTema = document.getElementById('trocarTema');
if (chaveTema) {
    chaveTema.checked = document.documentElement.dataset.theme !== 'claro';
    chaveTema.addEventListener('change', () => {
        const tema = chaveTema.checked ? 'escuro' : 'claro';
        document.documentElement.dataset.theme = tema;
        try { localStorage.setItem('tema', tema); } catch (e) { /* navegador bloqueou: tudo bem */ }
    });
}

/* ------------------------------------------------------------
 * 2) Botão ☰ abre/fecha o menu lateral no celular
 * ---------------------------------------------------------- */
const btnMenu = document.getElementById('btnMenu');
const sidebar = document.getElementById('sidebar');
if (btnMenu && sidebar) {
    btnMenu.addEventListener('click', () => sidebar.classList.toggle('aberta'));
}

/* ------------------------------------------------------------
 * 3) Botão de "olho" para mostrar/esconder a senha
 * ---------------------------------------------------------- */
document.querySelectorAll('.ver-senha').forEach((botao) => {
    botao.addEventListener('click', () => {
        const campo = document.getElementById(botao.dataset.alvo);
        campo.type = campo.type === 'password' ? 'text' : 'password';
    });
});

/* ------------------------------------------------------------
 * 4) Janelas (modais)
 *    - abrirModal / fecharModal mostram e escondem
 *    - qualquer elemento com data-fechar fecha a janela
 *    - a tecla ESC fecha a janela aberta
 * ---------------------------------------------------------- */
function abrirModal(modal) {
    modal.hidden = false;
    const primeiro = modal.querySelector('input, button');
    if (primeiro) primeiro.focus();
}

function fecharModal(modal) {
    modal.hidden = true;
}

document.querySelectorAll('.modal-fundo').forEach((fundo) => {
    fundo.querySelectorAll('[data-fechar]').forEach((b) => b.addEventListener('click', () => fecharModal(fundo)));
    // Clicar fora da caixa (no fundo escuro) também fecha
    fundo.addEventListener('click', (ev) => { if (ev.target === fundo) fecharModal(fundo); });
});

document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') {
        document.querySelectorAll('.modal-fundo:not([hidden])').forEach(fecharModal);
    }
});

// Botões com data-abrir="idDoModal" abrem aquele modal
document.querySelectorAll('[data-abrir]').forEach((botao) => {
    botao.addEventListener('click', () => {
        abrirModal(document.getElementById(botao.dataset.abrir));
        botao.closest('details')?.removeAttribute('open'); // fecha o menu ☰, se houver
    });
});

/* ------------------------------------------------------------
 * 5) Confirmação antes de ações perigosas
 *    Qualquer botão com data-confirmar="pergunta" mostra a janela
 *    de confirmação. Só se o usuário clicar em "Confirmar" o
 *    formulário é enviado.
 * ---------------------------------------------------------- */
const modalConfirmar = document.getElementById('modalConfirmar');
let botaoPendente = null;

document.querySelectorAll('[data-confirmar]').forEach((botao) => {
    botao.addEventListener('click', (ev) => {
        if (!modalConfirmar) return;          // sem modal na página: envia normal
        const form = botao.form;
        if (form && !form.reportValidity()) { // campos obrigatórios vazios? mostra o aviso do navegador
            ev.preventDefault();
            return;
        }
        ev.preventDefault();                  // segura o envio
        botaoPendente = botao;
        document.getElementById('modalTexto').textContent = botao.dataset.confirmar;
        abrirModal(modalConfirmar);
    });
});

if (modalConfirmar) {
    document.getElementById('modalSim').addEventListener('click', () => {
        fecharModal(modalConfirmar);
        if (botaoPendente && botaoPendente.form) {
            // requestSubmit(botao) envia o form INCLUINDO o name/value do botão
            botaoPendente.form.requestSubmit(botaoPendente);
        }
        botaoPendente = null;
    });
}

/* ------------------------------------------------------------
 * 6) Aviso (toast) some sozinho depois de 5 segundos
 * ---------------------------------------------------------- */
document.querySelectorAll('.toast').forEach((toast) => {
    toast.querySelector('.toast-fechar')?.addEventListener('click', () => toast.remove());
    setTimeout(() => toast.remove(), 5000);
});

/* ------------------------------------------------------------
 * 7) PWA: registra o Service Worker, que permite instalar o site
 *    como aplicativo e mostrar uma página quando estiver offline.
 * ---------------------------------------------------------- */
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js').catch((erro) => {
        console.warn('Service Worker não registrado:', erro);
    });
}
