<?php
require_once '../includes/functions.php';
checkUserType(['teacher', 'admin']);

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    requirePostCsrf();

    $task_id = (int)($_POST['task_id'] ?? 0);

    if ($task_id <= 0) {
        $_SESSION['error'] = 'Некорректный ID задачи';
        header('Location: tasks.php');
        exit();
    }

    if ($user_type === 'admin') {
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
    } else {
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND created_by = ?");
        $stmt->execute([$task_id, $user_id]);
    }

    $_SESSION['success'] = 'Задача удалена';
    header('Location: tasks.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        t.*,
        COUNT(DISTINCT at.id) AS solved_count,
        COUNT(DISTINCT at.student_id) AS students_count
    FROM tasks t
    LEFT JOIN assigned_tasks at ON at.task_id = t.id
    WHERE t.created_by = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

$type_names = [
    'sblizhenie' => 'Навстречу',
    'udalenie' => 'Удаление',
    'dogonka' => 'Вдогонку',
    'vstrecha' => 'Встреча в точке'
];

$page_title = 'Мои задачи';
$page_heading = 'Мои задачи';
$page_subtitle = 'Все задачи, опубликованные в общий доступ';

require_once '../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
    <h2>Все задачи</h2>
    <a href="add_task.php" class="btn btn-primary">+ Создать задачу</a>
</div>

<?php if (empty($tasks)): ?>
    <div class="empty-state">
        <p>У вас пока нет задач</p>
        <a href="add_task.php" class="btn btn-primary">Создать первую задачу</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Тип</th>
                <th>Сложность</th>
                <th>Решений</th>
                <th>Учеников</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?php echo (int)$task['id']; ?></td>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><?php echo htmlspecialchars($type_names[$task['task_type']] ?? $task['task_type']); ?></td>
                    <td><?php echo getDifficultyBadge($task['difficulty']); ?></td>
                    <td><?php echo (int)$task['solved_count']; ?></td>
                    <td><?php echo (int)$task['students_count']; ?></td>
                    <td><?php echo formatDate($task['created_at'], 'd.m.Y'); ?></td>
                    <td>
                        <a href="add_task.php?id=<?php echo (int)$task['id']; ?>" class="btn btn-small btn-secondary">Редактировать</a>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить задачу?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="delete_task" value="1">
                            <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                            <button type="submit" class="btn btn-small btn-danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>