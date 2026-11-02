<?php
require 'config.php';
if (is_login()) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = '用户名和密码不能为空。';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM `user` WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password'])) {
            $error = '用户名或密码不正确。';
        } elseif ((int)$user['status'] !== 1) {
            $error = '该账号已被封禁，无法登录，请联系管理员。';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nickname'] = $user['nickname'];
            $_SESSION['role'] = $user['role'];
            set_flash('登录成功，欢迎回来，' . ($user['nickname'] ?: $user['username']) . '。');
            redirect('index.php');
        }
    }
}
page_head('登录');
?>
<div class="card form-card">
  <h1>登录</h1>
  <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>用户名</label><input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
    <label>密码</label><input type="password" name="password" required>
    <button class="btn btn-primary btn-block" type="submit">登录</button>
  </form>
  <p class="muted">还没有账号？<a href="register.php">去注册</a></p>
  <p class="muted">演示账号：管理员 admin / 123456；普通用户 xiaoming / 123456、xiaohong / 123456</p>
</div>
<?php page_foot(); ?>
