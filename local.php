<?php
require 'includes/funcoes.php';
exigirPermissao('cadastros');

$q = trim($_GET['q'] ?? '');
$resultados = [];

if ($q !== '') {
    $stmt = conectar()->prepare(
        'SELECT nome, sku, cod_barras, localizacao FROM produtos
          WHERE sku LIKE ? OR cod_barras LIKE ? OR nome LIKE ?
          ORDER BY nome LIMIT 20'
    );
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like, $like]);
    $resultados = $stmt->fetchAll();
}

$titulo = 'Local do produto';
$menuAtivo = 'cadastros';
$subAtivo = 'local';
require 'includes/cabecalho.php';
?>
<h1 class="titulo-pagina">LOCAL PRODUTO &#128230;</h1>
<p class="destaque">Ache qualquer produto do estoque bem aqui: localizamos o produto e mostramos onde ele se encontra.</p>

<form class="barra-pesquisa" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="&#128269; SKU, código de barras ou nome" autofocus>
    <button class="btn btn-branco">Localizar</button>
</form>

<?php if ($q !== ''): ?>
    <div class="tabela-box">
        <table class="tabela">
            <thead><tr><th>Produto</th><th>SKU</th><th>Cod. Barras</th><th>Local do produto no estoque</th></tr></thead>
            <tbody>
            <?php foreach ($resultados as $r): ?>
                <tr>
                    <td data-rotulo="Produto"><?= e($r['nome']) ?></td>
                    <td data-rotulo="SKU"><?= e($r['sku']) ?></td>
                    <td data-rotulo="Cod. Barras"><?= e($r['cod_barras']) ?></td>
                    <td data-rotulo="Local"><strong><?= e($r['localizacao'] ?: 'Não informado') ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$resultados): ?>
                <tr><td colspan="4">Nenhum produto encontrado para "<?= e($q) ?>".</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require 'includes/submenu_cadastros.php'; ?>
<?php require 'includes/rodape.php'; ?>
