<?php
// public/escolher_equipe.php
require_once __DIR__ . '/../config/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. PROTEÇÃO DA PÁGINA: Se não estiver logado, chuta para o login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. SE JÁ TIVER EQUIPE: Redireciona direto para a área dele
if (!empty($_SESSION['teams_id'])) {
    if ($_SESSION['user_role'] === 'gestor') {
        header('Location: dashboard.php');
    } else {
        header('Location: minhas_tarefas.php');
    }
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// 3. PROCESSAMENTO DOS FORMULÁRIOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ----- ROTA A: CRIAR EQUIPE -----
    if (isset($_POST['acao']) && $_POST['acao'] === 'criar') {
        $nome_equipe = filter_input(INPUT_POST, 'name_teams', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($nome_equipe) {
            try {
                $pdo->beginTransaction();

                // Gera um código único de 6 caracteres (letras maiúsculas e números)
                $codigo_convite = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

                // Insere a nova equipe no banco
                $sql_team = "INSERT INTO teams (name_teams, code) VALUES (:name_teams, :code)";
                $stmt_team = $pdo->prepare($sql_team);
                $stmt_team->execute([
                    'name_teams' => $nome_equipe,
                    'code' => $codigo_convite
                ]);

                // Pega o ID da equipe que acabou de ser criada
                $team_id = $pdo->lastInsertId();

                // Atualiza o usuário atual para 'gestor' e vincula ao ID da equipe
                $sql_user = "UPDATE users SET position = 'gestor', teams_id = :teams_id WHERE id = :id";
                $stmt_user = $pdo->prepare($sql_user);
                $stmt_user->execute([
                    'teams_id' => $team_id,
                    'id' => $_SESSION['user_id']
                ]);

                // Atualiza as variáveis de sessão para refletir o novo papel
                $_SESSION['user_role'] = 'gestor';
                $_SESSION['teams_id'] = $team_id;

                $pdo->commit();

                // Redireciona para o Dashboard do Gestor
                header('Location: dashboard.php');
                exit;

            } catch (\PDOException $e) {
                $pdo->rollBack();
                $mensagem = 'Erro ao criar equipe: ' . $e->getMessage();
                $tipo_mensagem = 'danger';
            }
        } else {
            $mensagem = 'Preencha o nome da equipe!';
            $tipo_mensagem = 'danger';
        }
    }

    // ----- ROTA B: ENTRAR EM EQUIPE -----
    if (isset($_POST['acao']) && $_POST['acao'] === 'entrar') {
        $codigo_busca = strtoupper(trim($_POST['code']));

        if ($codigo_busca) {
            // Busca se o código de convite realmente existe
            $sql_busca = "SELECT id FROM teams WHERE code = :code";
            $stmt_busca = $pdo->prepare($sql_busca);
            $stmt_busca->execute(['code' => $codigo_busca]);
            $equipe = $stmt_busca->fetch();

            if ($equipe) {
                try {
                    // Atualiza o usuário para 'colaborador' e vincula ao ID da equipe encontrada
                    $sql_update = "UPDATE users SET position = 'colaborador', teams_id = :teams_id WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->execute([
                        'teams_id' => $equipe['id'],
                        'id' => $_SESSION['user_id']
                    ]);

                    // Atualiza as variáveis de sessão
                    $_SESSION['user_role'] = 'colaborador';
                    $_SESSION['teams_id'] = $equipe['id'];

                    // Redireciona para a tela de tarefas do funcionário
                    header('Location: minhas_tarefas.php');
                    exit;

                } catch (\PDOException $e) {
                    $mensagem = 'Erro ao entrar na equipe: ' . $e->getMessage();
                    $tipo_mensagem = 'danger';
                }
            } else {
                $mensagem = 'Código de convite inválido ou inexistente!';
                $tipo_mensagem = 'danger';
            }
        } else {
            $mensagem = 'Preencha o código de convite!';
            $tipo_mensagem = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Equipe - Sistema Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .setup-container { margin-top: 80px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Olá, <?= htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="login.php" class="btn btn-outline-light btn-sm">Sair</a>
    </div>
</nav>

<div class="container setup-container">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            
            <h2 class="text-center mb-2 text-secondary">Boas-vindas ao Sistema!</h2>
            <p class="text-center text-muted mb-4">Para começar, você precisa criar uma equipe ou fazer parte de uma já existente.</p>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= $tipo_mensagem; ?> text-center" role="alert">
                    <?= $mensagem; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-primary">
                        <div class="card-body p-4 d-flex flex-column">
                            <h4 class="card-title text-primary mb-3">Sou Gestor</h4>
                            <p class="card-text text-muted small flex-grow-1">
                                Crie um novo grupo de trabalho corporativo. Você será o administrador padrão e poderá atribuir e monitorar tarefas da sua equipe.
                            </p>
                            <form action="escolher_equipe.php" method="POST" class="mt-3">
                                <input type="hidden" name="acao" value="criar">
                                <div class="mb-3">
                                    <label for="name_teams" class="form-label font-monospace small">NOME DA EQUIPE</label>
                                    <input type="text" name="name_teams" id="name_teams" class="form-control" placeholder="Ex: Time de Desenvolvimento" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Criar Equipe & Entrar</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-success">
                        <div class="card-body p-4 d-flex flex-column">
                            <h4 class="card-title text-success mb-3">Sou Colaborador</h4>
                            <p class="card-text text-muted small flex-grow-1">
                                Se você recebeu um código de convite de 6 dígitos do seu gestor, insira-o abaixo para se vincular instantaneamente ao seu time.
                            </p>
                            <form action="escolher_equipe.php" method="POST" class="mt-3">
                                <input type="hidden" name="acao" value="entrar">
                                <div class="mb-3">
                                    <label for="code" class="form-label font-monospace small">CÓDIGO DE CONVITE</label>
                                    <input type="text" name="code" id="code" class="form-control text-uppercase font-monospace text-center" placeholder="Ex: A1B2C3" maxlength="6" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Validar Código & Entrar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>