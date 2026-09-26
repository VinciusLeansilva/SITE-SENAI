<?php
/*
 * Conexão com o banco de dados usando PDO.
 *
 * Por que PDO? Ele permite usar "prepared statements" (consultas
 * preparadas), que protegem contra SQL Injection: os valores digitados
 * pelo usuário nunca são misturados diretamente no texto do SQL.
 *
 * Ajuste as 4 constantes abaixo conforme o seu computador.
 * No XAMPP o padrão é usuário "root" e senha vazia.
 */
const DB_HOST  = 'localhost';
const DB_NOME  = 'stockcontrol';
const DB_USER  = 'root';
const DB_SENHA = '';

function conectar(): PDO
{
    // "static" guarda a conexão entre chamadas: conectamos só uma vez por página.
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_SENHA, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erros viram exceções
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como array associativo
            PDO::ATTR_EMULATE_PREPARES   => false,                  // usa prepared statements reais
        ]);
    }

    return $pdo;
}
