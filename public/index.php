<?php

require_once __DIR__ . '/../config/conexao.php';

if (isset($pdo)){
    echo "<h1>Sucesso! O php se conectou ao banco de dados dentro do docker </h1>";
}
?>