<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_root = !str_contains($current_path, '/admin/') &&
           !str_contains($current_path, '/teacher/') &&
           !str_contains($current_path, '/student/');

$asset_prefix = $is_root ? 'assets/' : '../assets/';
$logout_link = $is_root ? 'logout.php' : '../logout.php';

$page_title = $page_title ?? SITE_NAME;
$page_heading = $page_heading ?? SITE_NAME;
$page_subtitle = $page_subtitle ?? '';

$user_type = $_SESSION['user_type'] ?? null;

function renderNav(string $user_type = null, string $logout_link = 'logout.php'): array {
    if ($user_type === 'admin') {
        return [
            'dashboard.php' => 'Главная',
            'users.php' => 'Пользователи',
            'tasks.php' => 'Задачи',
            $logout_link => 'Выход',
        ];
    }

    if ($user_type === 'teacher') {
        return [
            'dashboard.php' => 'Главная',
            'tasks.php' => 'Мои задачи',
            'students.php' => 'Студенты',
            'add_task.php' => 'Создать задачу',
            $logout_link => 'Выход',
        ];
    }

    if ($user_type === 'student') {
        return [
            'dashboard.php' => 'Главная',
            'start_training.php' => 'Начать тренировку',
            'tasks.php' => 'Список задач',
            $logout_link => 'Выход',
        ];
    }

    return [
        'index.php' => 'Главная',
        'login.php' => 'Вход',
        'register.php' => 'Регистрация',
    ];
}

$nav_items = renderNav($user_type, $logout_link);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $asset_prefix; ?>css/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1><?php echo htmlspecialchars($page_heading); ?></h1>
        <?php if ($page_subtitle !== ''): ?>
            <p><?php echo htmlspecialchars($page_subtitle); ?></p>
        <?php endif; ?>

        <nav>
            <?php foreach ($nav_items as $href => $label): ?>
                <a href="<?php echo htmlspecialchars($href); ?>">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </header>

    <main>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>