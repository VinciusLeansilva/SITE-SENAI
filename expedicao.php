<?php
/*
 * Expedição: pedidos que já foram separados e estão prontos para sair.
 * (O protótipo não tinha esta tela, então ela segue o estilo da Separação.)
 */
require 'includes/funcoes.php';
exigirPermissao('expedicao');
$db = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    // Só muda para "enviado" se o pedido estiver "pronto para envio".
    $stmt = $db->prepare("UPDATE pedidos SET status = 'enviado' WHERE id = ? AND status = 'pronto_envio'");
    $stmt->execute([(int) ($_POST['id'] ?? 0)]);
    flash($stmt->rowCount() ? 'Pedido marcado como enviado.' : 'Não foi possível enviar este pedido.', $stmt->rowCount() ? 'sucesso' : 'erro');
    redirecionar('expedicao.php');
}

$pedidos = $db->query(
    "SELECT * FROM pedidos WHERE status IN ('separado', 'pronto_envio', 'enviado')
      ORDER BY FIELD(status, 'pronto_envio', 'separado', 'enviado'), data_emissao DESC"
)->fetchAll();

$titulo = 'Expedição';
$menuAtivo = 'expedicao';
require 'includes/cabecalho.php';
?>
<h1 class="titulo-pagina">EXPEDIÇÃO &#128666;</h1>

<div class="tabela-box escura">
    <table class="tabela">
        <thead><tr><th>Nº Pedido</th><th>Destinatário</th><th>Transportadora</th><th>Volumes</th><th>Status</th><th>Ação</th></tr></thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td data-rotulo="Nº Pedido"><?= e($p['numero']) ?></td>
                <td data-rotulo="Destinatário"><?= e($p['destinatario']) ?></td>
                <td data-rotulo="Transportadora"><?= e($p['transportadora']) ?></td>
                <td data-rotulo="Volumes"><?= $p['volumes'] ?: '—' ?></td>
                <td data-rotulo="Status"><span class="status status-<?= $p['status'] ?>"><?= STATUS_PEDIDO[$p['status']] ?></span></td>
                <td>
                    <?php if ($p['status'] === 'pronto_envio'): ?>
                        <form method="post">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="btn btn-verde" data-confirmar="Confirmar o envio do pedido <?= e($p['numero']) ?>?">Marcar como enviado</button>
                        </form>
                    <?php elseif ($p['status'] === 'separado'): ?>
                        <a href="pedido.php?id=<?= $p['id'] ?>" class="btn btn-cinza">Informar volumes</a>
                    <?php else: ?>
                        &#10004;
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$pedidos): ?>
            <tr><td colspan="6">Nenhum pedido separado ainda.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/rodape.php'; ?>
