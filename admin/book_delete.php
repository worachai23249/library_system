<?php
session_start();
require_once '../config/db.php';

if (isset($_GET['id']) && $_SESSION['role'] === 'admin') {
    try {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } catch (Exception $e) {
        // เงียบไว้ หรือ redirect ไปหน้า error (กรณีลบไม่ได้)
    }
}
header("Location: books.php");
exit;
?>