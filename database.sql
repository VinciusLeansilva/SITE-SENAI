-- =============================================================
--  StockControl - Controle Inteligente de Estoque
--  Script de criação do banco de dados (MySQL / MariaDB)
--
--  Como usar: abra o phpMyAdmin > aba "Importar" > escolha este
--  arquivo > "Executar". Ou pelo terminal:
--      mysql -u root -p < database.sql
-- =============================================================

-- Garante que os acentos (ç, ã, é...) sejam lidos corretamente
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS stockcontrol;
CREATE DATABASE stockcontrol CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stockcontrol;

-- -------------------------------------------------------------
-- Usuários do sistema. A senha NUNCA é guardada em texto puro:
-- guardamos o "hash" gerado pelo password_hash() do PHP.
-- -------------------------------------------------------------
CREATE TABLE usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    telefone   VARCHAR(20),
    senha      VARCHAR(255) NOT NULL,
    cargo      ENUM('Gerente','Supervisor','Assistente','Estoquista','Vendedor') NOT NULL,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Registro de cada login (tela "Histórico de atividades")
CREATE TABLE historico_login (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    logou_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Categorias / Tags dos produtos
CREATE TABLE categorias (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

-- Produtos (tela "Cadastro de Produtos")
CREATE TABLE produtos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(200) NOT NULL,
    sku             VARCHAR(50)  NOT NULL UNIQUE,
    preco           DECIMAL(10,2) NOT NULL DEFAULT 0,
    estoque         INT NOT NULL DEFAULT 0,
    estoque_minimo  INT NOT NULL DEFAULT 5,
    promo_dia       DATE NULL,
    promo_preco     DECIMAL(10,2) NULL,
    cod_barras      VARCHAR(30),
    fornecedor      VARCHAR(150),
    plataforma      VARCHAR(100),
    breve_descricao VARCHAR(500),
    descricao       TEXT,
    localizacao     VARCHAR(150),
    imagem          VARCHAR(255),
    categoria_id    INT NULL,
    status          ENUM('publicado','rascunho') NOT NULL DEFAULT 'publicado',
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Entradas e saídas de estoque (tabela do painel inicial)
CREATE TABLE movimentacoes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo       ENUM('entrada','saida') NOT NULL,
    quantidade INT NOT NULL,
    usuario_id INT NULL,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Pedidos / notas fiscais (telas "Separação" e "Dados da nota")
CREATE TABLE pedidos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    numero         VARCHAR(20) NOT NULL UNIQUE,
    destinatario   VARCHAR(150) NOT NULL,
    transportadora VARCHAR(100) NOT NULL,
    data_emissao   DATE NOT NULL,
    volumes        INT NULL,
    status         ENUM('em_separacao','separado','pronto_envio','enviado') NOT NULL DEFAULT 'em_separacao',
    criado_em      DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pedido_itens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id  INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- =============================================================
--  DADOS DE EXEMPLO (os mesmos que aparecem no protótipo)
--  Senha de TODOS os usuários: 123456
-- =============================================================
INSERT INTO usuarios (nome, email, telefone, senha, cargo) VALUES
('Felipe',  'estoquista@gmail.com', '(47) 99999-0001', '$2y$12$UsGELyiWitDalCkJ2wFGd.sYGp8FiozsBpv5UZNsDUOY7UZbsr2Fy', 'Estoquista'),
('Vitor',   'assistente@gmail.com', '(47) 99999-0002', '$2y$12$UsGELyiWitDalCkJ2wFGd.sYGp8FiozsBpv5UZNsDUOY7UZbsr2Fy', 'Assistente'),
('Gustavo', 'gerente@gmail.com',    '(47) 99999-0003', '$2y$12$UsGELyiWitDalCkJ2wFGd.sYGp8FiozsBpv5UZNsDUOY7UZbsr2Fy', 'Gerente'),
('João',    'supervisor@gmail.com', '(47) 99999-0004', '$2y$12$UsGELyiWitDalCkJ2wFGd.sYGp8FiozsBpv5UZNsDUOY7UZbsr2Fy', 'Supervisor'),
('Maria',   'vendedor@gmail.com',   '(47) 99999-0005', '$2y$12$UsGELyiWitDalCkJ2wFGd.sYGp8FiozsBpv5UZNsDUOY7UZbsr2Fy', 'Vendedor');

INSERT INTO historico_login (usuario_id, logou_em) VALUES
(1, CONCAT(CURDATE(), ' 14:23:00')),
(2, CONCAT(CURDATE(), ' 12:45:00')),
(3, CONCAT(CURDATE(), ' 10:20:00')),
(4, CONCAT(CURDATE(), ' 09:06:00')),
(5, CONCAT(CURDATE(), ' 08:06:00'));

INSERT INTO categorias (nome) VALUES
('Dispositivos e Wearables'),
('Periféricos e Acessórios'),
('Componentes e Peças'),
('Casa Inteligente'),
('Serviços especializados'),
('Máquina de lavar');

INSERT INTO produtos (nome, sku, preco, estoque, estoque_minimo, cod_barras, fornecedor, plataforma, breve_descricao, descricao, localizacao, categoria_id, status) VALUES
('Tampa Móvel de Vidro da Lavadora Brastemp', 'W11225557', 287.99, 36, 5, '7891129255571', 'Whirlpool S.A.', 'Mercado Livre',
 'Esta tampa é do tipo móvel superior, com acabamento seguro, indicada para a reposição e manutenção de lavadoras residenciais',
 'Marca: Brastemp / Whirlpool\nCódigo: W11225557 (Substituído por W11393157)\nTipo: Tampa móvel superior\nMaterial: Vidro temperado e acabamento plástico\nCapacidade: 12kg / 13kg / 14kg\nCor: Borda cinza-escuro e vidro transparente\nConexão: Dispositivo de segurança mecânico para interrupção de ciclo\nModelos compatíveis: BWK12A, BWK12ABANA, BWK12ABBNA, BWK13A, BWK13AB, BWK14A, BWK14ABANA, BWK14ABBNA',
 'Matriz - A 21 Caixa 5 / Filial C22', 6, 'publicado'),
('Filtro Secadora Brastemp', '326043145', 64.90, 12, 5, '7891129000011', 'Whirlpool S.A.', 'Mercado Livre',
 'Filtro de fiapos para secadoras Brastemp.', NULL, 'Matriz - B 03 Caixa 2', 3, 'publicado'),
('Válvula Lavadora Brastemp', 'W11245250', 49.99, 3, 5, '7891129000028', 'Whirlpool S.A.', 'Shopee',
 'Válvula de entrada de água para lavadoras.', NULL, 'Matriz - B 07 Caixa 1', 6, 'publicado'),
('Tampa Brastemp Bwg11', 'W10512610', 239.90, 8, 5, '7891129000035', 'Whirlpool S.A.', 'Mercado Livre',
 'Tampa para lavadora Brastemp modelo BWG11.', NULL, 'Filial - C 22', 6, 'publicado'),
('Teclado Mecânico RGB', 'TEC-RGB-01', 349.90, 50, 10, '7890000000011', 'TechParts Ltda', 'Loja própria',
 'Teclado mecânico com iluminação RGB.', NULL, 'Matriz - D 01', 2, 'publicado'),
('Mouse Gamer Pro', 'MOU-PRO-01', 159.90, 4, 10, '7890000000028', 'TechParts Ltda', 'Loja própria',
 'Mouse gamer 16000 DPI.', NULL, 'Matriz - D 02', 2, 'publicado'),
('Headset Bluetooth', 'HEA-BT-01', 219.90, 2, 5, '7890000000035', 'TechParts Ltda', 'Amazon',
 'Headset sem fio com microfone.', NULL, 'Matriz - D 03', 1, 'rascunho');

INSERT INTO movimentacoes (produto_id, tipo, quantidade, usuario_id, criado_em) VALUES
(5, 'entrada', 50, 3, NOW() - INTERVAL 1 HOUR),
(6, 'saida',   15, 3, NOW() - INTERVAL 2 HOUR),
(1, 'entrada', 20, 4, NOW() - INTERVAL 3 HOUR),
(7, 'saida',   10, 5, NOW() - INTERVAL 1 DAY),
(4, 'entrada',  5, 3, NOW() - INTERVAL 1 DAY);

INSERT INTO pedidos (numero, destinatario, transportadora, data_emissao, status) VALUES
('1385', 'Havan.SA', 'Correio', '2026-05-29', 'em_separacao'),
('1351', 'Havan.SA', 'Correio', '2026-06-09', 'em_separacao');

INSERT INTO pedido_itens (pedido_id, produto_id, quantidade) VALUES
(1, 1, 15),
(2, 1, 10);
