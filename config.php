<?php
// 公共配置文件：数据库连接、Session、通用函数
// 使用前请按本地环境修改数据库账号和密码（phpStudy / XAMPP 默认 root 无密码或 root）

define('DB_HOST', 'localhost');
define('DB_NAME', 'interest_community');
define('DB_USER', 'root');
define('DB_PASS', '');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('数据库连接失败：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        . '<br>请确认已导入 sql/install.sql，并检查 config.php 中的数据库账号密码。');
}

// 输出转义，防止 XSS
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function is_login()
{
    return !empty($_SESSION['user_id']);
}

function is_admin()
{
    return is_login() && ($_SESSION['role'] ?? '') === 'admin';
}

function require_login()
{
    if (!is_login()) {
        redirect('login.php');
    }
    // 每次受保护页面都复查账号状态：被管理员封禁后立即失效
    global $pdo;
    $stmt = $pdo->prepare('SELECT status FROM `user` WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $status = $stmt->fetchColumn();
    if ($status === false || (int)$status !== 1) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash'] = '该账号已被封禁或不存在，无法继续使用。';
        redirect('login.php');
    }
}

function require_admin()
{
    require_login();
    if (!is_admin()) {
        redirect('index.php');
    }
}

// 一次性提示信息
function set_flash($msg)
{
    $_SESSION['flash'] = $msg;
}

function show_flash()
{
    if (!empty($_SESSION['flash'])) {
        echo '<div class="flash">' . e($_SESSION['flash']) . '</div>';
        unset($_SESSION['flash']);
    }
}

// 防重复提交：表单令牌（生成 + 校验，校验成功后立即作废）
function form_token()
{
    if (empty($_SESSION['form_token'])) {
        $_SESSION['form_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['form_token'];
}

function check_form_token()
{
    $token = $_POST['form_token'] ?? '';
    if (empty($token) || empty($_SESSION['form_token']) || !hash_equals($_SESSION['form_token'], $token)) {
        return false;
    }
    unset($_SESSION['form_token']); // 用过即废，刷新/重复提交会被拦截
    return true;
}

// 分页：根据总数和每页条数算出当前页与 OFFSET，并输出页码链接（$baseUrl 需已带 ? 或 & 之外的参数）
function paginate($total, $perPage, $pageParam = 'page')
{
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = isset($_GET[$pageParam]) ? (int)$_GET[$pageParam] : 1;
    if ($page < 1) $page = 1;
    if ($page > $totalPages) $page = $totalPages;
    return [$page, $totalPages, ($page - 1) * $perPage];
}

function render_pagination($page, $totalPages, $baseUrl, $pageParam = 'page')
{
    if ($totalPages <= 1) return;
    $sep = (strpos($baseUrl, '?') === false) ? '?' : '&';
    echo '<div class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $url = $baseUrl . $sep . $pageParam . '=' . $i;
        if ($i === $page) {
            echo '<span class="page-current">' . $i . '</span>';
        } else {
            echo '<a href="' . e($url) . '">' . $i . '</a>';
        }
    }
    echo '</div>';
}

// 公共页头 + 导航（按角色显示不同入口）
function page_head($title)
{
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' - 兴趣社区</title>';
    echo '<link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<header class="topbar"><div class="container nav-wrap">';
    echo '<a class="logo" href="index.php">兴趣社区</a>';
    echo '<nav class="nav">';
    echo '<a href="index.php">社区首页</a>';
    if (is_login()) {
        echo '<a href="post_edit.php">发布帖子</a>';
        echo '<a href="my_posts.php">我的帖子</a>';
        echo '<a href="my_collects.php">我的收藏</a>';
        if (is_admin()) {
            echo '<a href="admin_posts.php">帖子管理</a>';
            echo '<a href="admin_comments.php">评论管理</a>';
            echo '<a href="admin_users.php">用户管理</a>';
            echo '<a href="admin_categories.php">分类管理</a>';
        }
        echo '<span class="nav-user">' . e($_SESSION['nickname'] ?: $_SESSION['username'])
            . '（' . (is_admin() ? '管理员' : '普通用户') . '）</span>';
        echo '<a href="logout.php">退出</a>';
    } else {
        echo '<a href="login.php">登录</a><a href="register.php">注册</a>';
    }
    echo '</nav></div></header><main class="container">';
    show_flash();
}

function page_foot()
{
    echo '</main><footer class="footer">基于 PHP + MySQL 的兴趣社区内容管理系统 · 结课作业</footer>';
    echo '<script src="assets/app.js"></script></body></html>';
}
