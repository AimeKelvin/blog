<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];

    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $profile_image = 'uploads/' . uniqid() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);
    }

    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, profile_image = ? WHERE id = ?");
    $stmt->execute([$username, $email, $bio, $profile_image, $user_id]);
    header('Location: profile.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Zamuka Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-6 min-h-screen flex items-center justify-center">
        <div class="bg-white shadow-xl rounded-lg p-8 w-full max-w-md">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Edit Profile</h1>
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div class="flex items-center space-x-4 mb-4">
                    <img src="<?= htmlspecialchars($user['profile_image'] ?? 'default_profile.png') ?>" alt="Current Profile Image" class="w-20 h-20 rounded-full object-cover border-2 border-indigo-500">
                    <input type="file" name="profile_image" accept="image/*" class="text-sm text-gray-600">
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea name="bio" id="bio" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-3 rounded-md hover:from-indigo-700 hover:to-purple-700 transition-all transform hover:scale-105">Save Changes</button>
                <p class="text-gray-600 text-center"><a href="profile.php" class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors">Back to Profile</a></p>
            </form>
        </div>
    </div>
</body>
</html>