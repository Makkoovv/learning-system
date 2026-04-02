<?php
require_once '../includes/functions.php';
checkUserType(['teacher', 'admin']);

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: students.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$student_id = (int)$_GET['id'];
$teacher_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, username, full_name, email, created_at
    FROM users
    WHERE id = ? AND user_type = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = 'Ученик не найден';
    header('Location: students.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        COUNT(at.id) as total_tasks,
        SUM(at.is_completed) as completed_tasks,
        AVG(at.score) as avg_score,
        MAX(at.score) as best_score,
        SUM(at.attempts) as total_attempts
    FROM assigned_tasks at
    WHERE at.student_id = ? AND at.teacher_id = ?
");
$stmt->execute([$student_id, $teacher_id]);
$stats = $stmt->fetch();

if (!$stats) {
    $stats = [
        'total_tasks' => 0,
        'completed_tasks' => 0,
        'avg_score' => 0,
        'best_score' => 0,
        'total_attempts' => 0
    ];
}

$stmt = $conn->prepare("
    SELECT 
        at.*,
        t.title,
        t.task_type,
        t.difficulty
    FROM assigned_tasks at
    JOIN tasks t ON t.id = at.task_id
    WHERE at.student_id = ? AND at.teacher_id = ?
    ORDER BY at.assigned_date DESC
");
$stmt->execute([$student_id, $teacher_id]);
$tasks = $stmt->fetchAll();

$type_names = [
    'sblizhenie' => 'Навстречу',
    'udalenie' => 'Удаление',
    'dogonka' => 'Вдогонку',
    'vstrecha' => 'Встреча в точке'
];

$page_title = 'Прогресс ученика';
$page_heading = 'Прогресс ученика';
$page_subtitle = $student['full_name'];

require_once '../includes/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="students.php" class="btn btn-secondary">← Назад к списку учеников</a>
</div>

<div class="stat-card" style="margin-bottom: 30px;">
    <h3>Информация об ученике</h3>
    <p><strong>ФИО:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
    <p><strong>Логин:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
    <p><strong>Дата регистрации:</strong> <?php echo formatDate($student['created_at'], 'd.m.Y'); ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Всего решений</h3>
        <div class="stat-number"><?php echo (int)$stats['total_tasks']; ?></div>
    </div>
    <div class="stat-card">
        <h3>Завершено</h3>
        <div class="stat-number"><?php echo (int)$stats['completed_tasks']; ?></div>
    </div>
    <div class="stat-card">
        <h3>Средний балл</h3>
        <div class="stat-number"><?php echo round((float)$stats['avg_score'], 1); ?></div>
    </div>
    <div class="stat-card">
        <h3>Попыток</h3>
        <div class="stat-number"><?php echo (int)$stats['total_attempts']; ?></div>
    </div>
</div>

<div style="margin-top: 30px;">
    <h3>История решений</h3>

    <?php if (empty($tasks)): ?>
        <div class="empty-state">
            <p>Ученик ещё не решал ваши задачи</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Задача</th>
                    <th>Тип</th>
                    <th>Сложность</th>
                    <th>Статус</th>
                    <th>Баллы</th>
                    <th>Попыток</th>
                    <th>Дата</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                        <td><?php echo htmlspecialchars($type_names[$task['task_type']] ?? $task['task_type']); ?></td>
                        <td><?php echo getDifficultyBadge($task['difficulty']); ?></td>
                        <td>
                            <?php if ($task['is_completed']): ?>
                                <span class="badge badge-success">Завершено</span>
                            <?php else: ?>
                                <span class="badge badge-warning">В работе</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$task['score']; ?></td>
                        <td><?php echo (int)$task['attempts']; ?></td>
                        <td><?php echo formatDate($task['assigned_date'], 'd.m.Y'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>