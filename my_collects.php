<?php
require 'config.php';
require_login();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM `collect` cl JOIN `post` p ON p.id=cl.post_id WHERE cl.uid = ? AND p.status = 1');
$stmt->execute([$_SESSION['user_id']]); $total = (int)$stmt->fetchColumn();
$perPage = 5; list($page, $totalPages, $offset) = paginate($total, $perPage);
$stmt = $pdo->prepare(
    'SELECT p.*, c.cate_name, u.nickname, u.username, cl.create_time AS collect_time
     FROM `collect` cl JOIN `post` p ON p.id=cl.post_id JOIN `category` c ON c.id=p.cate_id JOIN `user` u ON u.id=p.uid
     WHERE cl.uid = ? AND p.status = 1 ORDER BY cl.create_time DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
);
$stmt->execute([$_SESSION['user_id']]); $posts = $stmt->fetchAll();
page_head('我的收藏');
?>
<h1>我的收藏</h1>
<?php if (!$posts): ?><div class="card">你还没有收藏任何帖子，去社区首页逛逛吧。</div><?php endif; ?>
<?php foreach ($posts as $p): ?>
  <div class="card">
    <span class="badge"><?= e($p['cate_name']) ?></span>
    <h2><a href="post.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h2>
    <p class="post-meta">作者：<?= e($p['nickname'] ?: $p['username']) ?> · 帖子发表于 <?= e($p['create_time']) ?> · 收藏于 <?= e($p['collect_time']) ?></p>
  </div>
<?php endforeach; ?>
<?php render_pagination($page, $totalPages, 'my_collects.php'); ?>
<?php page_foot(); ?>
