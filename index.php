<?php
session_start();
require 'db.php';

// Fetch all posts with usernames and profile images, ordered by creation date (newest first)
try {
    $stmt = $pdo->query("SELECT posts.*, users.username, users.profile_image FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $posts = [];
    error_log("Database error in feed: " . $e->getMessage());
}

// Count likes for each post
$like_counts = [];
foreach ($posts as $post) {
    $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $like_stmt->execute([$post['id']]);
    $like_counts[$post['id']] = $like_stmt->fetchColumn() ?: 0;
}

// Check if the user has liked each post and fetch suggested users to follow
$liked_status = [];
$suggestions = [];
if (isset($_SESSION['user_id'])) {
    foreach ($posts as $post) {
        $like_check = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
        $like_check->execute([$post['id'], $_SESSION['user_id']]);
        $liked_status[$post['id']] = $like_check->fetch() ? true : false;
    }
    
    // Fetch users the logged-in user does NOT follow (for "Who to Follow")
    $suggest_stmt = $pdo->prepare("SELECT users.* FROM users WHERE users.id != ? AND users.id NOT IN (SELECT followed_id FROM follows WHERE follower_id = ?) LIMIT 5");
    $suggest_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $suggestions = $suggest_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zamuka Blog - Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-6xl mx-auto p-4 flex">
        <!-- Main Feed (Centered) -->
        <main class="w-full md:w-2/3 lg:w-1/2 mx-auto">
            <header class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Zamuka Blog Feed</h1>
                <nav class="flex justify-center space-x-4 mb-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Profile</a>
                        <a href="create_post.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Create Post</a>
                        <a href="logout.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Login</a>
                        <a href="register.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Register</a>
                    <?php endif; ?>
                </nav>
            </header>
            <div class="feed space-y-4">
                <?php if (empty($posts)): ?>
                    <p class="text-gray-600 text-center">No posts yet—be the first to share!</p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-all">
                            <div class="flex items-start mb-2">
                                <img src="<?= htmlspecialchars($post['profile_image'] ?? 'default_profile.png') ?>" alt="<?= htmlspecialchars($post['username']) ?>'s profile" class="w-10 h-10 rounded-full object-cover mr-3">
                                <div class="flex-1">
                                    <p class="text-gray-900 font-semibold"><?= htmlspecialchars($post['username']) ?></p>
                                    <p class="text-gray-500 text-xs"><?= htmlspecialchars($post['created_at']) ?></p>
                                </div>
                            </div>
                            <p class="text-gray-800 mb-3"><?= nl2br(htmlspecialchars($post['content'] ?? '')) ?></p>
                            <?php if (!empty($post['image'])): ?>
                                <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="w-full rounded-lg mb-3 object-cover">
                            <?php elseif (!empty($post['video'])): ?>
                                <video controls class="w-full rounded-lg mb-3">
                                    <source src="<?= htmlspecialchars($post['video']) ?>" type="video/mp4">
                                </video>
                            <?php endif; ?>
                            <div class="flex space-x-6 text-gray-600 text-sm">
                                <button class="like-btn flex items-center space-x-1 hover:text-indigo-600 transition-colors" data-post-id="<?= htmlspecialchars($post['id'] ?? '') ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    <span class="like-count"><?= htmlspecialchars($like_counts[$post['id']] ?? 0) ?></span>
                                </button>
                                <button class="comment-btn flex items-center space-x-1 hover:text-indigo-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                    <span>Comment</span>
                                </button>
                            </div>
                            <div class="comment-section hidden mt-3">
                                <div class="comments space-y-2 text-sm"></div>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form class="comment-form mt-2 flex space-x-2">
                                        <textarea class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm" placeholder="Write a comment..." rows="1" required></textarea>
                                        <button type="submit" class="bg-indigo-600 text-white px-3 py-1 rounded-md hover:bg-indigo-700 transition-colors text-sm">Post</button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm">Please <a href="login.php" class="text-indigo-600 hover:underline">login</a> to comment.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Right Sidebar (Who to Follow) -->
        <?php if (isset($_SESSION['user_id']) && !empty($suggestions)): ?>
            <aside class="hidden md:block w-1/4 ml-6">
                <div class="bg-white border border-gray-200 rounded-lg p-4 sticky top-4">
                    <h2 class="text-lg font-bold text-gray-900 mb-3">Who to Follow</h2>
                    <div class="space-y-3">
                        <?php foreach ($suggestions as $user): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <img src="<?= htmlspecialchars($user['profile_image'] ?? 'default_profile.png') ?>" alt="<?= htmlspecialchars($user['username']) ?>'s profile" class="w-8 h-8 rounded-full object-cover">
                                    <a href="profile.php?id=<?= htmlspecialchars($user['id']) ?>" class="text-gray-900 hover:text-indigo-600 text-sm font-medium"><?= htmlspecialchars($user['username']) ?></a>
                                </div>
                                <a href="follow.php?id=<?= htmlspecialchars($user['id']) ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">Follow</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        <?php endif; ?>
    </div>

    <!-- JavaScript (unchanged) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    if (!postId) return;
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
                            this.classList.toggle('text-indigo-600', !data.liked);
                            this.classList.toggle('text-indigo-800', data.liked);
                        } else {
                            alert(data.message || 'An error occurred. Please try again.');
                        }
                    })
                    .catch(() => alert('An error occurred. Please try again.'));
                });
            });

            document.querySelectorAll('.comment-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const commentSection = this.parentElement.nextElementSibling;
                    commentSection.classList.toggle('hidden');
                    if (!commentSection.classList.contains('hidden') && !commentSection.querySelector('.comments').innerHTML) {
                        const postId = this.parentElement.querySelector('.like-btn').dataset.postId;
                        if (!postId) return;
                        fetch(`get_comments.php?post_id=${encodeURIComponent(postId)}`)
                            .then(response => response.text())
                            .then(html => commentSection.querySelector('.comments').innerHTML = html)
                            .catch(() => alert('Failed to load comments.'));
                    }
                });
            });

            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const postId = this.closest('.post').querySelector('.like-btn').dataset.postId;
                    if (!postId) return;
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
                            this.previousElementSibling.innerHTML += data.newCommentHtml;
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