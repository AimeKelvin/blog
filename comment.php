<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to comment.']);
    exit;
}

if (isset($_POST['post_id']) && isset($_POST['comment'])) {
    $post_id = $_POST['post_id'];
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $comment]);

    $stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.id = LAST_INSERT_ID()");
    $stmt->execute();
    $new_comment = $stmt->fetch();

    $html = '<div class="comment border-b border-gray-200 pb-3">
                <p class="text-sm text-gray-600"><strong>' . htmlspecialchars($new_comment['username']) . '</strong> on ' . $new_comment['created_at'] . ':</p>
                <p class="text-gray-700">' . nl2br(htmlspecialchars($new_comment['comment'])) . '</p>
             </div>';

    echo json_encode(['success' => true, 'newCommentHtml' => $html]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>