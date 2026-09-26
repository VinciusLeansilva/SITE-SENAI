<?php
/*
 * Cadastro de Produtos.
 *   produto.php        -> novo produto
 *   produto.php?id=1   -> ver/editar o produto 1
 */
require 'includes/funcoes.php';
exigirPermissao('cadastros');
$db = conectar();

$id = (int) ($_GET['id'] ?? 0);

// Valores padrão para um produto novo
$p = [
    'id' => 0, 'nome' => '', 'sku' => '', 'preco' => '', 'estoque' => 0, 'estoque_minimo' => 5,
    'promo_dia' => '', 'promo_preco' => '', 'cod_barras' => '', 'fornecedor' => '', 'plataforma' => '',
    'breve_descricao' => '', 'descricao' => '', 'localizacao' => '', 'imagem' => '',
    'categoria_id' => '', 'status' => 'publicado',
];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) {
        flash('Produto não encontrado.', 'erro');
        redirecionar('produtos.php');
    }
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $estoqueAntigo = (int) $p['estoque'];

    // 1) Lê os campos do formulário
    $campos = ['nome', 'sku', 'cod_barras', 'fornecedor', 'plataforma', 'breve_descricao', 'descricao', 'localizacao'];
    foreach ($campos as $c) {
        $p[$c] = trim($_POST[$c] ?? '');
    }
    // Aceita "287,99" ou "287.99"
    $p['preco']          = str_replace(',', '.', $_POST['preco'] ?? '0');
    $p['promo_preco']    = str_replace(',', '.', $_POST['promo_preco'] ?? '');
    $p['promo_dia']      = $_POST['promo_dia'] ?? '';
    $p['estoque']        = (int) ($_POST['estoque'] ?? 0);
    $p['estoque_minimo'] = (int) ($_POST['estoque_minimo'] ?? 0);
    $p['categoria_id']   = (int) ($_POST['categoria_id'] ?? 0) ?: null;
    $p['status']         = isset($_POST['rascunho']) ? 'rascunho' : 'publicado';

    // 2) Valida
    if ($p['nome'] === '') $erros[] = 'Informe o nome do produto.';
    if ($p['sku'] === '')  $erros[] = 'Informe o SKU.';
    if (!is_numeric($p['preco']) || $p['preco'] < 0) $erros[] = 'Preço inválido.';
    if ($p['estoque'] < 0) $erros[] = 'O estoque não pode ser negativo.';
    if ($p['promo_preco'] !== '' && !is_numeric($p['promo_preco'])) $erros[] = 'Preço da promoção inválido.';

    // SKU precisa ser único
    $stmt = $db->prepare('SELECT id FROM produtos WHERE sku = ? AND id <> ?');
    $stmt->execute([$p['sku'], $id]);
    if ($stmt->fetch()) $erros[] = 'Já existe outro produto com este SKU.';

    // 3) Upload da imagem (opcional)
    if (!$erros && !empty($_FILES['imagem']['name'])) {
        $arq = $_FILES['imagem'];
        // Descobrimos o tipo REAL do arquivo (não confiamos na extensão enviada).
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime  = $arq['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($arq['tmp_name']) : '';

        if ($arq['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Falha ao enviar a imagem.';
        } elseif ($arq['size'] > 2 * 1024 * 1024) {
            $erros[] = 'A imagem deve ter no máximo 2 MB.';
        } elseif (!isset($tipos[$mime])) {
            $erros[] = 'Envie uma imagem JPG, PNG ou WEBP.';
        } else {
            // Nome aleatório: evita sobrescrever arquivos e nomes maliciosos.
            $nomeArquivo = bin2hex(random_bytes(8)) . '.' . $tipos[$mime];
            move_uploaded_file($arq['tmp_name'], __DIR__ . '/uploads/' . $nomeArquivo);
            if ($p['imagem']) @unlink(__DIR__ . '/uploads/' . basename($p['imagem']));
            $p['imagem'] = $nomeArquivo;
        }
    }

    // 4) Salva
    if (!$erros) {
        $dados = [
            $p['nome'], $p['sku'], $p['preco'], $p['estoque'], $p['estoque_minimo'],
            $p['promo_dia'] ?: null, $p['promo_preco'] !== '' ? $p['promo_preco'] : null,
            $p['cod_barras'], $p['fornecedor'], $p['plataforma'], $p['breve_descricao'],
            $p['descricao'], $p['localizacao'], $p['imagem'] ?: null, $p['categoria_id'], $p['status'],
        ];

        if ($id) {
            $sql = 'UPDATE produtos SET nome=?, sku=?, preco=?, estoque=?, estoque_minimo=?, promo_dia=?, promo_preco=?,
                    cod_barras=?, fornecedor=?, plataforma=?, breve_descricao=?, descricao=?, localizacao=?,
                    imagem=?, categoria_id=?, status=? WHERE id=?';
            $dados[] = $id;
            $db->prepare($sql)->execute($dados);
        } else {
            $sql = 'INSERT INTO produtos (nome, sku, preco, estoque, estoque_minimo, promo_dia, promo_preco,
                    cod_barras, fornecedor, plataforma, breve_descricao, descricao, localizacao, imagem,
                    categoria_id, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $db->prepare($sql)->execute($dados);
            $id = (int) $db->lastInsertId();
        }

        // Se o estoque mudou, registramos a entrada/saída no histórico.
        $diferenca = $p['estoque'] - $estoqueAntigo;
        if ($diferenca !== 0) {
            registrarMovimentacao($id, $diferenca > 0 ? 'entrada' : 'saida', abs($diferenca));
        }

        flash($p['status'] === 'rascunho' ? 'Rascunho salvo.' : 'Produto salvo com sucesso.');
        redirecionar('produto.php?id=' . $id);
    }
}

$categorias = $db->query('SELECT * FROM categorias ORDER BY nome')->fetchAll();

$titulo = $id ? $p['nome'] : 'Novo produto';
$menuAtivo = 'cadastros';
$subAtivo = $id ? '' : 'novo';
require 'includes/cabecalho.php';
?>
<h1 class="titulo-pagina">CADASTRO DE PRODUTOS</h1>

<?php foreach ($erros as $erro): ?>
    <p class="alerta-erro"><?= e($erro) ?></p>
<?php endforeach; ?>

<!-- enctype="multipart/form-data" é obrigatório para enviar arquivos -->
<form method="post" enctype="multipart/form-data" class="form-produto">
    <?= campoCsrf() ?>

    <div class="linha-nome">
        <input type="text" name="nome" class="input-nome" value="<?= e($p['nome']) ?>" placeholder="Nome do produto" required>
        <a href="produtos.php" class="btn btn-branco">&#128269; Pesquisa</a>
    </div>

    <div class="grade-produto">
        <div>
            <div class="bloco-imagem">
                <span class="etiqueta">Imagens</span>
                <?php if ($p['imagem']): ?>
                    <img src="uploads/<?= e($p['imagem']) ?>" alt="Foto do produto">
                <?php endif; ?>
                <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp">
            </div>

            <table class="ficha">
                <tr><th>SKU:</th><td><input name="sku" value="<?= e($p['sku']) ?>" required></td></tr>
                <tr><th>Preço (R$):</th><td><input name="preco" inputmode="decimal" value="<?= e($p['preco']) ?>" required></td></tr>
                <tr><th>Estoque:</th><td><input type="number" min="0" name="estoque" value="<?= e($p['estoque']) ?>"></td></tr>
                <tr><th>Estoque mínimo:</th><td><input type="number" min="0" name="estoque_minimo" value="<?= e($p['estoque_minimo']) ?>"></td></tr>
                <tr><th>Promoção:</th>
                    <td class="duplo">
                        <input type="date" name="promo_dia" value="<?= e($p['promo_dia']) ?>" aria-label="Dia da promoção">
                        <input name="promo_preco" inputmode="decimal" placeholder="Preço" value="<?= e($p['promo_preco']) ?>" aria-label="Preço da promoção">
                    </td>
                </tr>
                <tr><th>Cod. Barras:</th><td><input name="cod_barras" value="<?= e($p['cod_barras']) ?>"></td></tr>
                <tr><th>Fornecedor:</th><td><input name="fornecedor" value="<?= e($p['fornecedor']) ?>"></td></tr>
                <tr><th>Plataforma:</th><td><input name="plataforma" value="<?= e($p['plataforma']) ?>"></td></tr>
                <tr><th>Categoria:</th>
                    <td>
                        <select name="categoria_id">
                            <option value="">— nenhuma —</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $p['categoria_id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr><th>Localização:</th><td><input name="localizacao" value="<?= e($p['localizacao']) ?>" placeholder="Ex.: Matriz - A 21 Caixa 5"></td></tr>
            </table>
        </div>

        <div>
            <label class="caixa-texto">
                <strong>Breve descrição:</strong>
                <textarea name="breve_descricao" rows="3"><?= e($p['breve_descricao']) ?></textarea>
            </label>
            <label class="caixa-texto">
                <strong>Descrição do Produto:</strong>
                <textarea name="descricao" rows="12" placeholder="Uma característica por linha"><?= e($p['descricao']) ?></textarea>
            </label>
        </div>
    </div>

    <div class="acoes">
        <button type="submit" class="btn btn-azul">Salvar</button>
        <button type="submit" name="rascunho" value="1" class="btn btn-cinza">Salvar como rascunho</button>
        <?php if ($id): ?>
            <!-- form="formExcluir": este botão envia o formulário de exclusão lá embaixo -->
            <button type="submit" form="formExcluir" class="btn btn-vermelho"
                    data-confirmar="Tem certeza de que deseja excluir o produto do estoque? Após clicar em &quot;Confirmar&quot; todo progresso será perdido.">
                &#128465; Excluir
            </button>
        <?php endif; ?>
    </div>
</form>

<?php if ($id): ?>
    <form id="formExcluir" method="post" action="produto_excluir.php">
        <?= campoCsrf() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
    </form>
<?php endif; ?>

<?php require 'includes/submenu_cadastros.php'; ?>
<?php require 'includes/rodape.php'; ?>
