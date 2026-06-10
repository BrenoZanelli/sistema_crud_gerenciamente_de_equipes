<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../src/Models/User.php';

if (session_status()=== PHP_SESSION_NONE){
    session_start();
}

$erro= '';

if ($_SERVER['REQUEST_METHOD']==='POST'){
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password= $_POST['password'];

    if($email&&$password){
        $userModel=new User($pdo);
        $usuario=$userModel->findByEmail($email);

        if($usuario && password_verify($password,$usuario['senha_hash'])){

            $_SESSION['user_id']=$usuario['id'];
            $_SESSION['user_name']=$usuario['nome'];
            $_SESSION['user_role']=$usuario['position'];
            $_SESSION['teams_id']=$usuario['teams_id'];

            if($usuario['position']==='neutro'){
                header('Location: escolher_equipe.php');
            }elseif($usuario['position']==='gestor'){
                header('Location: dashboard.php');
            }else{
                header('Location: minhas_tarefas.php');
            }
            exit;

        }else{
            $erro='E-mail ou senha incorretos!';
        }

    }else{
        $erro='preencha todos os campos!';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Equipes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .login-card { max-width: 400px; margin-top: 100px; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="card shadow login-card w-100">
        <div class="card-body p-4">
            <h3 class="card-title text-center mb-4 text-primary">Login Corporativo</h3>
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $erro; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="seu@email.com" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Entrar</button>
                
                <div class="text-center">
                    <a href="cadastro.php" class="text-decoration-none text-muted small">Não tem conta? Cadastre-se</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>