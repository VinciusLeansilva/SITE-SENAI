<?php
require 'includes/funcoes.php';
$eu = exigirPermissao('admin');
$db = conectar();

// Excluir os usuários marcados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    // array_map('intval') garante que só números entrem na lista
    $ids = array_map('intval', $_POST['ids'] ?? []);
    // O gerente não pode excluir a si mesmo (senão ficaria sem acesso)
    $ids = array_values(array_diff($ids, [$eu['id']]));

    if ($ids) {
        // Cria "?, ?, ?" com a mesma quantidade de ids
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM usuarios WHERE id IN ($marcadores)")->execute($ids);
        flash(count($ids) . ' usuário(s) excluído(s).');
    } else {
        flash('Selecione ao menos um usuário (você não pode excluir a si mesmo).', 'erro');
    }
    redirecionar('usuarios.php');
}

$usuarios = $db->query('SELECT id, nome, email, cargo FROM usuarios ORDER BY nome')->fetchAll();

$titulo = 'Gerenciar usuários';
$menuAtivo = 'configuracoes';
require 'includes/cabecalho.php';
?>
<div class="cabecalho-admin">
    <a href="configuracoes.php" class="voltar">&lt; Voltar</a>
    <h1>Gerenciar Usuários</h1>
</div>

<form method="post">
    <?= campoCsrf() ?>
    <div class="botoes-admin">
        <a href="usuario_form.php" class="btn btn-contorno">Adicionar</a>
        <button class="btn btn-contorno" data-confirmar="Você confirma a remoção do(s) usuário(s) selecionado(s)?">Excluir</button>
    </div>

    <div class="tabela-box escura">
        <table class="tabela tabela-admin">
            <thead><tr><th></th><th>Nome</th><th>Email</th><th>Cargo</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $u['id'] ?>" aria-label="Selecionar <?= e($u['nome']) ?>" <?= $u['id'] == $eu['id'] ? 'disabled' : '' ?>></td>
                    <td data-rotulo="Nome"><?= e($u['nome']) ?></td>
                    <td data-rotulo="Email"><?= e($u['email']) ?></td>
                    <td data-rotulo="Cargo"><?= e($u['cargo']) ?></td>
                    <td><a href="usuario_form.php?id=<?= $u['id'] ?>" class="txt-azul">editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<?php require 'includes/rodape.php'; ?>
