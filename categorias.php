<?php
require 'includes/funcoes.php';
exigirPermissao('admin');
$db = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if ($ids) {
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        // Produtos dessa categoria ficam "sem categoria" (ON DELETE SET NULL no banco)
        $db->prepare("DELETE FROM categorias WHERE id IN ($marcadores)")->execute($ids);
        flash(count($ids) . ' categoria(s) removida(s).');
    } else {
        flash('Selecione ao menos uma categoria.', 'erro');
    }
    redirecionar('categorias.php');
}

$categorias = $db->query(
    'SELECT c.id, c.nome, COUNT(p.id) AS total
       FROM categorias c LEFT JOIN produtos p ON p.categoria_id = c.id
   GROUP BY c.id, c.nome ORDER BY c.nome'
)->fetchAll();

$titulo = 'Gerenciar categorias';
$menuAtivo = 'configuracoes';
require 'includes/cabecalho.php';
?>
<div class="cabecalho-admin">
    <a href="configuracoes.php" class="voltar">&lt; Voltar</a>
    <h1>Gerenciar categorias</h1>
</div>

<form method="post">
    <?= campoCsrf() ?>
    <div class="botoes-admin">
        <a href="categoria_nova.php" class="btn btn-contorno">Adicionar</a>
        <button class="btn btn-contorno" data-confirmar="Você confirma a remoção da(s) categoria(s) selecionada(s)?">Excluir</button>
    </div>

    <div class="painel lista-check">
        <h2 class="txt-azul">&#127991;&#65039; Categorias/Tags</h2>
        <?php foreach ($categorias as $c): ?>
            <label class="check">
                <input type="checkbox" name="ids[]" value="<?= $c['id'] ?>">
                <?= e($c['nome']) ?> <small>(<?= $c['total'] ?> produtos)</small>
            </label>
        <?php endforeach; ?>
        <?php if (!$categorias): ?><p class="vazio">Nenhuma categoria cadastrada.</p><?php endif; ?>
    </div>
</form>

<?php require 'includes/rodape.php'; ?>
