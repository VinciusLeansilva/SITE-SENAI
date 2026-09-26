<?php
/*
 * Adicionar usuário (usuario_form.php) ou editar (usuario_form.php?id=3).
 * Ao editar, a senha só muda se algo for digitado no campo.
 */
require 'includes/funcoes.php';
exigirPermissao('admin');
$db = conectar();

$id = (int) ($_GET['id'] ?? 0);
$u  = ['nome' => '', 'email' => '', 'telefone' => '', 'cargo' => 'Vendedor'];

if ($id) {
    $stmt = $db->prepare('SELECT nome, email, telefone, cargo FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) {
        flash('Usuário não encontrado.', 'erro');
        redirecionar('usuarios.php');
    }
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $u['nome']     = trim($_POST['nome'] ?? '');
    $u['email']    = trim($_POST['email'] ?? '');
    $u['telefone'] = trim($_POST['telefone'] ?? '');
    $u['cargo']    = $_POST['cargo'] ?? '';
    $senha         = $_POST['senha'] ?? '';

    if ($u['nome'] === '') $erros[] = 'Informe o nome.';
    if (!filter_var($u['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (!array_key_exists($u['cargo'], PERMISSOES)) $erros[] = 'Cargo inválido.';

    // Senha obrigatória para novo usuário; opcional na edição
    if (!$id || $senha !== '') {
        if (strlen($senha) < 6) $erros[] = 'A senha precisa ter pelo menos 6 caracteres.';
        if ($senha !== ($_POST['confirmar'] ?? '')) $erros[] = 'As senhas não conferem.';
    }

    $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
    $stmt->execute([$u['email'], $id]);
    if ($stmt->fetch()) $erros[] = 'Este e-mail já está cadastrado.';

    if (!$erros) {
        if ($id) {
            $db->prepare('UPDATE usuarios SET nome = ?, email = ?, telefone = ?, cargo = ? WHERE id = ?')
               ->execute([$u['nome'], $u['email'], $u['telefone'], $u['cargo'], $id]);
            if ($senha !== '') {
                $db->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')
                   ->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
            }
            flash('Usuário atualizado.');
        } else {
            $db->prepare('INSERT INTO usuarios (nome, email, telefone, cargo, senha) VALUES (?, ?, ?, ?, ?)')
               ->execute([$u['nome'], $u['email'], $u['telefone'], $u['cargo'], password_hash($senha, PASSWORD_DEFAULT)]);
            flash('Usuário adicionado.');
        }
        redirecionar('usuarios.php');
    }
}

$titulo = $id ? 'Editar usuário' : 'Adicionar usuário';
$menuAtivo = 'configuracoes';
require 'includes/cabecalho.php';
?>
<div class="cabecalho-admin">
    <a href="usuarios.php" class="voltar">&lt; Voltar</a>
    <h1><?= $titulo ?></h1>
</div>

<?php foreach ($erros as $erro): ?>
    <p class="alerta-erro"><?= e($erro) ?></p>
<?php endforeach; ?>

<form method="post" class="painel form-admin">
    <?= campoCsrf() ?>

    <label for="nome">Nome</label>
    <div class="campo-icone"><span>&#128100;</span><input id="nome" name="nome" value="<?= e($u['nome']) ?>" placeholder="Digite o nome" required></div>

    <label for="email">E-mail</label>
    <div class="campo-icone"><span>&#9993;&#65039;</span><input type="email" id="email" name="email" value="<?= e($u['email']) ?>" placeholder="Digite o E-mail" required></div>

    <label for="telefone">Telefone</label>
    <div class="campo-icone"><span>&#128222;</span><input type="tel" id="telefone" name="telefone" value="<?= e($u['telefone']) ?>" placeholder="Digite o telefone"></div>

    <label for="cargo">Cargo</label>
    <div class="campo-icone"><span>&#127991;&#65039;</span>
        <select id="cargo" name="cargo">
            <?php foreach (array_keys(PERMISSOES) as $cargo): ?>
                <option <?= $u['cargo'] === $cargo ? 'selected' : '' ?>><?= $cargo ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <label for="senha">Senha <?= $id ? '<small>(deixe em branco para manter)</small>' : '' ?></label>
    <div class="campo-icone"><span>&#128274;</span>
        <input type="password" id="senha" name="senha" placeholder="Digite a senha" <?= $id ? '' : 'required' ?> minlength="6">
        <button type="button" class="ver-senha" data-alvo="senha" aria-label="Mostrar senha">&#128065;</button>
    </div>

    <label for="confirmar">Confirmar senha</label>
    <div class="campo-icone"><span>&#128274;</span>
        <input type="password" id="confirmar" name="confirmar" placeholder="Confirme a senha" <?= $id ? '' : 'required' ?> minlength="6">
        <button type="button" class="ver-senha" data-alvo="confirmar" aria-label="Mostrar senha">&#128065;</button>
    </div>

    <button class="btn btn-azul" data-confirmar="Você deseja salvar o usuário (<?= $id ? e($u['nome']) : 'novo' ?>)?"><?= $id ? 'Salvar' : 'Adicionar' ?></button>
</form>

<?php require 'includes/rodape.php'; ?>
