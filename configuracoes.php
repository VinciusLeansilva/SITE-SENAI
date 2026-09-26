<?php
require 'includes/funcoes.php';
$usuario = exigirLogin();
$db = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'nome') {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            flash('O nome não pode ficar vazio.', 'erro');
        } else {
            $db->prepare('UPDATE usuarios SET nome = ? WHERE id = ?')->execute([$nome, $usuario['id']]);
            $_SESSION['usuario']['nome'] = $nome; // atualiza também a sessão
            flash('Nome alterado.');
        }
    }

    if ($acao === 'senha') {
        $stmt = $db->prepare('SELECT senha FROM usuarios WHERE id = ?');
        $stmt->execute([$usuario['id']]);
        $hashAtual = $stmt->fetchColumn();

        $nova = $_POST['nova'] ?? '';
        if (!password_verify($_POST['atual'] ?? '', $hashAtual)) {
            flash('Senha atual incorreta.', 'erro');
        } elseif (strlen($nova) < 6) {
            flash('A nova senha precisa ter pelo menos 6 caracteres.', 'erro');
        } elseif ($nova !== ($_POST['confirmar'] ?? '')) {
            flash('A confirmação não confere com a nova senha.', 'erro');
        } else {
            $db->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')
               ->execute([password_hash($nova, PASSWORD_DEFAULT), $usuario['id']]);
            flash('Senha alterada.');
        }
    }

    redirecionar('configuracoes.php');
}

$titulo = 'Configurações';
$menuAtivo = 'configuracoes';
require 'includes/cabecalho.php';
$usuario = usuarioLogado(); // relê (o nome pode ter mudado)
?>
<h1 class="titulo-grande centro">Configurações &#9881;&#65039;</h1>

<div class="painel config">
    <label class="linha-tema">
        <strong>Tema (Claro/Escuro):</strong>
        <!-- O app.js salva a escolha no navegador (localStorage) -->
        <input type="checkbox" id="trocarTema" class="interruptor" aria-label="Alternar tema escuro">
    </label>

    <h2>Meu perfil:</h2>

    <form method="post" class="linha-perfil">
        <?= campoCsrf() ?>
        <label>Nome: <input name="nome" value="<?= e($usuario['nome']) ?>" required></label>
        <button name="acao" value="nome" class="btn btn-contorno">Alterar</button>
    </form>

    <form method="post" class="linha-perfil">
        <?= campoCsrf() ?>
        <label>Senha atual: <input type="password" name="atual" required></label>
        <label>Nova senha: <input type="password" name="nova" minlength="6" required></label>
        <label>Confirmar: <input type="password" name="confirmar" minlength="6" required></label>
        <button name="acao" value="senha" class="btn btn-contorno">Alterar</button>
    </form>

    <?php if (pode('admin')): ?>
        <hr>
        <h2>Configurações avançadas (Apenas Gerente)</h2>
        <nav class="links-admin">
            <a href="usuarios.php">Gerenciar usuários &#9654;</a>
            <a href="historico.php">Histórico de atividades &#9654;</a>
            <a href="categorias.php">Gerenciar categorias/tags &#9654;</a>
        </nav>
    <?php endif; ?>
</div>

<?php require 'includes/rodape.php'; ?>
