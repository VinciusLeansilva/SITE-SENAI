<?php
/*
 * Recuperar senha por e-mail exige um servidor de e-mail configurado.
 * Para manter o projeto simples, orientamos o usuário a falar com o
 * Gerente, que pode cadastrar uma nova senha em "Gerenciar usuários".
 */
require 'includes/funcoes.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha | StockControl</title>
    <script>
        try { document.documentElement.dataset.theme = localStorage.getItem('tema') || 'escuro'; } catch (e) {}
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="pagina-login">
    <div class="card-login">
        <img src="assets/img/logo.png" alt="StockControl" class="logo-login">
        <hr>
        <h2>Esqueceu sua senha?</h2>
        <p>Procure o <strong>Gerente</strong> do sistema. Ele pode redefinir sua senha
           em <em>Configurações &rarr; Gerenciar usuários</em>.</p>
        <a href="login.php" class="btn btn-azul btn-largo">Voltar ao login</a>
    </div>
</body>
</html>
