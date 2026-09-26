<?php
/*
 * Links "-Feitos  -Rascunhos  -Novo Produto ..." que aparecem
 * embaixo das telas de Cadastros no protótipo.
 * Defina $subAtivo antes de incluir para destacar o link atual.
 */
$subAtivo = $subAtivo ?? '';
$links = [
    'feitos'    => ['Feitos',        'produtos.php'],
    'rascunhos' => ['Rascunhos',     'produtos.php?status=rascunho'],
    'novo'      => ['Novo Produto',  'produto.php'],
    'estoque'   => ['Estoque',       'produtos.php?filtro=baixo'],
    'local'     => ['Local Produto', 'local.php'],
    'categorias'=> ['Categorias',    'categorias.php'],
    'ajuda'     => ['Pedir ajuda',   'suporte.php'],
];
?>
<nav class="submenu">
    <?php foreach ($links as $chave => [$texto, $url]): ?>
        <?php if ($chave === 'categorias' && !pode('admin')) continue; ?>
        <a href="<?= $url ?>" class="<?= $subAtivo === $chave ? 'ativo' : '' ?>">-<?= $texto ?></a>
    <?php endforeach; ?>
</nav>
