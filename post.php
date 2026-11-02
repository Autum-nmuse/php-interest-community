<?php
require 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare(
    'SELECT p.*, c.cate_name, u.nickname, u.username FROM `post` p
     JOIN `category` c ON c.id = p.cate_id JOIN `user` u ON u.id = p.uid WHERE p.id = ?'
);
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); die('帖子不存在。'); }
// 草稿仅作者本人和管理员可见，其他用户无法查看
if ((int)$post['status'] !== 1 && !(is_login() && ((int)$_SESSION['user_id'] === (int)$post['uid'] || is_admin()))) {
    http_response_code(404); die('帖子不存在或尚未发布。');
}

// 点赞 / 收藏（登录后操作；唯一约束 + 先查后写，防止重复点赞/收藏；再次点击为取消）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_login();
    $action = $_POST['action'];
    if ($action === 'like' || $action === 'collect') {
        $table = $action === 'like' ? 'like_table' : 'collect';
        $stmt = $pdo->prepare("SELECT id FROM `$table` WHERE post_id = ? AND uid = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM `$table` WHERE post_id = ? AND uid = ?")->execute([$id, $_SESSION['user_id']]);
            set_flash($action === 'like' ? '已取消点赞。' : '已取消收藏。');
        } else {
            $pdo->prepare("INSERT INTO `$table` (post_id, uid) VALUES (?, ?)")->execute([$id, $_SESSION['user_id']]);
            set_flash($action === 'like' ? '点赞成功。' : '收藏成功，可在「我的收藏」查看。');
        }
        redirect('post.php?id=' . $id);
    }
    if ($action === 'comment') {
        if (!check_form_token()) { set_flash('请勿重复提交评论。'); redirect('post.php?id=' . $id); }
        $content = trim($_POST['content'] ?? '');
        if ($content === '') { set_flash('评论内容不能为空。'); redirect('post.php?id=' . $id); }
        $pdo->prepare('INSERT INTO `comment` (post_id, uid, content) VALUES (?, ?, ?)')
            ->execute([$id, $_SESSION['user_id'], $content]);
        set_flash('评论发表成功。');
        redirect('post.php?id=' . $id);
    }
}

$likeCount = (int)$pdo->query('SELECT COUNT(*) FROM `like_table` WHERE post_id = ' . $id)->fetchColumn();
$collectCount = (int)$pdo->query('SELECT COUNT(*) FROM `collect` WHERE post_id = ' . $id)->fetchColumn();
$liked = $collected = false;
if (is_login()) {
    $s = $pdo->prepare('SELECT COUNT(*) FROM `like_table` WHERE post_id = ? AND uid = ?'); $s->execute([$id, $_SESSION['user_id']]); $liked = (int)$s->fetchColumn() > 0;
    $s = $pdo->prepare('SELECT COUNT(*) FROM `collect` WHERE post_id = ? AND uid = ?'); $s->execute([$id, $_SESSION['user_id']]); $collected = (int)$s->fetchColumn() > 0;
}

// 评论分页
$totalComments = (int)$pdo->query('SELECT COUNT(*) FROM `comment` WHERE post_id = ' . $id)->fetchColumn();
$perPage = 5;
list($cPage, $cTotalPages, $cOffset) = paginate($totalComments, $perPage, 'cpage');
$stmt = $pdo->prepare(
    'SELECT cm.*, u.nickname, u.username FROM `comment` cm JOIN `user` u ON u.id = cm.uid
     WHERE cm.post_id = ? ORDER BY cm.create_time ASC, cm.id ASC LIMIT ' . $perPage . ' OFFSET ' . $cOffset
);
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

page_head($post['title']);
?>
<div class="card">
  <span class="badge"><?= e($post['cate_name']) ?></span>
  <?php if ((int)$post['status'] === 0): ?><span class="badge badge-draft">草稿</span><?php endif; ?>
  <h1><?= e($post['title']) ?></h1>
  <p class="post-meta">作者：<?= e($post['nickname'] ?: $post['username']) ?> · 发布于 <?= e($post['create_time']) ?><?php if ($post['update_time'] !== $post['create_time']): ?> · 更新于 <?= e($post['update_time']) ?><?php endif; ?></p>
  <p class="post-content"><?= e($post['content']) ?></p>
  <div class="actions">
    <?php if (is_login()): ?>
      <form method="post"><input type="hidden" name="action" value="like"><button class="btn btn-small" type="submit"><?= $liked ? '❤️ 已点赞' : '🤍 点赞' ?>（<?= $likeCount ?>）</button></form>
      <form method="post"><input type="hidden" name="action" value="collect"><button class="btn btn-small" type="submit"><?= $collected ? '⭐ 已收藏' : '☆ 收藏' ?>（<?= $collectCount ?>）</button></form>
      <?php if ((int)$_SESSION['user_id'] === (int)$post['uid']): ?>
        <a class="btn btn-small" href="post_edit.php?id=<?= $id ?>">编辑</a>
        <form method="post" action="post_delete.php" data-confirm="确定要删除这篇帖子吗？其评论、点赞、收藏将一并删除。">
          <input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-small" type="submit">删除</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <span class="muted">点赞 <?= $likeCount ?> · 收藏 <?= $collectCount ?> · <a href="login.php">登录</a>后可评论、点赞、收藏</span>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>评论（<?= $totalComments ?>）</h2>
  <?php if (!$comments): ?><p class="muted">还没有评论，来发表第一条吧。</p><?php endif; ?>
  <?php foreach ($comments as $cm): ?>
    <div class="comment">
      <p><strong><?= e($cm['nickname'] ?: $cm['username']) ?></strong> <span class="muted"><?= e($cm['create_time']) ?></span></p>
      <p class="post-content"><?= e($cm['content']) ?></p>
      <?php if (is_login() && ((int)$_SESSION['user_id'] === (int)$cm['uid'] || is_admin())): ?>
        <form method="post" action="comment_delete.php" data-confirm="确定要删除这条评论吗？">
          <input type="hidden" name="id" value="<?= (int)$cm['id'] ?>"><input type="hidden" name="post_id" value="<?= $id ?>">
          <button class="link-btn" type="submit">删除评论</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php render_pagination($cPage, $cTotalPages, 'post.php?id=' . $id, 'cpage'); ?>

  <?php if (is_login()): ?>
    <form method="post">
      <input type="hidden" name="action" value="comment">
      <input type="hidden" name="form_token" value="<?= e(form_token()) ?>">
      <label>发表评论</label>
      <textarea name="content" rows="3" required placeholder="写下你的想法，内容不能为空"></textarea>
      <button class="btn btn-primary" type="submit" style="margin-top:10px">提交评论</button>
    </form>
  <?php else: ?>
    <p class="muted"><a href="login.php">登录</a>后可发表评论。</p>
  <?php endif; ?>
</div>
<?php page_foot(); ?>
