<?php
require 'config.php';
require_admin();

// 封禁 / 解封（不能封禁自己，也不能封禁其他管理员，避免系统锁死）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $targetId = (int)$_POST['toggle_id'];
    if ($targetId === (int)$_SESSION['user_id']) {
        set_flash('不能封禁自己。');
    } else {
        $stmt = $pdo->prepare('SELECT role, status FROM `user` WHERE id = ?'); $stmt->execute([$targetId]); $target = $stmt->fetch();
        if (!$target) { set_flash('用户不存在。'); }
        elseif ($target['role'] === 'admin') { set_flash('不能封禁管理员账号。'); }
        else {
            $newStatus = (int)$target['status'] === 1 ? 0 : 1;
            $pdo->prepare('UPDATE `user` SET status = ? WHERE id = ?')->execute([$newStatus, $targetId]);
            set_flash($newStatus === 0 ? '该账号已封禁，封禁账号无法登录和发布内容。' : '该账号已解封。');
        }
    }
    redirect('admin_users.php');
}
$total = (int)$pdo->query('SELECT COUNT(*) FROM `user`')->fetchColumn();
$perPage = 10; list($page, $totalPages, $offset) = paginate($total, $perPage);
$users = $pdo->query('SELECT * FROM `user` ORDER BY id ASC LIMIT ' . $perPage . ' OFFSET ' . $offset)->fetchAll();
page_head('用户管理');
?>
<h1>用户管理（管理员）</h1>
<div class="card">
  <table>
    <tr><th>ID</th><th>用户名</th><th>昵称</th><th>角色</th><th>状态</th><th>注册时间</th><th>操作</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td><td><?= e($u['username']) ?></td><td><?= e($u['nickname']) ?></td>
      <td><?= $u['role'] === 'admin' ? '管理员' : '普通用户' ?></td>
      <td><?= (int)$u['status'] === 1 ? '正常' : '<span class="badge badge-banned">已封禁</span>' ?></td>
      <td><?= e($u['create_time']) ?></td>
      <td>
        <?php if ($u['role'] !== 'admin'): ?>
        <form class="del-form" method="post" data-confirm="<?= (int)$u['status'] === 1 ? '确定要封禁该账号吗？封禁后无法登录。' : '确定要解封该账号吗？' ?>">
          <input type="hidden" name="toggle_id" value="<?= (int)$u['id'] ?>">
          <button class="link-btn" type="submit"><?= (int)$u['status'] === 1 ? '封禁' : '解封' ?></button>
        </form>
        <?php else: ?><span class="muted">—</span><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php render_pagination($page, $totalPages, 'admin_users.php'); ?>
</div>
<?php page_foot(); ?>
