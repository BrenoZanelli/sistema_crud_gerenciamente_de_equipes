<?php

$host='db';
$db='sistema_equipes';
$user='dev_user';
$pass='dev_password';
$charset='utf8mb4';

$dsn="mysql:host=$host;dbname=$db;charset=$charset";

$options=[
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Transforma erros do SQL em exceções do PHP
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, // Retorna os dados como arrays associativos limpos
    PDO::ATTR_EMULATE_PREPARES=> false, // Usa prepares reais do MySQL para evitar SQL Injection
];

try{
    $pdo=new PDO($dsn,$user,$pass,$options);
} catch (\PDOException $e){
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>