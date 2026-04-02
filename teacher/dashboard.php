<?php
require_once '../includes/functions.php';
checkUserType(['teacher', 'admin']);

$db = new Database();
$conn = $db->getConnection();
$teacher_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as total_tasks
    FROM tasks
    WHERE created_by = ?
");
$stmt->execute([$teacher_id]);
$task_stats = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT at.student_id) as total_students,
        COUNT(at.id) as total_attempts,
        COALESCE(AVG(at.score), 0) as avg_score
    FROM tasks t
    LEFT JOIN assigned_tasks at ON at.task_id = t.id
    WHERE t.created_by = ?
");
$stmt->execute([$teacher_id]);
$stats = $stmt->fetch();

$total_tasks = (int)($task_stats['total_tasks'] ?? 0);
$total_students = (int)($stats['total_students'] ?? 0);
$total_attempts = (int)($stats['total_attempts'] ?? 0);
$avg_score = (float)($stats['avg_score'] ?? 0);

$page_title = 'Панель преподавателя';
$page_heading = 'Панель преподавателя';
$page_subtitle = 'Добро пожаловать, ' . ($_SESSION['full_name'] ?? 'Преподаватель') . '!';

require_once '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Мои задачи</h3>
        <div class="stat-number"><?php echo $total_tasks; ?></div>
        <p>опубликовано в общий доступ</p>
    </div>

    <div class="stat-card">
        <h3>Ученики решали</h3>
        <div class="stat-number"><?php echo $total_students; ?></div>
        <p>по вашим задачам</p>
    </div>

    <div class="stat-card">
        <h3>Всего решений</h3>
        <div class="stat-number"><?php echo $total_attempts; ?></div>
        <p>попыток учеников</p>
    </div>

    <div class="stat-card">
        <h3>Средний балл</h3>
        <div class="stat-number"><?php echo number_format($avg_score, 1); ?></div>
        <p>по всем вашим задачам</p>
    </div>
</div>

<div style="margin-top: 30px; display:flex; gap:10px; flex-wrap:wrap;">
    <a href="add_task.php" class="btn btn-primary">Создать задачу</a>
    <a href="tasks.php" class="btn btn-secondary">Открыть мои задачи</a>
    <a href="students.php" class="btn btn-success">Статистика учеников</a>
</div>

<?php require_once '../includes/footer.php'; ?>