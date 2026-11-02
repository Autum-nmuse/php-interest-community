<?php
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
$id = (int)($_POST['id'] ?? 0);
$postId = (int)($_POST['post_id'] ?? 0);
$stmt = $pdo->prepare('SELECT uid FROM `comment` WHERE id = ?');
$stmt->execute([$id]);
$uid = $stmt->fetchColumn();
if ($uid === false) { set_flash('评论不存在。'); redirect('index.php'); }
// 权限：评论作者本人可删自己的评论，管理员可删任意违规评论
if ((int)$uid !== (int)$_SESSION['user_id'] && !is_admin()) { set_flash('只能删除自己的评论。'); redirect('index.php'); }
$pdo->prepare('DELETE FROM `comment` WHERE id = ?')->execute([$id]);
set_flash('评论已删除。');
redirect(isset($_POST['from_admin']) ? 'admin_comments.php' : ($postId > 0 ? 'post.php?id=' . $postId : 'index.php'));
