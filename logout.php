<?php
require 'includes/funcoes.php';

// Apaga os dados da sessão e destrói a sessão.
$_SESSION = [];
session_destroy();

redirecionar('login.php');
