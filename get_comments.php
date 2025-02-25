<?php
require 'db.php';

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    $stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at ASC");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();

    foreach ($comments as $comment) {
        echo '<div class="comment border-b border-gray-200 pb-3">
                <p class="text-sm text-gray-600"><strong>' . htmlspecialchars($comment['username']) . '</strong> on ' . $comment['created_at'] . ':</p>
                <p class="text-gray-700">' . nl2br(htmlspecialchars($comment['comment'])) . '</p>
              </div>';
    }
}
?>