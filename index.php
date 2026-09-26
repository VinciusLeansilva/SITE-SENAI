<?php
require 'includes/funcoes.php';
$usuario = exigirLogin();
$db = conectar();

// Números dos cartões. query() é seguro aqui porque não há dados do usuário no SQL.
$totalItens    = (int) $db->query('SELECT COALESCE(SUM(estoque), 0) FROM produtos')->fetchColumn();
$totalProdutos = (int) $db->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$baixoEstoque  = (int) $db->query('SELECT COUNT(*) FROM produtos WHERE estoque <= estoque_minimo')->fetchColumn();

// Últimas 5 movimentações, juntando (JOIN) com produto e usuário para mostrar os nomes.
$movs = $db->query(
    'SELECT m.*, p.nome AS produto, u.nome AS usuario
       FROM movimentacoes m
       JOIN produtos p ON p.id = m.produto_id
  LEFT JOIN usuarios u ON u.id = m.usuario_id
   ORDER BY m.criado_em DESC
      LIMIT 5'
)->fetchAll();

$titulo = 'Início';
$menuAtivo = 'inicio';
require 'includes/cabecalho.php';
?>
<h1 class="titulo-grande">Bem vindo, <?= e($usuario['nome']) ?>!</h1>

<!-- A pesquisa envia para a lista de produtos (ou para Separação, se o cargo não vê Cadastros) -->
<form class="pesquisa" action="<?= pode('cadastros') ? 'produtos.php' : 'separacao.php' ?>" method="get">
    <input type="search" name="q" placeholder="Pesquisar..." aria-label="Pesquisar">
</form>

<section class="cards">
    <div class="card-info">
        <div class="card-icone azul">&#128230;</div>
        <div>
            <p class="card-titulo">Produtos em estoque</p>
            <p class="card-numero azul"><?= number_format($totalItens, 0, ',', '.') ?></p>
            <p class="card-sub">Total de itens disponíveis</p>
            <span class="selo selo-azul"><?= $totalProdutos ?> produtos cadastrados</span>
        </div>
    </div>

    <a class="card-info" href="<?= pode('cadastros') ? 'produtos.php?filtro=baixo' : '#' ?>">
        <div class="card-icone laranja">&#9888;</div>
        <div>
            <p class="card-titulo">Itens com baixo estoque</p>
            <p class="card-numero laranja"><?= $baixoEstoque ?></p>
            <p class="card-sub">Precisam de atenção</p>
            <span class="selo selo-laranja">estoque &le; mínimo</span>
        </div>
    </a>
</section>

<div class="tabela-box">
    <table class="tabela">
        <thead>
            <tr><th>Data/Hora</th><th>Tipo</th><th>Produto</th><th>Quantidade</th><th>Usuário</th></tr>
        </thead>
        <tbody>
        <?php foreach ($movs as $m): ?>
            <?php $entrada = $m['tipo'] === 'entrada'; ?>
            <tr>
                <td data-rotulo="Data/Hora"><?= dataBR($m['criado_em'], true) ?></td>
                <td data-rotulo="Tipo">
                    <span class="selo <?= $entrada ? 'selo-verde' : 'selo-azul' ?>">
                        <?= $entrada ? '&darr; Entrada' : '&uarr; Saída' ?>
                    </span>
                </td>
                <td data-rotulo="Produto"><?= e($m['produto']) ?></td>
                <td data-rotulo="Quantidade">
                    <span class="<?= $entrada ? 'txt-verde' : 'txt-vermelho' ?>"><?= ($entrada ? '+' : '-') . $m['quantidade'] ?> un.</span>
                </td>
                <td data-rotulo="Usuário"><?= e($m['usuario'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$movs): ?>
            <tr><td colspan="5">Nenhuma movimentação ainda.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/rodape.php'; ?>
