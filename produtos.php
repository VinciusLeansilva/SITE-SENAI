<?php
require 'includes/funcoes.php';
exigirPermissao('cadastros');

// Filtros vindos da URL (?q=...&status=...&filtro=baixo)
$q      = trim($_GET['q'] ?? '');
$status = ($_GET['status'] ?? '') === 'rascunho' ? 'rascunho' : 'publicado';
$baixo  = ($_GET['filtro'] ?? '') === 'baixo';

// Montamos o WHERE aos poucos, sempre com "?" no lugar dos valores.
$where  = [];
$params = [];

if ($baixo) {
    $where[] = 'estoque <= estoque_minimo';
} else {
    $where[]  = 'status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[]  = '(nome LIKE ? OR sku LIKE ? OR cod_barras LIKE ?)';
    $like     = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}

$sql  = 'SELECT * FROM produtos WHERE ' . implode(' AND ', $where) . ' ORDER BY nome';
$stmt = conectar()->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

if ($baixo) {
    $tituloPagina = 'ESTOQUE BAIXO';
    $subAtivo = 'estoque';
} elseif ($status === 'rascunho') {
    $tituloPagina = 'RASCUNHOS';
    $subAtivo = 'rascunhos';
} else {
    $tituloPagina = 'PRODUTOS CADASTRADOS';
    $subAtivo = 'feitos';
}

$titulo = 'Produtos';
$menuAtivo = 'cadastros';
require 'includes/cabecalho.php';
?>
<h1 class="titulo-pagina"><?= $tituloPagina ?> &#128722;</h1>

<form class="barra-pesquisa" method="get">
    <?php if ($status === 'rascunho'): ?><input type="hidden" name="status" value="rascunho"><?php endif; ?>
    <?php if ($baixo): ?><input type="hidden" name="filtro" value="baixo"><?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nome, SKU ou código de barras">
    <button class="btn btn-branco">&#128269; Pesquisa</button>
</form>

<div class="lista-produtos">
    <?php foreach ($produtos as $p): ?>
        <a class="linha-produto" href="produto.php?id=<?= $p['id'] ?>">
            <div><span>Produto:</span><strong><?= e($p['nome']) ?></strong></div>
            <div><span>SKU:</span><strong><?= e($p['sku']) ?></strong></div>
            <div><span>Preço:</span><strong><?= dinheiro($p['preco']) ?></strong></div>
            <div><span>Estoque:</span>
                <strong class="<?= $p['estoque'] <= $p['estoque_minimo'] ? 'txt-vermelho' : '' ?>"><?= $p['estoque'] ?> un.</strong>
            </div>
        </a>
    <?php endforeach; ?>

    <?php if (!$produtos): ?>
        <p class="vazio">Nenhum produto encontrado.</p>
    <?php endif; ?>
</div>

<?php require 'includes/submenu_cadastros.php'; ?>
<?php require 'includes/rodape.php'; ?>
