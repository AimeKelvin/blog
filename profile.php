<?php
session_start();
require 'db.php';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
} else if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found");
}

$post_stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$post_stmt->execute([$user_id]);
$posts = $post_stmt->fetchAll() ?: []; // Fallback to empty array

$is_following = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id) {
    $follow_stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
    $follow_stmt->execute([$_SESSION['user_id'], $user_id]);
    $is_following = $follow_stmt->fetch() ? true : false;
}

// Fetch follower and following counts (optional for stats)
$followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
$followers_stmt->execute([$user_id]);
$followers = $followers_stmt->fetchColumn() ?: 0;

$following_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$following_stmt->execute([$user_id]);
$following = $following_stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['username']) ?>'s Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-6">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 text-center mb-4">Profile</h1>
            <nav class="text-center mb-6">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php" class="text-indigo-600 hover:text-indigo-800 px-4 py-2 rounded-md hover:bg-indigo-100 transition-colors">Feed</a>
                    <a href="profile.php" class="text-indigo-600 hover:text-indigo-800 px-4 py-2 rounded-md hover:bg-indigo-100 transition-colors">Profile</a>
                    <a href="create_post.php" class="text-indigo-600 hover:text-indigo-800 px-4 py-2 rounded-md hover:bg-indigo-100 transition-colors">Create Post</a>
                    <a href="logout.php" class="text-indigo-600 hover:text-indigo-800 px-4 py-2 rounded-md hover:bg-indigo-100 transition-colors">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-indigo-600 hover:text-indigo-800 px-4 py-2 rounded-md hover:bg-indigo-100 transition-colors">Login</a>
                    <a href="register.php" class="text-indigo-600 hover:text-indigo-800 px-4 py-2 rounded-md hover:bg-indigo-100 transition-colors">Register</a>
                <?php endif; ?>
            </nav>
        </header>

        <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
            <!-- Profile Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <img src="<?= htmlspecialchars($user['profile_image'] ?? 'default_profile.png') ?>" alt="Profile Image" class="w-24 h-24 rounded-full object-cover border-4 border-indigo-500">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($user['username']) ?></h2>
                        <p class="text-gray-600"><?= htmlspecialchars($user['bio'] ?? 'No bio yet') ?></p>
                    </div>
                </div>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id): ?>
                    <a href="follow.php?user_id=<?= htmlspecialchars($user_id) ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                        <?= $is_following ? 'Unfollow' : 'Follow' ?>
                    </a>
                <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id): ?>
                    <a href="edit_profile.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Edit Profile</a>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="flex justify-around text-gray-600 mb-6">
                <p>Posts: <?= count($posts) ?></p>
                <p>Followers: <?= $followers ?></p>
                <p>Following: <?= $following ?></p>
            </div>

            <!-- Posts Feed -->
            <div class="space-y-6">
                <?php if (empty($posts)): ?>
                    <p class="text-gray-600 text-center">No posts yet—start sharing!</p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post bg-gray-50 shadow-md rounded-lg p-4 hover:shadow-lg transition-all">
                            <h3 class="text-xl font-bold text-gray-900">
                                <a href="view_post.php?id=<?= htmlspecialchars($post['id'] ?? '') ?>" class="text-indigo-600 hover:text-indigo-800 transition-colors"><?= htmlspecialchars($post['title'] ?? 'Untitled') ?></a>
                            </h3>
                            <p class="text-gray-700 mb-2"><?= nl2br(htmlspecialchars(substr($post['content'] ?? '', 0, 150))) ?>... <a href="view_post.php?id=<?= htmlspecialchars($post['id'] ?? '') ?>" class="text-indigo-600 hover:underline transition-colors">Read more</a></p>
                            <?php if (!empty($post['image'])): ?>
                                <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="w-full h-48 object-cover rounded-md mb-2">
                            <?php endif; ?>
                            <?php if (!empty($post['video'])): ?>
                                <video controls src="<?= htmlspecialchars($post['video']) ?>" class="w-full h-48 object-cover rounded-md mb-2"></video>
                            <?php endif; ?>
                            <div class="interactions flex space-x-4 text-gray-600 text-sm">
                                <?php
                                // Safely count likes with error handling
                                $likes_count = 0;
                                try {
                                    $likes_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
                                    $likes_stmt->execute([$post['id']]);
                                    $likes_count = $likes_stmt->fetchColumn() ?: 0;
                                } catch (PDOException $e) {
                                    error_log("Error counting likes for post ID " . ($post['id'] ?? 'unknown') . ": " . $e->getMessage());
                                }
                                ?>
                                <span>Likes: <?= htmlspecialchars($likes_count) ?></span>
                                <a href="like.php?post_id=<?= htmlspecialchars($post['id'] ?? '') ?>" class="text-indigo-600 hover:text-indigo-800 transition-colors like-btn" data-post-id="<?= htmlspecialchars($post['id'] ?? '') ?>">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    Like
                                </a>
                                <button class="comment-btn text-indigo-600 hover:text-indigo-800 transition-colors">Comment</button>
                            </div>
                            <div class="comment-section hidden mt-4">
                                <div class="comments space-y-2 mb-4">
                                    <!-- Comments loaded dynamically -->
                                </div>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form class="comment-form flex flex-col">
                                        <textarea class="p-3 border border-gray-300 rounded-md mb-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors" placeholder="Add a comment" rows="2" required></textarea>
                                        <button type="submit" class="bg-indigo-600 text-white p-2 rounded-md hover:bg-indigo-700 transition-colors">Post Comment</button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-gray-500 italic">Please <a href="login.php" class="text-indigo-600 hover:underline transition-colors">login</a> to interact.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript for AJAX interactions (same as before, no changes needed) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Like Buttons
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    if (!postId) return; // Prevent errors if postId is missing
                    const likeCountSpan = this.querySelector('.like-count');
                    fetch(`like.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `post_id=${encodeURIComponent(postId)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            likeCountSpan.textContent = data.newCount;
                            this.classList.toggle('text-indigo-800', data.liked);
                            this.classList.toggle('text-indigo-600', !data.liked);
                        } else {
                            alert(data.message || 'An error occurred. Please try again.');
                        }
                    })
                    .catch(() => alert('An error occurred. Please try again.'));
                });
            });

            // Handle Comment Buttons
            document.querySelectorAll('.comment-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const commentSection = this.parentElement.nextElementSibling;
                    commentSection.classList.toggle('hidden');
                    if (!commentSection.classList.contains('hidden') && !commentSection.querySelector('.comments').innerHTML) {
                        const postId = this.previousElementSibling.dataset.postId;
                        if (!postId) return; // Prevent errors if postId is missing
                        fetch(`get_comments.php?post_id=${encodeURIComponent(postId)}`)
                            .then(response => response.text())
                            .then(html => {
                                commentSection.querySelector('.comments').innerHTML = html;
                            })
                            .catch(() => alert('Failed to load comments.'));
                    }
                });
            });

            // Handle Comment Forms
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const postId = this.closest('.post').querySelector('.like-btn').dataset.postId;
                    if (!postId) return; // Prevent errors if postId is missing
                    const commentText = this.querySelector('textarea').value;
                    if (!commentText.trim()) {
                        alert('Please enter a comment.');
                        return;
                    }
                    fetch('comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `post_id=${encodeURIComponent(postId)}&comment=${encodeURIComponent(commentText)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const commentsDiv = this.previousElementSibling;
                            commentsDiv.innerHTML += data.newCommentHtml;
                            this.querySelector('textarea').value = '';
                        } else {
                            alert(data.message || 'An error occurred. Please try again.');
                        }
                    })
                    .catch(() => alert('An error occurred. Please try again.'));
                });
            });
        });
    </script>
</body>
</html>