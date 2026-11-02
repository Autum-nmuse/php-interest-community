<?php
require 'config.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM `post` WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) { set_flash('帖子不存在。'); redirect('my_posts.php'); }
    // 权限控制：普通用户只能改自己的帖子；管理员如需删帖请走帖子管理
    if ((int)$post['uid'] !== (int)$_SESSION['user_id']) { set_flash('只能修改自己发布的帖子。'); redirect('index.php'); }
}
$categories = $pdo->query('SELECT * FROM `category` ORDER BY id')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $cateId = (int)($_POST['cate_id'] ?? 0);
    $status = isset($_POST['status']) && (int)$_POST['status'] === 0 ? 0 : 1;
    if ($title === '' || $content === '') {
        $error = '标题和正文不能为空。';
    } elseif ($cateId <= 0) {
        $error = '请选择兴趣分类。';
    } else {
        if ($id > 0) {
            $pdo->prepare('UPDATE `post` SET title = ?, content = ?, cate_id = ?, status = ? WHERE id = ? AND uid = ?')
                ->execute([$title, $content, $cateId, $status, $id, $_SESSION['user_id']]);
            set_flash('帖子修改成功。');
            redirect('post.php?id=' . $id);
        } else {
            $pdo->prepare('INSERT INTO `post` (uid, title, content, cate_id, status) VALUES (?, ?, ?, ?, ?)')
                ->execute([$_SESSION['user_id'], $title, $content, $cateId, $status]);
            $newId = (int)$pdo->lastInsertId();
            set_flash($status === 1 ? '帖子发布成功。' : '草稿保存成功，草稿仅自己可见。');
            redirect($status === 1 ? 'post.php?id=' . $newId : 'my_posts.php');
        }
    }
    $post = ['title' => $title, 'content' => $content, 'cate_id' => $cateId, 'status' => $status];
}
page_head($id > 0 ? '编辑帖子' : '发布帖子');
?>
<div class="card form-card form-card-wide">
  <h1><?= $id > 0 ? '编辑帖子' : '发布新帖子' ?></h1>
  <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>标题</label>
    <input type="text" name="title" required value="<?= e($post['title'] ?? '') ?>">
    <label>兴趣分类</label>
    <select name="cate_id" required>
      <option value="0">请选择分类</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= isset($post['cate_id']) && (int)$post['cate_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['cate_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>正文</label>
    <textarea name="content" rows="8" required><?= e($post['content'] ?? '') ?></textarea>
    <label>状态</label>
    <select name="status">
      <option value="1" <?= !isset($post['status']) || (int)$post['status'] === 1 ? 'selected' : '' ?>>发布</option>
      <option value="0" <?= isset($post['status']) && (int)$post['status'] === 0 ? 'selected' : '' ?>>保存为草稿（仅自己可见）</option>
    </select>
    <button class="btn btn-primary btn-block" type="submit"><?= $id > 0 ? '保存修改' : '提交' ?></button>
  </form>
</div>
<?php page_foot(); ?>
