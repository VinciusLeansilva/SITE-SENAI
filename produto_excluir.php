<?php
/*
 * Exclui um produto. Só aceita POST (nunca exclua coisas com um simples link/GET,
 * porque um link pode ser aberto sem querer ou por um robô).
 */
require 'includes/funcoes.php';
exigirPermissao('cadastros');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('produtos.php');
}
validarCsrf();

$id = (int) ($_POST['id'] ?? 0);
$db = conectar();

$stmt = $db->prepare('SELECT imagem FROM produtos WHERE id = ?');
$stmt->execute([$id]);
$produto = $stmt->fetch();

if ($produto) {
    $db->prepare('DELETE FROM produtos WHERE id = ?')->execute([$id]);
    // Apaga também a foto da pasta uploads
    if ($produto['imagem']) {
        @unlink(__DIR__ . '/uploads/' . basename($produto['imagem']));
    }
    flash('Produto excluído.');
} else {
    flash('Produto não encontrado.', 'erro');
}

redirecionar('produtos.php');
