<?php
require 'config.php';
require_login();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM `post` WHERE uid = ?'); $stmt->execute([$_SESSION['user_id']]); $total = (int)$stmt->fetchColumn();
$perPage = 5; list($page, $totalPages, $offset) = paginate($total, $perPage);
$stmt = $pdo->prepare(
    'SELECT p.*, c.cate_name, (SELECT COUNT(*) FROM `comment` WHERE post_id=p.id) AS cc,
            (SELECT COUNT(*) FROM `like_table` WHERE post_id=p.id) AS lc
     FROM `post` p JOIN `category` c ON c.id=p.cate_id WHERE p.uid = ?
     ORDER BY p.create_time DESC, p.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
);
$stmt->execute([$_SESSION['user_id']]); $posts = $stmt->fetchAll();
page_head('我的帖子');
?>
<h1>我的帖子</h1>
<p><a class="btn btn-primary" href="post_edit.php">+ 发布新帖子</a></p>
<?php if (!$posts): ?><div class="card">你还没有发布过帖子。</div><?php endif; ?>
<?php foreach ($posts as $p): ?>
  <div class="card">
    <span class="badge"><?= e($p['cate_name']) ?></span>
    <?php if ((int)$p['status'] === 0): ?><span class="badge badge-draft">草稿 · 仅自己可见</span><?php endif; ?>
    <h2><a href="post.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h2>
    <p class="post-meta"><?= e($p['create_time']) ?> · 评论 <?= (int)$p['cc'] ?> · 点赞 <?= (int)$p['lc'] ?></p>
    <div class="actions">
      <a class="btn btn-small" href="post_edit.php?id=<?= (int)$p['id'] ?>">编辑</a>
      <form method="post" action="post_delete.php" data-confirm="确定要删除这篇帖子吗？">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-small" type="submit">删除</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
<?php render_pagination($page, $totalPages, 'my_posts.php'); ?>
<?php page_foot(); ?>
