<?php
require 'config.php';

// 社区首页：浏览全部已发布帖子，支持分类筛选 + 关键词搜索 + 分页（未登录也可浏览）
$cateId = isset($_GET['cate_id']) ? (int)$_GET['cate_id'] : 0;
$keyword = trim($_GET['keyword'] ?? '');

$categories = $pdo->query('SELECT * FROM `category` ORDER BY id')->fetchAll();

$where = ['p.status = 1'];
$params = [];
if ($cateId > 0) { $where[] = 'p.cate_id = ?'; $params[] = $cateId; }
if ($keyword !== '') { $where[] = '(p.title LIKE ? OR p.content LIKE ?)'; $params[] = '%' . $keyword . '%'; $params[] = '%' . $keyword . '%'; }
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM `post` p WHERE $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$perPage = 5;
list($page, $totalPages, $offset) = paginate($total, $perPage);

$stmt = $pdo->prepare(
    "SELECT p.*, c.cate_name, u.nickname, u.username,
            (SELECT COUNT(*) FROM `comment` WHERE post_id = p.id) AS comment_count,
            (SELECT COUNT(*) FROM `like_table` WHERE post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM `collect` WHERE post_id = p.id) AS collect_count
     FROM `post` p
     JOIN `category` c ON c.id = p.cate_id
     JOIN `user` u ON u.id = p.uid
     WHERE $whereSql
     ORDER BY p.create_time DESC, p.id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$baseUrl = 'index.php?cate_id=' . $cateId . '&keyword=' . urlencode($keyword);
page_head('社区首页');
?>
<h1>社区帖子</h1>
<div class="card">
  <form class="filter-form" method="get">
    <select name="cate_id">
      <option value="0">全部分类</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cateId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['cate_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="keyword" placeholder="搜索标题或正文关键词" value="<?= e($keyword) ?>">
    <button class="btn btn-primary" type="submit">搜索</button>
    <?php if (is_login()): ?><a class="btn" href="post_edit.php">+ 发布帖子</a><?php endif; ?>
  </form>
</div>

<?php if (!$posts): ?>
  <div class="card">暂无帖子<?= $keyword !== '' || $cateId > 0 ? '，换个条件试试' : '' ?>。</div>
<?php endif; ?>

<?php foreach ($posts as $p): ?>
  <div class="card">
    <span class="badge"><?= e($p['cate_name']) ?></span>
    <h2><a href="post.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h2>
    <p class="post-content"><?= e(mb_strimwidth($p['content'], 0, 160, '…')) ?></p>
    <p class="post-meta">作者：<?= e($p['nickname'] ?: $p['username']) ?> · <?= e($p['create_time']) ?>
      · 评论 <?= (int)$p['comment_count'] ?> · 点赞 <?= (int)$p['like_count'] ?> · 收藏 <?= (int)$p['collect_count'] ?></p>
  </div>
<?php endforeach; ?>

<?php render_pagination($page, $totalPages, $baseUrl); ?>
<?php page_foot(); ?>
