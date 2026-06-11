<?php
require_once __DIR__ . '/../config/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


//garante que seja o gestor que esta logado na pagina
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'gestor') {
    header('Location: login.php');
    exit;
}

$teams_id = $_SESSION['teams_id'];
$mensagem = '';
$tipo_mensagem = '';

//busca infos. da equipe para mostrar
$sql_team = "SELECT name_teams, code FROM teams WHERE id = :teams_id";
$stmt_team = $pdo->prepare($sql_team);
$stmt_team->execute(['teams_id' => $teams_id]);
$equipe = $stmt_team->fetch();

//criaçao de tarefas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'nova_tarefa') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $priority = $_POST['priority'];
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($title && $description && $priority && $user_id) {
        try {
            $sql_task = "INSERT INTO tasks (title, description, priority, status, user_id, teams_id) 
                         VALUES (:title, :description, :priority, 'em espera', :user_id, :teams_id)";
            $stmt_task = $pdo->prepare($sql_task);
            $stmt_task->execute([
                'title'       => $title,
                'description' => $description,
                'priority'    => $priority,
                'user_id'     => $user_id,
                'teams_id'    => $teams_id
            ]);

            $mensagem = "Tarefa atribuída com sucesso!";
            $tipo_mensagem = "success";
        } catch (\PDOException $e) {
            $mensagem = "Erro ao criar tarefa: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos corretamente.";
        $tipo_mensagem = "danger";
    }
}

//mostra os trabalhadores disponiveis
$sql_colabs = "SELECT id, nome FROM users WHERE teams_id = :teams_id AND position = 'colaborador'";
$stmt_colabs = $pdo->prepare($sql_colabs);
$stmt_colabs->execute(['teams_id' => $teams_id]);
$colaboradores = $stmt_colabs->fetchAll();

//mostra todas as tarefas do time
$sql_tasks = "SELECT t.*, u.nome AS nome_colaborador 
              FROM tasks t 
              JOIN users u ON t.user_id = u.id 
              WHERE t.teams_id = :teams_id 
              ORDER BY t.create_at DESC";
$stmt_tasks = $pdo->prepare($sql_tasks);
$stmt_tasks->execute(['teams_id' => $teams_id]);
$tarefas = $stmt_tasks->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Gestor - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-brand span { font-size: 0.85rem; color: #adb5bd; }
        .badge-prioridade { font-size: 0.75rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="#">
            Painel do Gestor <br class="d-md-none">
            <span class="ms-md-2">| Equipe: <strong><?= htmlspecialchars($equipe['name_teams']); ?></strong></span>
        </a>
        <div class="d-flex align-items-center">
            <span class="text-light me-3 d-none d-md-inline">Olá, <?= htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="login.php" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    
    <div class="card bg-white shadow-sm border-0 mb-4">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center g-3">
            <div>
                <h4 class="mb-1 text-secondary">Código de Convite da Equipe</h4>
                <p class="text-muted small mb-0">Compartilhe este código com seus funcionários para que eles entrem no seu time.</p>
            </div>
            <div class="text-md-end mt-3 mt-md-0">
                <span class="fs-2 fw-bold text-primary border border-primary border-2 px-3 py-1 rounded font-monospace bg-light">
                    <?= htmlspecialchars($equipe['code']); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?= $tipo_mensagem; ?> alert-dismissible fade show shadow-sm text-center" role="alert">
            <?= $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark mb-3">Distribuir Nova Tarefa</h5>
                    <hr>
                    
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="acao" value="nova_tarefa">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label small fw-bold text-secondary">TÍTULO DA TAREFA</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="Ex: Ajustar Query do Banco" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label small fw-bold text-secondary">DESCRIÇÃO DETALHADA</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Explique o que deve ser feito..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label small fw-bold text-secondary">GRAU DE PRIORIDADE</label>
                            <select name="priority" id="priority" class="form-select" required>
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Média</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="user_id" class="form-label small fw-bold text-secondary">DELEGAR PARA</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="" disabled selected>Selecione um funcionário...</option>
                                <?php if (empty($colaboradores)): ?>
                                    <option value="" disabled>Nenhum colaborador cadastrado no time ainda.</option>
                                <?php else: ?>
                                    <?php foreach ($colaboradores as $colab): ?>
                                        <option value="<?= $colab['id']; ?>"><?= htmlspecialchars($colab['nome']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mt-2" <?= empty($colaboradores) ? 'disabled' : ''; ?>>
                            Atribuir Tarefa
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark mb-3">Monitoramento de Atividades</h5>
                    <hr>
                    
                    <?php if (empty($tarefas)): ?>
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">Nenhuma tarefa foi distribuída para esta equipe até o momento.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tarefa</th>
                                        <th>Responsável</th>
                                        <th>Prioridade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tarefas as $task): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-dark d-block"><?= htmlspecialchars($task['title']); ?></strong>
                                                <small class="text-muted d-block text-truncate" style="max-width: 250px;">
                                                    <?= htmlspecialchars($task['description']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary rounded-pill">
                                                    <?= htmlspecialchars($task['nome_colaborador']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($task['priority'] === 'urgente'): ?>
                                                    <span class="badge bg-danger badge-prioridade">Urgente</span>
                                                <?php elseif($task['priority'] === 'media'): ?>
                                                    <span class="badge bg-warning text-dark badge-prioridade">Média</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info text-dark badge-prioridade">Baixa</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($task['status'] === 'concluida'): ?>
                                                    <span class="badge bg-success">Concluída</span>
                                                <?php elseif($task['status'] === 'em andamento'): ?>
                                                    <span class="badge bg-primary">Em Andamento</span>
                                                <?php else: ?>
                                                    <span class="badge bg-dark-subtle text-dark border">Em Espera</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>