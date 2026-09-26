<?php
/*
 * Cabeçalho + menu lateral. Antes de incluir, defina:
 *   $titulo     -> texto da aba do navegador
 *   $menuAtivo  -> 'inicio', 'vendas', 'cadastros', 'separacao',
 *                  'expedicao', 'suporte' ou 'configuracoes'
 */
$usuario   = usuarioLogado();
$titulo    = $titulo ?? 'StockControl';
$menuAtivo = $menuAtivo ?? '';

// Itens do menu: [chave, texto, link, permissão necessária]
$itensMenu = [
    ['vendas',    'Vendas',    'vendas.php',    'vendas'],
    ['cadastros', 'Cadastros', 'produtos.php',  'cadastros'],
    ['separacao', 'Separação', 'separacao.php', 'separacao'],
    ['expedicao', 'Expedição', 'expedicao.php', 'expedicao'],
    ['suporte',   'Suporte',   'suporte.php',   'suporte'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | StockControl</title>

    <!-- PWA: permite instalar o site como aplicativo -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1e6fd9">
    <link rel="icon" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- Aplica o tema salvo ANTES de desenhar a página (evita "piscar") -->
    <script>
        try { document.documentElement.dataset.theme = localStorage.getItem('tema') || 'escuro'; } catch (e) {}
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<div class="layout">

    <aside class="sidebar" id="sidebar">
        <a href="index.php" class="logo"><img src="assets/img/logo.png" alt="StockControl"></a>

        <nav class="menu">
            <?php foreach ($itensMenu as [$chave, $texto, $link, $perm]): ?>
                <?php if (pode($perm)): ?>
                    <a href="<?= $link ?>" class="<?= $menuAtivo === $chave ? 'ativo' : '' ?>"><?= $texto ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <hr>
            <a href="configuracoes.php" class="<?= $menuAtivo === 'configuracoes' ? 'ativo' : '' ?>">Configurações</a>
            <a href="logout.php">Sair</a>
        </nav>

        <p class="grupo">Grupo: Vitor Pereira, Felipe Ecel, Gustavo G</p>
    </aside>

    <main class="conteudo">
        <header class="topo">
            <!-- Botão ☰: abre/fecha o menu no celular -->
            <button class="btn-menu" id="btnMenu" aria-label="Abrir menu">&#9776;</button>
            <div class="perfil">
                <div class="avatar" aria-hidden="true">&#128100;</div>
                <span><?= e($usuario['cargo']) ?></span>
            </div>
        </header>

        <?php if ($f = pegarFlash()): ?>
            <div class="toast toast-<?= e($f['tipo']) ?>" role="status">
                <span><?= $f['tipo'] === 'sucesso' ? '&#10004;' : '&#9888;' ?></span>
                <?= e($f['msg']) ?>
                <button type="button" class="toast-fechar" aria-label="Fechar">&times;</button>
            </div>
        <?php endif; ?>
