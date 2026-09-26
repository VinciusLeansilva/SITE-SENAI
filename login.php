<?php
require 'includes/funcoes.php';

// Se já estiver logado, vai direto para o painel.
if (usuarioLogado()) {
    redirecionar('index.php');
}

$erro  = '';
// "Lembrar-me": o e-mail fica salvo num cookie (NUNCA a senha).
$email = $_COOKIE['lembrar_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = conectar()->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    // password_verify compara a senha digitada com o hash salvo no banco.
    if ($u && password_verify($senha, $u['senha'])) {
        session_regenerate_id(true); // novo ID de sessão: evita "sequestro de sessão"
        $_SESSION['usuario'] = [
            'id'    => $u['id'],
            'nome'  => $u['nome'],
            'email' => $u['email'],
            'cargo' => $u['cargo'],
        ];

        conectar()->prepare('INSERT INTO historico_login (usuario_id) VALUES (?)')->execute([$u['id']]);

        if (!empty($_POST['lembrar'])) {
            setcookie('lembrar_email', $email, time() + 60 * 60 * 24 * 30, '', '', false, true);
        } else {
            setcookie('lembrar_email', '', time() - 3600);
        }

        redirecionar('index.php');
    }

    // Mensagem genérica: não dizemos se o erro foi no e-mail ou na senha.
    $erro = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | StockControl</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1e6fd9">
    <link rel="icon" href="assets/icons/icon-192.png">
    <script>
        try { document.documentElement.dataset.theme = localStorage.getItem('tema') || 'escuro'; } catch (e) {}
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="pagina-login">
    <form class="card-login" method="post" autocomplete="on">
        <img src="assets/img/logo.png" alt="StockControl" class="logo-login">
        <hr>

        <?php if ($erro): ?>
            <p class="alerta-erro"><?= e($erro) ?></p>
        <?php endif; ?>

        <?= campoCsrf() ?>

        <label for="email">Email</label>
        <div class="campo-icone">
            <span aria-hidden="true">&#128100;</span>
            <input type="email" id="email" name="email" placeholder="Digite seu email" value="<?= e($email) ?>" required>
        </div>

        <label for="senha">Senha</label>
        <div class="campo-icone">
            <span aria-hidden="true">&#128274;</span>
            <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
            <button type="button" class="ver-senha" data-alvo="senha" aria-label="Mostrar senha">&#128065;</button>
        </div>

        <div class="linha-login">
            <label class="check"><input type="checkbox" name="lembrar" <?= $email ? 'checked' : '' ?>> Lembrar-me</label>
            <a href="esqueci-senha.php">Esqueceu sua senha?</a>
        </div>

        <button type="submit" class="btn btn-azul btn-largo">Entrar</button>
    </form>
</body>
</html>
