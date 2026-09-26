<?php
require 'includes/funcoes.php';
exigirPermissao('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    // Recebe vários nomes (name="nomes[]") e ignora os campos vazios.
    $nomes = array_filter(array_map('trim', $_POST['nomes'] ?? []));

    // INSERT IGNORE: se a categoria já existir (UNIQUE), apenas pula.
    $stmt = conectar()->prepare('INSERT IGNORE INTO categorias (nome) VALUES (?)');
    $adicionadas = 0;
    foreach ($nomes as $nome) {
        $stmt->execute([$nome]);
        $adicionadas += $stmt->rowCount();
    }

    flash($adicionadas . ' categoria(s) adicionada(s).', $adicionadas ? 'sucesso' : 'erro');
    redirecionar('categorias.php');
}

$titulo = 'Adicionar categoria';
$menuAtivo = 'configuracoes';
require 'includes/cabecalho.php';
?>
<div class="cabecalho-admin">
    <a href="categorias.php" class="voltar">&lt; Voltar</a>
    <h1>Adicionar categoria</h1>
</div>

<form method="post" class="painel form-admin">
    <?= campoCsrf() ?>
    <?php for ($i = 1; $i <= 3; $i++): ?>
        <label for="cat<?= $i ?>" class="rotulo-grande">Nome da categoria</label>
        <div class="campo-icone"><span>&#127991;&#65039;</span>
            <input id="cat<?= $i ?>" name="nomes[]" placeholder="Digite a categoria" <?= $i === 1 ? 'required' : '' ?>>
        </div>
    <?php endfor; ?>
    <button class="btn btn-verde" data-confirmar="Você deseja adicionar as categorias digitadas?">Adicionar</button>
</form>

<?php require 'includes/rodape.php'; ?>
