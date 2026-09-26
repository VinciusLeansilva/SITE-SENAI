<?php
/*
 * Funções usadas em todas as páginas.
 * Toda página do sistema começa com:  require 'includes/funcoes.php';
 */
require_once __DIR__ . '/../config/conexao.php';

// Inicia a sessão com cookies mais seguros.
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,   // JavaScript não consegue ler o cookie da sessão
        'cookie_samesite' => 'Lax',  // dificulta ataques vindos de outros sites
    ]);
}

/* ---------------------------------------------------------------
 * e() = "escape". Use SEMPRE que for imprimir algo vindo do banco
 * ou do usuário no HTML. Evita ataques XSS (injeção de <script>).
 * ------------------------------------------------------------- */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------------------------------------------------------------
 * Mensagens "flash": mostradas uma única vez na próxima página.
 * Ex.: flash('Produto salvo!');  flash('Erro!', 'erro');
 * ------------------------------------------------------------- */
function flash(string $mensagem, string $tipo = 'sucesso'): void
{
    $_SESSION['flash'] = ['msg' => $mensagem, 'tipo' => $tipo];
}

function pegarFlash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------------------------------------------------------
 * CSRF: um "código secreto" colocado em todo formulário POST.
 * Assim, outro site não consegue enviar formulários em nosso nome.
 * ------------------------------------------------------------- */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function validarCsrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!hash_equals(csrfToken(), $enviado)) {
        http_response_code(400);
        exit('Requisição inválida (token CSRF). Volte e recarregue a página.');
    }
}

/* ---------------------------------------------------------------
 * Login e permissões
 * ------------------------------------------------------------- */
function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

// Coloque no topo das páginas que exigem login.
function exigirLogin(): array
{
    $u = usuarioLogado();
    if (!$u) {
        redirecionar('login.php');
    }
    return $u;
}

/*
 * Quais menus cada cargo pode ver/usar.
 * Veja no protótipo: o Gerente vê tudo; o Assistente vê só
 * Separação, Expedição, Suporte e Configurações.
 */
const PERMISSOES = [
    'Gerente'    => ['vendas', 'cadastros', 'separacao', 'expedicao', 'suporte', 'admin'],
    'Supervisor' => ['vendas', 'cadastros', 'separacao', 'expedicao', 'suporte'],
    'Estoquista' => ['cadastros', 'separacao', 'expedicao', 'suporte'],
    'Vendedor'   => ['vendas', 'suporte'],
    'Assistente' => ['separacao', 'expedicao', 'suporte'],
];

function pode(string $area): bool
{
    $u = usuarioLogado();
    return $u && in_array($area, PERMISSOES[$u['cargo']] ?? [], true);
}

// Bloqueia a página se o cargo não tiver acesso à área.
function exigirPermissao(string $area): array
{
    $u = exigirLogin();
    if (!pode($area)) {
        http_response_code(403);
        flash('Você não tem permissão para acessar esta área.', 'erro');
        redirecionar('index.php');
    }
    return $u;
}

/* ---------------------------------------------------------------
 * Formatação
 * ------------------------------------------------------------- */
function dinheiro($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function dataBR(?string $data, bool $comHora = false): string
{
    if (!$data) {
        return '';
    }
    return date($comHora ? 'd/m/Y H:i' : 'd/m/Y', strtotime($data));
}

const STATUS_PEDIDO = [
    'em_separacao' => 'aguardando separação',
    'separado'     => 'separado',
    'pronto_envio' => 'pronto para envio',
    'enviado'      => 'enviado',
];

// Registra entrada/saída de estoque (aparece no painel inicial).
function registrarMovimentacao(int $produtoId, string $tipo, int $quantidade): void
{
    $u = usuarioLogado();
    $sql = 'INSERT INTO movimentacoes (produto_id, tipo, quantidade, usuario_id) VALUES (?, ?, ?, ?)';
    conectar()->prepare($sql)->execute([$produtoId, $tipo, $quantidade, $u['id'] ?? null]);
}
