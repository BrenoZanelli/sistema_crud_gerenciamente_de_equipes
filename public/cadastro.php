<?php

require_once __DIR__ . '/../config/conexao.php';

$mensagem= '';
$tipo_mensagem= '';

if ($_SERVER['REQUEST_METHOD']==='POST'){
    $nome=filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email=filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password=$_POST['password'];


    if($nome && $email && $password){
      try {
            // 1. Transforma a senha pura em um HASH seguro usando BCrypt
            $senha_hash = password_hash($password, PASSWORD_DEFAULT);

            // 2. Query corrigida alinhada estritamente com as colunas do SEU banco
            // Passamos 'neutro' e NULL diretamente como parâmetros seguros no execute
            $sql = "INSERT INTO users (nome, email, senha_hash, position, teams_id) 
                    VALUES (:nome, :email, :senha_hash, :position, :teams_id)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nome'       => $nome,
                'email'      => $email,
                'senha_hash' => $senha_hash,
                'position'   => 'neutro', // Alinhado com o seu banco
                'teams_id'   => null      // Alinhado com o seu banco (teams_id aceita NULL)
            ]);

            $mensagem = 'Cadastro realizado com sucesso! Você já pode fazer login.';
            $tipo_mensagem = 'success';
        }catch(\PDOException $e){

            if ($e->getCode()==23000){
                $mensagem='Este e-mail já está cadastrado no sistema!';
            }else{
                $mensagem='Erro ao cadastrar: '. $e->getMessage();
            }
            $tipo_mensagem='danger';
        }
    }else{
        $mensagem='POr favor, preencha todos os campos corretamente.';
        $tipo_mensagem='danger';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Equipes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .cadastro-card { max-width: 450px; margin-top: 80px; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="card shadow cadastro-card w-100">
        <div class="card-body p-4">
            <h3 class="card-title text-center mb-4 text-primary">Criar Conta</h3>
            
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= $tipo_mensagem; ?>" role="alert">
                    <?= $mensagem; ?>
                </div>
            <?php endif; ?>

            <form action="cadastro.php" method="POST">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome Completo</label>
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="João Silva" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail Corporativo</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="nome@empresa.com" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Mínimo 6 caracteres" minlength="6" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Cadastrar</button>
                
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none text-muted small">Já tem uma conta? Faça Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>