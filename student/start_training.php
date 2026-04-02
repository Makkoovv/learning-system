<?php
require_once '../includes/functions.php';
checkUserType(['student']);

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("
    SELECT id
    FROM tasks
    ORDER BY RAND()
    LIMIT 1
");
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = 'Пока нет доступных задач';
    header('Location: dashboard.php');
    exit();
}

header('Location: solve_task.php?task_id=' . (int)$task['id']);
exit();