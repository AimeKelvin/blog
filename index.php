<?php
session_start();
require 'db.php';

// Fetch all posts with usernames and profile images, ordered by creation date (newest first)
try {
    $stmt = $pdo->query("SELECT posts.*, users.username, users.profile_image FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; // Fallback to empty array if query fails
} catch (PDOException $e) {
    $posts = []; // Ensure $posts is always an array
    error_log("Database error in feed: " . $e->getMessage()); // Log error silently
}

// Count likes for each post
$like_counts = [];
foreach ($posts as $post) {
    $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $like_stmt->execute([$post['id']]);
    $like_counts[$post['id']] = $like_stmt->fetchColumn() ?: 0; // Default to 0 if no likes
}

// Check if the user has liked each post and fetch followed users
$liked_status = [];
$following = [];
if (isset($_SESSION['user_id'])) {
    foreach ($posts as $post) {
        $like_check = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
        $like_check->execute([$post['id'], $_SESSION['user_id']]);
        $liked_status[$post['id']] = $like_check->fetch() ? true : false;
    }
    
    // Fetch users the logged-in user follows
    $follow_stmt = $pdo->prepare("SELECT users.* FROM users JOIN follows ON users.id = follows.followed_id WHERE follows.follower_id = ?");
    $follow_stmt->execute([$_SESSION['user_id']]);
    $following = $follow_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
    <div class="container mx-auto p-4">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 text-center mb-4">Zamuka Blog Feed</h1>
            <nav class="text-center mb-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Profile</a>
                    <a href="create_post.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Create Post</a>
                    <a href="logout.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Login</a>
                    <a href="register.php" class="text-indigo-600 hover:text-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-100 transition-colors">Register</a>
                <?php endif; ?>
            </nav>
            <?php if (isset($_SESSION['user_id']) && !empty($following)): ?>
                <div class="bg-white shadow-md rounded-lg p-4 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Following</h2>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($following as $followed_user): ?>
                            <div class="flex items-center space-x-2">
                                <img src="<?= htmlspecialchars($followed_user['profile_image'] ?? 'default_profile.png') ?>" alt="<?= htmlspecialchars($followed_user['username']) ?>'s profile" class="w-8 h-8 rounded-full object-cover border border-indigo-500">
                                <a href="profile.php?id=<?= htmlspecialchars($followed_user['id']) ?>" class="text-indigo-600 hover:text-indigo-800 text-sm transition-colors line-clamp-1"><?= htmlspecialchars($followed_user['username']) ?></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </header>
        <main class="feed grid grid-cols-1 gap-4">
            <?php if (empty($posts)): ?>
                <p class="text-gray-600 text-center">No posts yet—be the first to share!</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post bg-white shadow-md rounded-lg p-3 hover:shadow-lg transition-all">
                        <div class="flex items-start mb-2">
                            <img src="<?= htmlspecialchars($post['profile_image'] ?? 'default_profile.png') ?>" alt="<?= htmlspecialchars($post['username']) ?>'s profile" class="w-8 h-8 rounded-full object-cover border border-indigo-500 mr-2">
                            <div class="flex-1">
                                <p class="text-gray-600 text-sm font-semibold line-clamp-1"><?= htmlspecialchars($post['username']) ?></p>
                                <p class="text-gray-500 text-xs"><?= htmlspecialchars($post['created_at']) ?></p>
                            </div>
                        </div>
                        <div class="aspect-square w-full overflow-hidden rounded-md mb-2">
                            <?php if (!empty($post['image'])): ?>
                                <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="w-full h-32 object-cover">
                            <?php elseif (!empty($post['video'])): ?>
                                <video controls class="w-full h-32 object-cover rounded-md">
                                    <source src="<?= htmlspecialchars($post['video']) ?>" type="video/mp4">
                                </video>
                            <?php else: ?>
                                <div class="w-full h-32 bg-gray-200 flex items-center justify-center text-gray-500 text-xs">No media</div>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-700 mb-2 line-clamp-2 text-sm"><?= nl2br(htmlspecialchars($post['content'] ?? '')) ?></p>
                        <div class="interactions flex space-x-3 text-gray-600 text-xs">
                            <button class="like-btn flex items-center space-x-1 text-indigo-600 hover:text-indigo-800 transition-colors" data-post-id="<?= htmlspecialchars($post['id'] ?? '') ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                <span>Like (<span class="like-count"><?= htmlspecialchars($like_counts[$post['id']] ?? 0) ?></span>)</span>
                            </button>
                            <button class="comment-btn flex items-center space-x-1 hover:text-indigo-800 transition-colors">Comment</button>
                        </div>
                        <div class="comment-section hidden mt-2">
                            <div class="comments space-y-1 mb-2">
                                <!-- Comments loaded dynamically -->
                            </div>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form class="comment-form flex flex-col">
                                    <textarea class="p-2 border border-gray-300 rounded-md mb-1 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors" placeholder="Add a comment" rows="2" required></textarea>
                                    <button type="submit" class="bg-indigo-600 text-white p-1.5 rounded-md hover:bg-indigo-700 transition-colors text-xs">Post Comment</button>
                                </form>
                            <?php else: ?>
                                <p class="text-gray-500 italic text-xs">Please <a href="login.php" class="text-indigo-600 hover:underline transition-colors">login</a> to interact.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
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