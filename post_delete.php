<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT uid FROM `post` WHERE id = ?');
$stmt->execute([$id]);
$uid = $stmt->fetchColumn();
if ($uid === false) { set_flash('帖子不存在。'); redirect('index.php'); }
// 权限：作者本人可删自己帖子，管理员可删任意违规帖子
if ((int)$uid !== (int)$_SESSION['user_id'] && !is_admin()) { set_flash('只能删除自己发布的帖子。'); redirect('index.php'); }
$pdo->prepare('DELETE FROM `post` WHERE id = ?')->execute([$id]); // 评论/点赞/收藏靠外键级联删除
set_flash('帖子已删除。');
redirect(is_admin() && isset($_POST['from_admin']) ? 'admin_posts.php' : 'my_posts.php');
