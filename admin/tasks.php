<?php
require_once '../includes/functions.php';
checkUserType(['admin']);

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    requirePostCsrf();

    $task_id = (int)($_POST['task_id'] ?? 0);

    if ($task_id > 0) {
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        $_SESSION['success'] = 'Задача удалена';
    } else {
        $_SESSION['error'] = 'Некорректный ID задачи';
    }

    header('Location: tasks.php');
    exit();
}

$tasks = $conn->query("
    SELECT t.*, u.full_name AS teacher_name
    FROM tasks t
    LEFT JOIN users u ON u.id = t.created_by
    ORDER BY t.created_at DESC
")->fetchAll();

$type_names = [
    'sblizhenie' => 'Навстречу',
    'udalenie' => 'Удаление',
    'dogonka' => 'Вдогонку',
    'vstrecha' => 'Встреча в точке'
];

$page_title = 'Все задачи';
$page_heading = 'Все задачи';
$page_subtitle = 'Управление задачами';

require_once '../includes/header.php';
?>

<h2>Все задачи (<?php echo count($tasks); ?>)</h2>

<?php if (empty($tasks)): ?>
    <div class="empty-state">
        <p>Задач пока нет</p>
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
                <th>Автор</th>
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
                    <td><?php echo htmlspecialchars($task['teacher_name'] ?? '—'); ?></td>
                    <td><?php echo formatDate($task['created_at'], 'd.m.Y'); ?></td>
                    <td>
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