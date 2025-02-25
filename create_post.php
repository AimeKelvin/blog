<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = 'uploads/' . uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }
    $video = '';
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $video = 'uploads/' . uniqid() . '.' . pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['video']['tmp_name'], $video);
    }

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image, video) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $content, $image, $video]);
    header('Location: index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Zamuka Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-6 min-h-screen flex items-center justify-center">
        <div class="bg-white shadow-xl rounded-lg p-8 w-full max-w-2xl">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Create a New Post</h1>
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="title" placeholder="Enter post title" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                    <textarea name="content" id="content" placeholder="Write your post content" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors h-32 resize-none"></textarea>
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Upload Image (Optional)</label>
                    <input type="file" name="image" id="image" accept="image/*" class="w-full p-2 border border-gray-300 rounded-md text-sm text-gray-600">
                    <div id="image-preview" class="mt-2 hidden">
                        <img id="preview-image" class="w-48 h-48 object-cover rounded-md border border-gray-300">
                    </div>
                </div>
                <div>
                    <label for="video" class="block text-sm font-medium text-gray-700 mb-1">Upload Video (Optional)</label>
                    <input type="file" name="video" id="video" accept="video/*" class="w-full p-2 border border-gray-300 rounded-md text-sm text-gray-600">
                    <div id="video-preview" class="mt-2 hidden">
                        <video id="preview-video" controls class="w-48 h-48 object-cover rounded-md border border-gray-300">
                            <source src="" type="video/mp4">
                        </video>
                    </div>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-3 rounded-md hover:from-indigo-700 hover:to-purple-700 transition-all transform hover:scale-105">Post Now</button>
                <p class="text-gray-600 text-center"><a href="index.php" class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors">Back to Feed</a></p>
            </form>
        </div>
    </div>

    <!-- JavaScript for media previews -->
    <script>
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-image');
            if (file) {
                preview.classList.remove('hidden');
                previewImg.src = URL.createObjectURL(file);
            } else {
                preview.classList.add('hidden');
                previewImg.src = '';
            }
        });

        document.getElementById('video').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('video-preview');
            const previewVideo = document.getElementById('preview-video');
            if (file) {
                preview.classList.remove('hidden');
                previewVideo.src = URL.createObjectURL(file);
            } else {
                preview.classList.add('hidden');
                previewVideo.src = '';
            }
        });
    </script>
</body>
</html>