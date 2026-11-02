<?php
require 'config.php';
require_admin();
$total = (int)$pdo->query('SELECT COUNT(*) FROM `comment`')->fetchColumn();
$perPage = 10; list($page, $totalPages, $offset) = paginate($total, $perPage);
$comments = $pdo->query(
    'SELECT cm.*, u.username, p.title AS post_title FROM `comment` cm JOIN `user` u ON u.id=cm.uid JOIN `post` p ON p.id=cm.post_id
     ORDER BY cm.create_time DESC, cm.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
)->fetchAll();
page_head('评论管理');
?>
<h1>评论管理（管理员）</h1>
<div class="card">
  <?php if (!$comments): ?><p class="muted">暂无评论。</p><?php endif; ?>
  <table>
    <tr><th>ID</th><th>评论内容</th><th>评论人</th><th>所属帖子</th><th>时间</th><th>操作</th></tr>
    <?php foreach ($comments as $cm): ?>
    <tr>
      <td><?= (int)$cm['id'] ?></td><td><?= e($cm['content']) ?></td><td><?= e($cm['username']) ?></td>
      <td><a href="post.php?id=<?= (int)$cm['post_id'] ?>"><?= e($cm['post_title']) ?></a></td>
      <td><?= e($cm['create_time']) ?></td>
      <td><form class="del-form" method="post" action="comment_delete.php" data-confirm="确定要删除这条违规评论吗？">
        <input type="hidden" name="id" value="<?= (int)$cm['id'] ?>"><input type="hidden" name="from_admin" value="1">
        <button class="link-btn" type="submit">删除</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php render_pagination($page, $totalPages, 'admin_comments.php'); ?>
</div>
<?php page_foot(); ?>
