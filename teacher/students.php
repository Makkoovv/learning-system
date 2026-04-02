<?php
require_once '../includes/functions.php';
checkUserType(['teacher', 'admin']);

$db = new Database();
$conn = $db->getConnection();
$teacher_id = $_SESSION['user_id'];

$students = $conn->prepare("
    SELECT 
        u.id,
        u.username,
        u.full_name,
        u.email,
        u.created_at,
        COUNT(at.id) as total_tasks,
        SUM(at.is_completed) as completed_tasks,
        AVG(at.score) as avg_score
    FROM users u
    LEFT JOIN assigned_tasks at 
        ON u.id = at.student_id
        AND at.teacher_id = ?
    WHERE u.user_type = 'student' AND u.is_active = 1
    GROUP BY u.id
    ORDER BY u.full_name
");
$students->execute([$teacher_id]);
$students = $students->fetchAll();

$page_title = 'Ученики';
$page_heading = 'Ученики';
$page_subtitle = 'Статистика учеников по вашим задачам';

require_once '../includes/header.php';
?>

<h2>Все ученики (<?php echo count($students); ?>)</h2>

<?php if (empty($students)): ?>
    <div class="empty-state">
        <p>Нет учеников в системе</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Логин</th>
                <th>Email</th>
                <th>Решений</th>
                <th>Завершено</th>
                <th>Средний балл</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo (int)$student['id']; ?></td>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo (int)($student['total_tasks'] ?? 0); ?></td>
                    <td><?php echo (int)($student['completed_tasks'] ?? 0); ?></td>
                    <td>
                        <?php
                        echo $student['avg_score'] !== null ? round((float)$student['avg_score'], 1) : '-';
                        ?>
                    </td>
                    <td>
                        <a href="student_progress.php?id=<?php echo (int)$student['id']; ?>" class="btn btn-small btn-primary">Прогресс</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>