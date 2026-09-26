<?php
require 'includes/funcoes.php';
exigirPermissao('suporte');

$titulo = 'Suporte';
$menuAtivo = 'suporte';
require 'includes/cabecalho.php';
?>
<h1 class="titulo-pagina">SUPORTE &#128172;</h1>

<div class="painel faq">
    <h2>Perguntas frequentes</h2>

    <details>
        <summary>Como cadastro um produto?</summary>
        <p>Vá em <strong>Cadastros &rarr; -Novo Produto</strong>, preencha os campos e clique em <strong>Salvar</strong>.
           Se ainda não terminou, use <strong>Salvar como rascunho</strong>.</p>
    </details>

    <details>
        <summary>Como separo um pedido?</summary>
        <p>Em <strong>Separação</strong>, clique no pedido e depois em <strong>Abrir dados da nota</strong>.
           No &#9776; do item escolha <strong>Separar pedido</strong>. O estoque é baixado automaticamente.</p>
    </details>

    <details>
        <summary>Quando o pedido fica "pronto para envio"?</summary>
        <p>Depois de separado, informe o <strong>número de volumes</strong> (caixas). Aí ele aparece
           em <strong>Expedição</strong> para ser marcado como enviado.</p>
    </details>

    <details>
        <summary>Esqueci minha senha</summary>
        <p>Peça ao Gerente para redefinir em <strong>Configurações &rarr; Gerenciar usuários &rarr; editar</strong>.</p>
    </details>

    <h2>Contato</h2>
    <p>Grupo: Vitor Pereira, Felipe Ecel, Gustavo G — SENAI, Técnico em Informática.</p>
</div>

<?php require 'includes/rodape.php'; ?>
