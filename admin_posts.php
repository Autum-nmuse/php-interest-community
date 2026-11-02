<?php
require 'config.php';
require_admin();
$total = (int)$pdo->query('SELECT COUNT(*) FROM `post`')->fetchColumn();
$perPage = 10; list($page, $totalPages, $offset) = paginate($total, $perPage);
$posts = $pdo->query(
    'SELECT p.*, c.cate_name, u.username FROM `post` p JOIN `category` c ON c.id=p.cate_id JOIN `user` u ON u.id=p.uid
     ORDER BY p.create_time DESC, p.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
)->fetchAll();
page_head('帖子管理');
?>
<h1>帖子管理（管理员）</h1>
<div class="card">
  <?php if (!$posts): ?><p class="muted">暂无帖子。</p><?php endif; ?>
  <table>
    <tr><th>ID</th><th>标题</th><th>作者</th><th>分类</th><th>状态</th><th>发布时间</th><th>操作</th></tr>
    <?php foreach ($posts as $p): ?>
    <tr>
      <td><?= (int)$p['id'] ?></td>
      <td><a href="post.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></td>
      <td><?= e($p['username']) ?></td><td><?= e($p['cate_name']) ?></td>
      <td><?= (int)$p['status'] === 1 ? '已发布' : '<span class="badge badge-draft">草稿</span>' ?></td>
      <td><?= e($p['create_time']) ?></td>
      <td><form class="del-form" method="post" action="post_delete.php" data-confirm="确定要强制删除这篇帖子吗？其评论、点赞、收藏将一并删除。">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="from_admin" value="1">
        <button class="link-btn" type="submit">删除</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php render_pagination($page, $totalPages, 'admin_posts.php'); ?>
</div>
<?php page_foot(); ?>
