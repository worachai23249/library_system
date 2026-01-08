<?php
session_start();
require_once 'config/db.php';

$email = $_SESSION['pending_email'] ?? '';
if (!$email) {
    header("Location: login.php");
    exit;
}

$error_msg = "";

if (isset($_POST['verify'])) {
    $otp_input = trim($_POST['otp']);
    
    // ตรวจสอบ OTP
    $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ? AND otp_code = ?");
    $stmt->execute([$email, $otp_input]);
    $user = $stmt->fetch();

    if ($user) {
        // อัปเดตสถานะเป็นยืนยันแล้ว
        $update = $pdo->prepare("UPDATE members SET is_email_verified = 1, otp_code = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        
        // ล้าง session ชั่วคราว
        unset($_SESSION['pending_email']);
        
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'ยืนยันสำเร็จ!',
                    text: 'บัญชีของคุณเปิดใช้งานแล้ว กรุณาเข้าสู่ระบบ',
                    confirmButtonText: 'ไปหน้าเข้าสู่ระบบ',
                    confirmButtonColor: '#0f172a'
                }).then(() => {
                    window.location.href = 'login.php';
                });
            }, 100);
        </script>";
    } else {
        $error_msg = "รหัส OTP ไม่ถูกต้อง กรุณาลองใหม่";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ยืนยันอีเมล - The Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>body { font-family: 'Kanit', sans-serif; }</style>
</head>
<body class="flex items-center justify-center min-h-screen bg-slate-900 p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">ยืนยันรหัส OTP</h2>
        <p class="text-slate-500 text-sm mb-6">เราได้ส่งรหัส 6 หลักไปที่อีเมล<br><span class="text-blue-600 font-bold"><?php echo htmlspecialchars($email); ?></span></p>

        <form method="post" class="space-y-4">
            <input type="text" name="otp" maxlength="6" class="w-full text-center text-3xl tracking-[10px] font-bold border-2 border-slate-200 rounded-xl py-3 focus:border-blue-500 focus:outline-none transition text-slate-700" placeholder="000000" required>
            
            <?php if($error_msg): ?>
                <p class="text-red-500 text-sm"><?php echo $error_msg; ?></p>
            <?php endif; ?>

            <button type="submit" name="verify" class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-blue-600 transition shadow-lg">
                ยืนยันตัวตน
            </button>
        </form>
        
        <div class="mt-6 border-t border-slate-100 pt-4">
            <a href="login.php" class="text-sm text-slate-400 hover:text-slate-600">กลับไปหน้าเข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>