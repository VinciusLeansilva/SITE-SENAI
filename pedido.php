<?php
/*
 * "Dados da nota": mostra um pedido e permite
 *   - Separar pedido        (baixa o estoque e muda o status)
 *   - Atualizar volumes     (informa quantas caixas; se já separado -> pronto para envio)
 *   - Remover da separação  (tira um item do pedido)
 */
require 'includes/funcoes.php';
exigirPermissao('separacao');
$db = conectar();

$id = (int) ($_GET['id'] ?? 0);

function buscarPedido(PDO $db, int $id): ?array
{
    $stmt = $db->prepare('SELECT * FROM pedidos WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

$pedido = buscarPedido($db, $id);
if (!$pedido) {
    flash('Pedido não encontrado.', 'erro');
    redirecionar('separacao.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'separar') {
        if ($pedido['status'] !== 'em_separacao') {
            flash('Este pedido já foi separado.', 'erro');
        } else {
            // Busca os itens e confere se há estoque suficiente de todos.
            $stmt = $db->prepare(
                'SELECT i.produto_id, i.quantidade, p.nome, p.estoque
                   FROM pedido_itens i JOIN produtos p ON p.id = i.produto_id
                  WHERE i.pedido_id = ?'
            );
            $stmt->execute([$id]);
            $itens = $stmt->fetchAll();

            $faltando = array_filter($itens, fn($i) => $i['estoque'] < $i['quantidade']);

            if (!$itens) {
                flash('O pedido não tem itens.', 'erro');
            } elseif ($faltando) {
                $nomes = implode(', ', array_column($faltando, 'nome'));
                flash('Estoque insuficiente para: ' . $nomes, 'erro');
            } else {
                // Transação: se algo der errado no meio, nada é gravado.
                $db->beginTransaction();
                $baixar = $db->prepare('UPDATE produtos SET estoque = estoque - ? WHERE id = ?');
                foreach ($itens as $i) {
                    $baixar->execute([$i['quantidade'], $i['produto_id']]);
                    registrarMovimentacao((int) $i['produto_id'], 'saida', (int) $i['quantidade']);
                }
                // Se os volumes já foram informados, pula direto para "pronto para envio".
                $novoStatus = $pedido['volumes'] ? 'pronto_envio' : 'separado';
                $db->prepare('UPDATE pedidos SET status = ? WHERE id = ?')->execute([$novoStatus, $id]);
                $db->commit();
                flash('Pedido separado com sucesso.');
            }
        }
    }

    if ($acao === 'volumes') {
        $volumes = (int) ($_POST['volumes'] ?? 0);
        if ($volumes < 1) {
            flash('Informe um número de volumes maior que zero.', 'erro');
        } else {
            $novoStatus = $pedido['status'] === 'separado' ? 'pronto_envio' : $pedido['status'];
            $db->prepare('UPDATE pedidos SET volumes = ?, status = ? WHERE id = ?')->execute([$volumes, $novoStatus, $id]);
            flash('Volumes atualizados.');
        }
    }

    if ($acao === 'remover_item') {
        if ($pedido['status'] !== 'em_separacao') {
            flash('Só é possível remover itens de pedidos aguardando separação.', 'erro');
        } else {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $db->prepare('DELETE FROM pedido_itens WHERE id = ? AND pedido_id = ?')->execute([$itemId, $id]);

            // Se o pedido ficou vazio, ele sai da separação.
            $stmt = $db->prepare('SELECT COUNT(*) FROM pedido_itens WHERE pedido_id = ?');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() === 0) {
                $db->prepare('DELETE FROM pedidos WHERE id = ?')->execute([$id]);
                flash('Item removido. O pedido ficou vazio e saiu da separação.');
                redirecionar('separacao.php');
            }
            flash('Item removido da separação.');
        }
    }

    // Padrão PRG (Post/Redirect/Get): evita reenviar o formulário ao apertar F5.
    redirecionar('pedido.php?id=' . $id);
}

$stmt = $db->prepare(
    'SELECT i.id, i.quantidade, p.nome, p.sku
       FROM pedido_itens i JOIN produtos p ON p.id = i.produto_id
      WHERE i.pedido_id = ?'
);
$stmt->execute([$id]);
$itens = $stmt->fetchAll();

$titulo = 'Pedido ' . $pedido['numero'];
$menuAtivo = 'separacao';
require 'includes/cabecalho.php';
?>
<a href="separacao.php?status=<?= e($pedido['status']) ?>" class="voltar">&larr; Voltar</a>

<h1 class="titulo-nota">Dados da nota</h1>
<span class="status status-<?= $pedido['status'] ?>"><?= STATUS_PEDIDO[$pedido['status']] ?></span>

<dl class="dados-nota">
    <div><dt>Destinatário</dt><dd><?= e($pedido['destinatario']) ?></dd></div>
    <div><dt>Número</dt><dd><?= e($pedido['numero']) ?></dd></div>
    <div><dt>Data de emissão</dt><dd><?= dataBR($pedido['data_emissao']) ?></dd></div>
    <div><dt>Volumes</dt><dd><?= $pedido['volumes'] ?: '—' ?></dd></div>
</dl>

<h2 class="subtitulo">Itens da nota</h2>
<div class="tabela-box escura sem-corte">
    <table class="tabela">
        <thead><tr><th></th><th>Produto</th><th>Cód.(SKU)</th><th>Quantidade</th><th>Transportadora</th></tr></thead>
        <tbody>
        <?php foreach ($itens as $it): ?>
            <tr>
                <td>
                    <!-- Menu de ações: <details> abre/fecha ao clicar no ☰ -->
                    <details class="menu-acoes">
                        <summary aria-label="Ações">&#9776;</summary>
                        <div class="menu-acoes-lista">
                            <form method="post">
                                <?= campoCsrf() ?>
                                <button name="acao" value="separar" <?= $pedido['status'] !== 'em_separacao' ? 'disabled' : '' ?>>&#128230; Separar pedido</button>
                            </form>
                            <button type="button" data-abrir="modalVolumes">&#128260; Atualizar volumes</button>
                            <form method="post">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                <button name="acao" value="remover_item" class="txt-azul"
                                        data-confirmar="Você confirma a remoção do item selecionado da separação?"
                                        <?= $pedido['status'] !== 'em_separacao' ? 'disabled' : '' ?>>&#128465; Remover da separação</button>
                            </form>
                        </div>
                    </details>
                </td>
                <td data-rotulo="Produto"><?= e($it['nome']) ?></td>
                <td data-rotulo="SKU"><?= e($it['sku']) ?></td>
                <td data-rotulo="Quantidade"><?= $it['quantidade'] ?> Un</td>
                <td data-rotulo="Transportadora"><?= e($pedido['transportadora']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Janela "Número de volumes" (tela 14 do protótipo) -->
<div class="modal-fundo" id="modalVolumes" hidden>
    <form method="post" class="modal" role="dialog" aria-modal="true" aria-labelledby="tituloVolumes">
        <?= campoCsrf() ?>
        <button type="button" class="modal-x" data-fechar aria-label="Fechar">&times;</button>
        <h3 id="tituloVolumes">Número de volumes</h3>
        <input type="number" name="volumes" min="1" placeholder="Informar volumes..." value="<?= e($pedido['volumes']) ?>" required>
        <div class="modal-botoes">
            <button name="acao" value="volumes" class="btn btn-azul">Confirmar</button>
            <button type="button" class="btn btn-cinza" data-fechar>Cancelar</button>
        </div>
    </form>
</div>

<?php require 'includes/rodape.php'; ?>
