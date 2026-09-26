<?php
/*
 * Vendas: cria um pedido (nota) que vai para a fila de Separação.
 * O protótipo não tinha esta tela, então ela foi feita no mesmo estilo.
 */
require 'includes/funcoes.php';
exigirPermissao('vendas');
$db = conectar();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $numero         = trim($_POST['numero'] ?? '');
    $destinatario   = trim($_POST['destinatario'] ?? '');
    $transportadora = trim($_POST['transportadora'] ?? '');
    $dataEmissao    = $_POST['data_emissao'] ?? date('Y-m-d');
    $produtoId      = (int) ($_POST['produto_id'] ?? 0);
    $quantidade     = (int) ($_POST['quantidade'] ?? 0);

    if ($numero === '' || $destinatario === '' || $transportadora === '') $erros[] = 'Preencha todos os campos.';
    if ($quantidade < 1) $erros[] = 'A quantidade deve ser pelo menos 1.';

    $stmt = $db->prepare('SELECT id FROM pedidos WHERE numero = ?');
    $stmt->execute([$numero]);
    if ($stmt->fetch()) $erros[] = 'Já existe um pedido com este número.';

    if (!$erros) {
        // Transação: ou grava o pedido E o item, ou não grava nada.
        $db->beginTransaction();
        $db->prepare('INSERT INTO pedidos (numero, destinatario, transportadora, data_emissao) VALUES (?, ?, ?, ?)')
           ->execute([$numero, $destinatario, $transportadora, $dataEmissao]);
        $pedidoId = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade) VALUES (?, ?, ?)')
           ->execute([$pedidoId, $produtoId, $quantidade]);
        $db->commit();

        flash('Pedido ' . $numero . ' criado e enviado para separação.');
        redirecionar('vendas.php');
    }
}

$produtos = $db->query("SELECT id, nome, sku, estoque FROM produtos WHERE status = 'publicado' ORDER BY nome")->fetchAll();
$pedidos  = $db->query('SELECT * FROM pedidos ORDER BY criado_em DESC')->fetchAll();

$titulo = 'Vendas';
$menuAtivo = 'vendas';
require 'includes/cabecalho.php';
?>
<h1 class="titulo-pagina">VENDAS</h1>

<?php foreach ($erros as $erro): ?>
    <p class="alerta-erro"><?= e($erro) ?></p>
<?php endforeach; ?>

<form method="post" class="painel form-grade">
    <?= campoCsrf() ?>
    <h2>Novo pedido</h2>
    <label>Nº do pedido <input name="numero" required></label>
    <label>Destinatário <input name="destinatario" required></label>
    <label>Transportadora <input name="transportadora" value="Correio" required></label>
    <label>Data de emissão <input type="date" name="data_emissao" value="<?= date('Y-m-d') ?>" required></label>
    <label>Produto
        <select name="produto_id" required>
            <?php foreach ($produtos as $p): ?>
                <option value="<?= $p['id'] ?>"><?= e($p['nome']) ?> (<?= e($p['sku']) ?>) — <?= $p['estoque'] ?> em estoque</option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Quantidade <input type="number" name="quantidade" min="1" value="1" required></label>
    <button class="btn btn-azul">Criar pedido</button>
</form>

<div class="tabela-box">
    <table class="tabela">
        <thead><tr><th>Nº Pedido</th><th>Destinatário</th><th>Data</th><th>Transportadora</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td data-rotulo="Nº Pedido"><?= e($p['numero']) ?></td>
                <td data-rotulo="Destinatário"><?= e($p['destinatario']) ?></td>
                <td data-rotulo="Data"><?= dataBR($p['data_emissao']) ?></td>
                <td data-rotulo="Transportadora"><?= e($p['transportadora']) ?></td>
                <td data-rotulo="Status"><span class="status status-<?= $p['status'] ?>"><?= STATUS_PEDIDO[$p['status']] ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/rodape.php'; ?>
