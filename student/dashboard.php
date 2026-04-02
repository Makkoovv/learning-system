<?php
require_once '../includes/functions.php';
checkUserType(['student']);

$db = new Database();
$conn = $db->getConnection();
$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_attempts,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_tasks,
        COALESCE(AVG(score), 0) as avg_score
    FROM assigned_tasks
    WHERE student_id = ?
");
$stmt->execute([$student_id]);
$stats = $stmt->fetch();

$total_attempts = (int)($stats['total_attempts'] ?? 0);
$completed_tasks = (int)($stats['completed_tasks'] ?? 0);
$avg_score = (float)($stats['avg_score'] ?? 0);

$stmt = $conn->prepare("
    SELECT 
        at.id,
        at.score,
        at.is_completed,
        at.attempts,
        at.last_attempt,
        t.title,
        t.task_type
    FROM assigned_tasks at
    JOIN tasks t ON t.id = at.task_id
    WHERE at.student_id = ?
    ORDER BY at.last_attempt DESC, at.assigned_date DESC
    LIMIT 5
");
$stmt->execute([$student_id]);
$recent = $stmt->fetchAll();

$type_names = [
    'sblizhenie' => 'Навстречу',
    'udalenie' => 'Удаление',
    'dogonka' => 'Вдогонку',
    'vstrecha' => 'Встреча в точке'
];

$page_title = 'Панель ученика';
$page_heading = 'Подготовка к ОГЭ';
$page_subtitle = 'Добро пожаловать, ' . ($_SESSION['full_name'] ?? 'Ученик') . '!';

require_once '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Всего решений</h3>
        <div class="stat-number"><?php echo $total_attempts; ?></div>
    </div>
    <div class="stat-card">
        <h3>Завершено задач</h3>
        <div class="stat-number"><?php echo $completed_tasks; ?></div>
    </div>
    <div class="stat-card">
        <h3>Средний балл</h3>
        <div class="stat-number"><?php echo number_format($avg_score, 1); ?></div>
    </div>
</div>

<div style="margin: 30px 0; display:flex; gap:15px; justify-content:center; flex-wrap:wrap;">
    <a href="start_training.php" class="btn btn-primary" style="padding:15px 30px; font-size:18px;">🚀 Начать тренировку</a>
    <a href="tasks.php" class="btn btn-secondary" style="padding:15px 30px; font-size:18px;">📚 Открыть список задач</a>
</div>

<div style="margin-top:30px;">
    <h3>Последние решения</h3>

    <?php if (empty($recent)): ?>
        <div class="empty-state">
            <p>У тебя пока нет решённых задач</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Задача</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Баллы</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                        <td><?php echo htmlspecialchars($type_names[$item['task_type']] ?? $item['task_type']); ?></td>
                        <td>
                            <?php if ($item['is_completed']): ?>
                                <span class="badge badge-success">Завершено</span>
                            <?php else: ?>
                                <span class="badge badge-warning">В работе</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$item['score']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>