    </main>
</div>

<!--
    Janela de confirmação reutilizável (usada por Excluir, Remover etc.).
    O JavaScript (app.js) preenche o texto e mostra a janela.
-->
<div class="modal-fundo" id="modalConfirmar" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
        <button type="button" class="modal-x" data-fechar aria-label="Fechar">&times;</button>
        <h3 id="modalTitulo">Confirmação</h3>
        <p id="modalTexto"></p>
        <div class="modal-botoes">
            <button type="button" class="btn btn-verde" id="modalSim">Confirmar</button>
            <button type="button" class="btn btn-vermelho" data-fechar>Cancelar</button>
        </div>
    </div>
</div>
</body>
</html>
