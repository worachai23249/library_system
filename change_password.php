<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$msg = "";
$msg_type = ""; // success or error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM members WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE members SET password = ? WHERE id = ?")->execute([$new_hash, $_SESSION['user_id']]);
            $msg = "เปลี่ยนรหัสผ่านสำเร็จ";
            $msg_type = "success";
        } else {
            $msg = "รหัสผ่านใหม่ไม่ตรงกัน";
            $msg_type = "error";
        }
    } else {
        $msg = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        $msg_type = "error";
    }
}

require_once 'includes/header.php';
?>

<div class="bg-slate-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="flex flex-col md:flex-row gap-8">
            
            <aside class="w-full md:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                    <nav class="space-y-1">
                        <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span class="text-sm font-medium">ข้อมูลส่วนตัว</span>
                        </a>
                        <a href="profile_addresses.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="text-sm font-medium">ที่อยู่จัดส่ง</span>
                        </a>
                        <a href="change_password.php" class="flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-md transition">
                            <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span class="text-sm font-medium">เปลี่ยนรหัสผ่าน</span>
                        </a>
                        <a href="verify.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-medium">ยืนยันตัวตน (KYC)</span>
                        </a>
                        <a href="messages.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="text-sm font-medium">การแจ้งเตือน</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <main class="flex-grow">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                    <h1 class="text-2xl font-serif font-bold text-slate-800 mb-6 pb-4 border-b border-slate-100 flex items-center gap-3">
                        <span class="bg-gold-100 text-gold-600 p-2 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></span>
                        เปลี่ยนรหัสผ่าน
                    </h1>

                    <?php if($msg): ?>
                        <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?php echo $msg_type=='success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="<?php echo $msg_type=='success' ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' : 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z'; ?>" clip-rule="evenodd"/></svg>
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="max-w-lg space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">รหัสผ่านปัจจุบัน</label>
                            <input type="password" name="current_password" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">รหัสผ่านใหม่</label>
                            <input type="password" name="new_password" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" name="confirm_password" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 transition">
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-gold-500 hover:text-slate-900 transition shadow-lg transform active:scale-95">
                                เปลี่ยนรหัสผ่าน
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>