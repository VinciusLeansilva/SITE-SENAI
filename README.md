# StockControl — Controle Inteligente de Estoque

Sistema web feito a partir do protótipo **StockControl.pdf** (24 telas).
Tecnologias: **HTML + CSS + JavaScript puro + PHP puro + MySQL**, sem frameworks.
Ele também pode ser **instalado como aplicativo** (PWA) no computador ou no celular.

Grupo: Vitor Pereira, Felipe Ecel, Gustavo G — SENAI, Técnico em Informática.

---

## 1. Como rodar no seu computador (XAMPP)

1. Instale o **XAMPP** e abra o painel. Clique em **Start** no **Apache** e no **MySQL**.
2. Copie a pasta do projeto para `C:\xampp\htdocs\stockcontrol`.
3. Abra o **phpMyAdmin** (`http://localhost/phpmyadmin`) → aba **Importar** →
   escolha o arquivo `database.sql` → **Executar**.
   Isso cria o banco `stockcontrol` com dados de exemplo.
4. Se o seu MySQL tiver senha, altere `config/conexao.php` (`DB_USER` e `DB_SENHA`).
5. Acesse **http://localhost/stockcontrol/login.php**.

### Usuários de teste (senha de todos: `123456`)

| E-mail                 | Cargo      | O que ele vê no menu                           |
|------------------------|------------|------------------------------------------------|
| gerente@gmail.com      | Gerente    | Tudo, inclusive "Configurações avançadas"      |
| supervisor@gmail.com   | Supervisor | Vendas, Cadastros, Separação, Expedição, Suporte |
| estoquista@gmail.com   | Estoquista | Cadastros, Separação, Expedição, Suporte       |
| vendedor@gmail.com     | Vendedor   | Vendas, Suporte                                |
| assistente@gmail.com   | Assistente | Separação, Expedição, Suporte (tela 3 do PDF)  |

> Sem XAMPP? Com PHP e MySQL instalados, rode `php -S localhost:8000` dentro da pasta
> e acesse `http://localhost:8000/login.php`.

---

## 2. Instalar como aplicativo (PWA)

Um **PWA** (Progressive Web App) é um site que o navegador deixa instalar como app:
ele ganha ícone, abre em janela própria (sem barra de endereço) e mostra uma
tela amigável quando está sem conexão. Três arquivos fazem isso:

| Arquivo             | Para que serve                                                    |
|---------------------|-------------------------------------------------------------------|
| `manifest.json`     | Nome, ícone, cores e o modo "standalone" (janela de app)          |
| `service-worker.js` | Guarda CSS/JS/imagens em cache e mostra `offline.html` sem internet |
| `assets/icons/`     | Ícones 192×192 e 512×512 (feitos a partir do logo do PDF)         |

**No computador (Chrome/Edge):** abra o site → clique no ícone de **instalar** (⊕)
na barra de endereço → **Instalar**. O StockControl aparece no menu Iniciar.

**No celular (Android/Chrome):** menu ⋮ → **Adicionar à tela inicial / Instalar app**.

> ⚠️ O navegador só permite instalar em **`localhost`** ou em sites com **HTTPS**.
> Para usar no celular, o sistema precisa estar hospedado com HTTPS
> (ex.: uma hospedagem PHP com certificado grátis Let's Encrypt).

---

## 3. Organização das pastas

```
├── config/conexao.php        → conexão com o banco (PDO)
├── includes/
│   ├── funcoes.php           → sessão, segurança, permissões, formatação
│   ├── cabecalho.php         → <head> + menu lateral (usado em todas as páginas)
│   ├── rodape.php            → fim da página + janela de confirmação
│   └── submenu_cadastros.php → links "-Feitos -Rascunhos -Novo Produto..."
├── assets/css/style.css      → todo o visual (tema claro/escuro + celular)
├── assets/js/app.js          → modais, tema, menu do celular, PWA
├── uploads/                  → fotos dos produtos (bloqueado para PHP)
├── database.sql              → cria o banco e os dados de exemplo
└── *.php                     → uma página para cada tela
```

### Qual página corresponde a qual tela do PDF

| Tela(s) do PDF | Página                         | O que faz |
|----------------|--------------------------------|-----------|
| 1              | `login.php`                    | Login com "Lembrar-me" e mostrar senha |
| 2, 3           | `index.php`                    | Painel: totais, estoque baixo e últimas movimentações |
| 4              | `produto.php`                  | Cadastrar / editar produto (com foto) |
| 5              | janela no `produto.php`        | Confirmação antes de excluir |
| 6              | `produtos.php`                 | Lista de produtos (Feitos, Rascunhos, Estoque baixo) |
| 7              | `local.php`                    | Encontrar onde o produto está no estoque |
| 8, 9           | `separacao.php`                | Pedidos por etapa: Em separação / Separado / Pronto |
| 10 a 16        | `pedido.php`                   | Dados da nota + menu ☰ (Separar, Volumes, Remover) |
| 17             | `configuracoes.php`            | Tema, meu perfil e links do Gerente |
| 18, 20         | `usuarios.php`                 | Listar e excluir usuários |
| 19             | `usuario_form.php`             | Adicionar / editar usuário |
| 21             | `historico.php`                | Quem logou e quando |
| 22, 23         | `categorias.php`               | Listar e excluir categorias |
| 24             | `categoria_nova.php`           | Adicionar categorias |
| (não tinha)    | `vendas.php`, `expedicao.php`, `suporte.php` | Criar pedidos, despachar e ajuda |

---

## 4. Fluxo de um pedido

```
Vendas (cria pedido) ──► Em separação ──► Separado ──► Pronto para envio ──► Enviado
                          "Separar pedido"   "Atualizar volumes"   Expedição: "Marcar como enviado"
                          (baixa o estoque)
```

---

## 5. Segurança aplicada (e por quê)

| Proteção | Onde | Contra o quê |
|---|---|---|
| `password_hash` / `password_verify` | login, usuários | Senhas nunca ficam em texto puro no banco |
| Prepared statements (`?` no SQL) | todas as consultas | **SQL Injection** |
| Função `e()` (`htmlspecialchars`) | tudo que é impresso no HTML | **XSS** (injeção de `<script>`) |
| Token CSRF em todo formulário POST | `campoCsrf()` / `validarCsrf()` | Outro site enviando formulários em seu nome |
| `session_regenerate_id` no login | `login.php` | Sequestro de sessão |
| Permissão por cargo no servidor | `exigirPermissao()` | Esconder o menu não basta: a página também bloqueia |
| Upload: tipo real (finfo), 2 MB, nome aleatório, `.htaccess` | `produto.php`, `uploads/` | Envio de arquivo malicioso |
| Exclusões só por POST + confirmação | todas | Apagar algo sem querer |

---

## 6. Roteiro de testes

1. Entre com `gerente@gmail.com` / `123456` → deve aparecer o painel.
2. **Cadastros → -Novo Produto**: cadastre um produto com estoque 10 → ele aparece em "-Feitos"
   e a entrada aparece no painel.
3. Abra o produto → **Excluir** → a janela de confirmação aparece → **Confirmar**.
4. **Vendas**: crie um pedido → ele aparece em **Separação → Em separação**.
5. Abra o pedido → ☰ → **Separar pedido** → aparece "Pedido separado com sucesso." e o estoque baixa.
6. ☰ → **Atualizar volumes** → informe 2 → status muda para "pronto para envio".
7. **Expedição** → **Marcar como enviado**.
8. **Configurações** → troque o tema (claro/escuro) e recarregue: a escolha continua.
9. Saia e entre com `assistente@gmail.com`: o menu mostra só Separação, Expedição e Suporte.
   Tente abrir `usuarios.php` pela barra de endereço: você é mandado de volta ao painel.
10. Diminua a janela (ou abra no celular): o menu vira o botão ☰ e as tabelas viram cartões.

---

## 7. Ideias para evoluir (exercícios)

- Pedido com **vários produtos** na tela de Vendas (hoje é 1 item por pedido).
- Recuperação de senha por **e-mail** (PHPMailer).
- Gráfico de entradas/saídas no painel (`<canvas>` com JS puro).
- Várias fotos por produto (tabela `produto_imagens`).
