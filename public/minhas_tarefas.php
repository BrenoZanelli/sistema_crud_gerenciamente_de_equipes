<?php
require_once __DIR__ . '/../config/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


//garante que só o funcionario vai estar logado na pagina
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'colaborador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$teams_id = $_SESSION['teams_id'];
$mensagem = '';
$tipo_mensagem = '';

//atualização da tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $novo_status = $_POST['status'];

    // Lista de status permitidos para evitar alterações maliciosas
    $status_validos = ['em andamento', 'concluida'];

    if ($task_id && in_array($novo_status, $status_validos)) {
        try {
            // Se for concluída, salvamos também a data atual em 'finished_at'
            if ($novo_status === 'concluida') {
                $sql_update = "UPDATE tasks SET status = :status, finished_at = NOW() 
                               WHERE id = :task_id AND user_id = :user_id";
            } else {
                $sql_update = "UPDATE tasks SET status = :status WHERE id = :task_id AND user_id = :user_id";
            }

            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                'status'  => $novo_status,
                'task_id' => $task_id,
                'user_id' => $user_id
            ]);

            $mensagem = "Status da tarefa atualizado com sucesso!";
            $tipo_mensagem = "success";
        } catch (\PDOException $e) {
            $mensagem = "Erro ao atualizar tarefa: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
}

// busca as tarefas do funcionario
$sql_tasks = "SELECT * FROM tasks WHERE user_id = :user_id AND teams_id = :teams_id ORDER BY create_at DESC";
$stmt_tasks = $pdo->prepare($sql_tasks);
$stmt_tasks->execute([
    'user_id'  => $user_id,
    'teams_id' => $teams_id
]);
$minhas_tarefas = $stmt_tasks->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Tarefas - Painel do Colaborador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .card-tarefa { transition: transform 0.2s; }
        .card-tarefa:hover { transform: translateY(-2px); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="#">
            Espaço do Colaborador <span class="text-muted small fs-6">| Minhas Atividades</span>
        </a>
        <div class="d-flex align-items-center">
            <span class="text-light me-3">Olá, <?= htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="login.php" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-secondary mb-0">Quadro de Tarefas</h3>
                <span class="badge bg-primary px-3 py-2">Total: <?= count($minhas_tarefas); ?></span>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= $tipo_mensagem; ?> alert-dismissible fade show shadow-sm text-center" role="alert">
                    <?= $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($minhas_tarefas)): ?>
                <div class="card shadow-sm border-0 text-center py-5 bg-white">
                    <div class="card-body">
                        <p class="text-muted fs-5 mb-0">🎉 Excelente! Você não tem nenhuma tarefa pendente no momento.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($minhas_tarefas as $tarefa): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0 card-tarefa bg-white">
                                <div class="card-body p-4 d-flex flex-column">
                                    
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title text-dark mb-0 fw-bold"><?= htmlspecialchars($tarefa['title']); ?></h5>
                                        
                                        <?php if($tarefa['priority'] === 'urgente'): ?>
                                            <span class="badge bg-danger">Urgente</span>
                                        <?php elseif($tarefa['priority'] === 'media'): ?>
                                            <span class="badge bg-warning text-dark">Média</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Baixa</span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="card-text text-muted small flex-grow-1 mt-2">
                                        <?= nl2br(htmlspecialchars($tarefa['description'])); ?>
                                    </p>
                                    
                                    <hr class="text-muted opacity-25">

                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <div>
                                            <span class="small text-muted d-block font-monospace" style="font-size: 0.75rem;">STATUS ATUAL</span>
                                            <?php if($tarefa['status'] === 'concluida'): ?>
                                                <span class="badge bg-success">Concluída</span>
                                            <?php elseif($tarefa['status'] === 'em andamento'): ?>
                                                <span class="badge bg-primary">Em Andamento</span>
                                            <?php else: ?>
                                                <span class="badge bg-dark-subtle text-dark border">Em Espera</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="actions">
                                            <?php if($tarefa['status'] === 'em espera'): ?>
                                                <form action="minhas_tarefas.php" method="POST">
                                                    <input type="hidden" name="task_id" value="<?= $tarefa['id']; ?>">
                                                    <input type="hidden" name="status" value="em andamento">
                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm px-3">Iniciar Trabalho</button>
                                                </form>
                                            <?php elseif($tarefa['status'] === 'em andamento'): ?>
                                                <form action="minhas_tarefas.php" method="POST">
                                                    <input type="hidden" name="task_id" value="<?= $tarefa['id']; ?>">
                                                    <input type="hidden" name="status" value="concluida">
                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                    <button type="submit" class="btn btn-success btn-sm px-3">Concluir Tarefa</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small italic">Finalizada em: <br>
                                                    <small class="font-monospace"><?= date('d/m/Y H:i', strtotime($tarefa['finished_at'])); ?></small>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>