<?php
session_start();
require 'db.php';

$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    die("Post not found");
}

$comment_stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at ASC");
$comment_stmt->execute([$post_id]);
$comments = $comment_stmt->fetchAll();

$liked = false;
if (isset($_SESSION['user_id'])) {
    $like_stmt = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    $like_stmt->execute([$post_id, $_SESSION['user_id']]);
    $liked = $like_stmt->fetch() ? true : false;
}

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $comment]);
    header("Location: view_post.php?id=$post_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <p>By <?= htmlspecialchars($post['username']) ?> on <?= $post['created_at'] ?></p>
    <?php if ($post['image']): ?>
        <img src="<?= $post['image'] ?>" alt="Post image">
    <?php endif; ?>
    <?php if ($post['video']): ?>
        <video controls src="<?= $post['video'] ?>"></video>
    <?php endif; ?>
    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
    <?php if (isset($_SESSION['user_id'])): ?>
        <p><a href="like.php?post_id=<?= $post['id'] ?>"><?= $liked ? 'Unlike' : 'Like' ?></a></p>
    <?php endif; ?>
    <h2>Comments</h2>
    <?php foreach ($comments as $comment): ?>
        <div>
            <p><strong><?= htmlspecialchars($comment['username']) ?></strong> on <?= $comment['created_at'] ?>:</p>
            <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
        </div>
    <?php endforeach; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post">
            <textarea name="comment" placeholder="Add a comment" required></textarea>
            <button type="submit">Comment</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Login</a> to comment</p>
    <?php endif; ?>
</body>
</html>