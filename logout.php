<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-6 min-h-screen flex items-center justify-center">
        <div class="bg-white shadow-xl rounded-lg p-8 w-full max-w-md text-center">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Are you sure you want to logout?</h1>
            <p class="text-gray-600 mb-6">You’ll need to log in again to access your account.</p>
            <div class="space-x-4">
                <a href="logout_process.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">Logout</a>
                <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">Cancel</a>
            </div>
        </div>
    </div>
</body>
</html>