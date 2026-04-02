<?php
require_once 'config.php';
require_once 'database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function redirect(string $url): void {
    // поддержка абсолютных путей вида /login.php
    if (str_starts_with($url, '/')) {
        header("Location: " . BASE_PATH . $url);
    } else {
        header("Location: $url");
    }
    exit();
}

function checkUserType(array $allowed_types): void {
    if (!isLoggedIn() || !in_array($_SESSION['user_type'], $allowed_types, true)) {
        redirect('/login.php');
    }
}

function sanitizeInput(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function requirePostCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('CSRF token invalid');
    }
}

function getDifficultyBadge($difficulty): string {
    $badges = [
        'easy' => '<span class="badge badge-success">Легкая</span>',
        'medium' => '<span class="badge badge-warning">Средняя</span>',
        'hard' => '<span class="badge badge-danger">Сложная</span>'
    ];
    return $badges[$difficulty] ?? $badges['medium'];
}

function getStatusBadge($status): string {
    if ($status == 1 || $status === true) {
        return '<span class="badge badge-success">Активен</span>';
    }
    return '<span class="badge badge-secondary">Неактивен</span>';
}

function formatDate($date, $format = 'd.m.Y H:i'): string {
    if (!$date || $date === '0000-00-00 00:00:00') return '-';
    return date($format, strtotime($date));
}

function validateUserId($id): bool {
    return is_numeric($id) && (int)$id > 0;
}

function getTaskTypeName($type): string {
    $names = [
        'sblizhenie' => 'Сближение',
        'udalenie' => 'Удаление',
        'dogonka' => 'В догонку',
        'vstrecha' => 'Встреча в точке'
    ];
    return $names[$type] ?? (string)$type;
}
?>