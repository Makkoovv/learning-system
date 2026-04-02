<?php
require_once '../includes/functions.php';
checkUserType(['admin']);

$db = new Database();
$conn = $db->getConnection();

$stats = [];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as total FROM users")->fetch()['total'] ?? 0;
$stats['admins'] = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'admin'")->fetch()['total'] ?? 0;
$stats['teachers'] = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'teacher'")->fetch()['total'] ?? 0;
$stats['students'] = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'student'")->fetch()['total'] ?? 0;
$stats['total_tasks'] = $conn->query("SELECT COUNT(*) as total FROM tasks")->fetch()['total'] ?? 0;
$stats['attempts'] = $conn->query("SELECT COUNT(*) as total FROM assigned_tasks")->fetch()['total'] ?? 0;

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

$page_title = 'Панель администратора';
$page_heading = 'Панель администратора';
$page_subtitle = 'Добро пожаловать, ' . ($_SESSION['full_name'] ?? 'Администратор') . '!';

require_once '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Всего пользователей</h3>
        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
        <p>
            <?php echo $stats['admins']; ?> админ. /
            <?php echo $stats['teachers']; ?> преп. /
            <?php echo $stats['students']; ?> уч.
        </p>
    </div>

    <div class="stat-card">
        <h3>Всего задач</h3>
        <div class="stat-number"><?php echo $stats['total_tasks']; ?></div>
        <p>в общем банке</p>
    </div>

    <div class="stat-card">
        <h3>Всего решений</h3>
        <div class="stat-number"><?php echo $stats['attempts']; ?></div>
        <p>попыток учеников</p>
    </div>
</div>

<div style="margin-top: 30px;">
    <h3>Последние пользователи</h3>
    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Логин</th>
                <th>ФИО</th>
                <th>Город</th>
                <th>Школа</th>
                <th>Тип</th>
                <th>Статус</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo (int)$user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['city'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['school'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                        <td><?php echo $user['is_active'] ? 'Активен' : 'Неактивен'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Нет пользователей</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 30px; display:flex; gap:10px;">
    <a href="users.php" class="btn btn-primary">Управление пользователями</a>
    <a href="tasks.php" class="btn btn-secondary">Управление задачами</a>
</div>

<?php require_once '../includes/footer.php'; ?>