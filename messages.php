<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Mark as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);

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
                        <a href="change_password.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span class="text-sm font-medium">เปลี่ยนรหัสผ่าน</span>
                        </a>
                        <a href="verify.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-medium">ยืนยันตัวตน (KYC)</span>
                        </a>
                        <a href="messages.php" class="flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-md transition">
                            <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="text-sm font-medium">การแจ้งเตือน</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <main class="flex-grow">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 min-h-[500px]">
                    <h1 class="text-2xl font-serif font-bold text-slate-800 mb-8 flex items-center gap-3">
                        <span class="bg-gold-100 text-gold-600 p-2 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg></span>
                        กล่องข้อความ
                    </h1>

                    <?php if (count($messages) == 0): ?>
                        <div class="text-center py-20 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm text-slate-300">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            </div>
                            <p class="text-slate-500 font-light">ยังไม่มีข้อความแจ้งเตือนใหม่</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6 relative before:absolute before:inset-y-0 before:left-6 before:w-0.5 before:bg-slate-200 before:z-0">
                            <?php foreach ($messages as $msg): ?>
                                <div class="relative flex gap-6 z-10 group">
                                    <div class="w-12 h-12 flex-shrink-0 bg-white border-2 <?php echo strpos($msg['title'], 'อนุมัติ') !== false ? 'border-emerald-500 text-emerald-500' : 'border-slate-300 text-slate-400 group-hover:border-gold-500 group-hover:text-gold-500'; ?> rounded-full flex items-center justify-center transition shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div class="flex-grow bg-white border border-slate-100 rounded-2xl p-5 shadow-sm group-hover:shadow-md transition">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="font-bold text-slate-800 text-lg"><?php echo htmlspecialchars($msg['title']); ?></h3>
                                            <span class="text-xs text-slate-400 bg-slate-50 px-2 py-1 rounded"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <p class="text-slate-600 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>