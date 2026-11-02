<?php
require 'config.php';
require_admin();

// 新增 / 修改（同一页面处理）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = trim($_POST['cate_name'] ?? '');
    $intro = trim($_POST['intro'] ?? '');
    $editId = (int)($_POST['edit_id'] ?? 0);
    if ($name === '') {
        set_flash('分类名称不能为空。');
    } elseif ($editId > 0) {
        $pdo->prepare('UPDATE `category` SET cate_name = ?, intro = ? WHERE id = ?')->execute([$name, $intro, $editId]);
        set_flash('分类修改成功。');
    } else {
        $pdo->prepare('INSERT INTO `category` (cate_name, intro) VALUES (?, ?)')->execute([$name, $intro]);
        set_flash('分类新增成功。');
    }
    redirect('admin_categories.php');
}
// 删除：分类下有帖子时外键 RESTRICT 会阻止删除，给出提示
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `post` WHERE cate_id = ?'); $stmt->execute([$delId]);
    if ((int)$stmt->fetchColumn() > 0) {
        set_flash('该分类下还有帖子，不能直接删除，请先处理这些帖子。');
    } else {
        $pdo->prepare('DELETE FROM `category` WHERE id = ?')->execute([$delId]);
        set_flash('分类已删除。');
    }
    redirect('admin_categories.php');
}
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM `category` WHERE id = ?'); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch();
}
$categories = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM `post` WHERE cate_id=c.id) AS post_count FROM `category` c ORDER BY c.id')->fetchAll();
page_head('分类管理');
?>
<h1>兴趣分类管理（管理员）</h1>
<div class="card form-card-wide" style="margin:0 0 16px">
  <h2><?= $edit ? '修改分类' : '新增分类' ?></h2>
  <form method="post">
    <input type="hidden" name="edit_id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
    <label>分类名称</label><input type="text" name="cate_name" required value="<?= e($edit['cate_name'] ?? '') ?>" placeholder="例如：编程">
    <label>分类简介</label><input type="text" name="intro" value="<?= e($edit['intro'] ?? '') ?>">
    <button class="btn btn-primary" type="submit" name="save" style="margin-top:12px"><?= $edit ? '保存修改' : '新增分类' ?></button>
    <?php if ($edit): ?><a class="btn" href="admin_categories.php">取消编辑</a><?php endif; ?>
  </form>
</div>
<div class="card">
  <table>
    <tr><th>ID</th><th>分类名称</th><th>简介</th><th>帖子数</th><th>操作</th></tr>
    <?php foreach ($categories as $c): ?>
    <tr>
      <td><?= (int)$c['id'] ?></td><td><?= e($c['cate_name']) ?></td><td><?= e($c['intro']) ?></td><td><?= (int)$c['post_count'] ?></td>
      <td><a href="admin_categories.php?edit=<?= (int)$c['id'] ?>">修改</a>
        <form class="del-form" method="post" data-confirm="确定要删除这个分类吗？"><input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>"><button class="link-btn" type="submit">删除</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php page_foot(); ?>
