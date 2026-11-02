<?php
require 'config.php';
if (is_login()) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = '用户名和密码不能为空。';
    } elseif (mb_strlen($username) < 3 || mb_strlen($password) < 6) {
        $error = '用户名至少 3 个字符，密码至少 6 位。';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM `user` WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = '该用户名已被注册，请换一个。';
        } else {
            $stmt = $pdo->prepare("INSERT INTO `user` (username, password, nickname, role, status) VALUES (?, ?, ?, 'user', 1)");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nickname !== '' ? $nickname : $username]);
            set_flash('注册成功，请登录。');
            redirect('login.php');
        }
    }
}
page_head('注册');
?>
<div class="card form-card">
  <h1>注册账号</h1>
  <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>用户名</label><input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
    <label>昵称（可空，默认用用户名）</label><input type="text" name="nickname" value="<?= e($_POST['nickname'] ?? '') ?>">
    <label>密码（至少 6 位）</label><input type="password" name="password" required>
    <button class="btn btn-primary btn-block" type="submit">注册</button>
  </form>
  <p class="muted">已有账号？<a href="login.php">去登录</a></p>
</div>
<?php page_foot(); ?>
