<?php
$host = 'localhost';
$dbname = 'library_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 🔥 เพิ่มบรรทัดนี้ครับ!
    // /library_system คือชื่อโฟลเดอร์โปรเจกต์ของคุณใน htdocs
    define('BASE_URL', '/library_system'); 

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>