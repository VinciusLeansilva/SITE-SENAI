<?php
require 'includes/funcoes.php';
exigirPermissao('admin');

$logins = conectar()->query(
    'SELECT u.nome, u.cargo, h.logou_em
       FROM historico_login h JOIN usuarios u ON u.id = h.usuario_id
   ORDER BY h.logou_em DESC
      LIMIT 50'
)->fetchAll();

$titulo = 'Histórico de atividades';
$menuAtivo = 'configuracoes';
require 'includes/cabecalho.php';
?>
<div class="cabecalho-admin">
    <a href="configuracoes.php" class="voltar">&lt; Voltar</a>
    <h1>Histórico de atividades</h1>
</div>

<div class="tabela-box escura">
    <table class="tabela tabela-admin">
        <thead><tr><th>Nome</th><th>Cargo</th><th>Logou</th></tr></thead>
        <tbody>
        <?php foreach ($logins as $l): ?>
            <tr>
                <td data-rotulo="Nome"><?= e($l['nome']) ?></td>
                <td data-rotulo="Cargo"><?= e($l['cargo']) ?></td>
                <td data-rotulo="Logou"><?= dataBR($l['logou_em'], true) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'includes/rodape.php'; ?>
