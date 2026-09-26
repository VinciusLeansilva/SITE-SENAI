<?php
require 'includes/funcoes.php';
exigirPermissao('separacao');
$db = conectar();

// Qual "aba" está aberta: em_separacao, separado ou pronto_envio
$abas   = ['em_separacao' => 'Em separação', 'separado' => 'Separado', 'pronto_envio' => 'Pronto para envio'];
$status = array_key_exists($_GET['status'] ?? '', $abas) ? $_GET['status'] : 'em_separacao';
$q      = trim($_GET['q'] ?? '');

// Conta quantos pedidos há em cada status (GROUP BY agrupa as linhas por status)
$contagem = array_fill_keys(array_keys($abas), 0);
foreach ($db->query('SELECT status, COUNT(*) AS total FROM pedidos GROUP BY status') as $linha) {
    $contagem[$linha['status']] = (int) $linha['total'];
}

$sql    = 'SELECT * FROM pedidos WHERE status = ?';
$params = [$status];
if ($q !== '') {
    $sql     .= ' AND (numero LIKE ? OR destinatario LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$stmt = $db->prepare($sql . ' ORDER BY data_emissao');
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

// Itens de cada pedido (para a lista que abre ao clicar no ☰)
$itensStmt = $db->prepare(
    'SELECT i.quantidade, p.sku, p.nome, p.localizacao, c.nome AS categoria
       FROM pedido_itens i
       JOIN produtos p ON p.id = i.produto_id
  LEFT JOIN categorias c ON c.id = p.categoria_id
      WHERE i.pedido_id = ?'
);

$titulo = 'Separação';
$menuAtivo = 'separacao';
require 'includes/cabecalho.php';
?>
<form class="pesquisa" method="get">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar..." aria-label="Pesquisar pedido">
</form>

<div class="abas">
    <?php foreach ($abas as $chave => $texto): ?>
        <a href="?status=<?= $chave ?>" class="<?= $status === $chave ? 'ativo' : '' ?>">
            <?= $texto ?><strong><?= $contagem[$chave] ?></strong>
        </a>
    <?php endforeach; ?>
</div>

<div class="lista-pedidos">
    <?php foreach ($pedidos as $ped): ?>
        <?php $itensStmt->execute([$ped['id']]); $itens = $itensStmt->fetchAll(); ?>
        <!-- <details> abre e fecha sozinho, sem precisar de JavaScript -->
        <details class="pedido">
            <summary>
                <span class="hamburguer" aria-hidden="true">&#9776;</span>
                <span><small>Nº Pedido</small><?= e($ped['numero']) ?></span>
                <span><small>Data</small><?= dataBR($ped['data_emissao']) ?></span>
                <span><small>Transportadora</small><?= e($ped['transportadora']) ?></span>
            </summary>
            <div class="pedido-corpo">
                <h3><?= e($ped['destinatario']) ?></h3>
                <table class="tabela tabela-mini">
                    <thead><tr><th>Código</th><th>Produto</th><th>Categoria</th><th>Quantidade</th><th>Localização</th></tr></thead>
                    <tbody>
                    <?php foreach ($itens as $it): ?>
                        <tr>
                            <td data-rotulo="Código"><?= e($it['sku']) ?></td>
                            <td data-rotulo="Produto"><?= e($it['nome']) ?></td>
                            <td data-rotulo="Categoria"><?= e($it['categoria'] ?? '—') ?></td>
                            <td data-rotulo="Quantidade"><?= $it['quantidade'] ?></td>
                            <td data-rotulo="Localização"><?= e($it['localizacao'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a class="btn btn-azul" href="pedido.php?id=<?= $ped['id'] ?>">Abrir dados da nota &rarr;</a>
            </div>
        </details>
    <?php endforeach; ?>

    <?php if (!$pedidos): ?>
        <p class="vazio">Nenhum pedido nesta etapa.</p>
    <?php endif; ?>
</div>

<?php require 'includes/rodape.php'; ?>
