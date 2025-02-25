<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

if (isset($_GET['user_id'])) {
    $followed_id = $_GET['user_id'];
    $follower_id = $_SESSION['user_id'];

    if ($follower_id == $followed_id) {
        die("You cannot follow yourself");
    }

    $stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->execute([$follower_id, $followed_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$follower_id, $followed_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $followed_id]);
    }
    header('Location: profile.php?id=' . $followed_id);
}
?>