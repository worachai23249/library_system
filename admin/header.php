<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. เชื่อมต่อฐานข้อมูล
if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    die("Error: ไม่พบไฟล์ config/db.php");
}

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $redirect_url = defined('BASE_URL') ? BASE_URL . '/login.php' : '../login.php';
    header("Location: " . $redirect_url);
    exit;
}

$user_name = $_SESSION['name'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);

// --- ตัวแปรแจ้งเตือน (Notifications) ---
try {
    $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
} catch (Exception $e) { $pending_orders = 0; }

try {
    $unread_chats = $pdo->query("SELECT COUNT(*) FROM chats WHERE receiver_id = 1 AND is_read = 0")->fetchColumn();
} catch (Exception $e) { $unread_chats = 0; }

try {
    $pending_kyc = $pdo->query("SELECT COUNT(*) FROM members WHERE verification_status = 'pending'")->fetchColumn();
} catch (Exception $e) { $pending_kyc = 0; }

// ✅ ฟังก์ชันเช็คว่าเมนูไหน Active (ปรับ Class ให้เข้าธีม)
function menuClass($page_name, $current_page) {
    $isActive = false;
    if ($current_page == $page_name) $isActive = true;
    if ($page_name == 'books.php' && (strpos($current_page, 'book_') !== false)) $isActive = true;
    if ($page_name == 'members.php' && (strpos($current_page, 'member_') !== false)) $isActive = true;
    if ($page_name == 'borrow.php' && ($current_page == 'return.php')) $isActive = true;
    if ($page_name == 'orders.php' && ($current_page == 'order_update.php')) $isActive = true;
    if ($page_name == 'verifications.php' && ($current_page == 'verifications.php')) $isActive = true;

    // ธีมใหม่: สีทอง + ตัวหนา
    return $isActive 
        ? 'text-gold-600 font-bold bg-gold-50/50' 
        : 'text-slate-600 hover:text-gold-600 hover:bg-slate-50';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The Library</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706' },
                        navy: { 800: '#1e293b', 900: '#0f172a' }
                    },
                    fontFamily: {
                        serif: ['"Playfair Display"', 'serif'],
                        sans: ['"Kanit"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f8fafc; color: #1e293b; }
        h1, h2, h3, .font-serif { font-family: 'Playfair Display', serif; }
        
        /* Glassmorphism Navbar */
        .glass-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-slate-50">

<nav class="glass-nav sticky top-0 z-50 transition-all duration-300">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-navy-900 text-gold-400 rounded-full flex items-center justify-center shadow-lg border border-gold-500 group-hover:scale-105 transition duration-300">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <div>
                    <span class="text-xl font-serif font-bold tracking-wide text-navy-900">THE LIBRARY</span>
                    <p class="text-[10px] text-gold-600 uppercase tracking-widest -mt-1 font-bold">Admin Panel</p>
                </div>
            </a>

            <div class="hidden lg:flex items-center gap-1">
                
                <a href="index.php" class="<?php echo menuClass('index.php', $current_page); ?> px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    ภาพรวม
                </a>

                <a href="orders.php" class="relative <?php echo menuClass('orders.php', $current_page); ?> px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    คำสั่งซื้อ
                    <?php if($pending_orders > 0): ?>
                        <span class="absolute top-1 right-1 flex h-2.5 w-2.5">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gold-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-gold-500"></span>
                        </span>
                    <?php endif; ?>
                </a>

                <a href="books.php" class="<?php echo menuClass('books.php', $current_page); ?> px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    คลังหนังสือ
                </a>

                <a href="members.php" class="<?php echo menuClass('members.php', $current_page); ?> px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    สมาชิก
                </a>

                <a href="verifications.php" class="relative <?php echo menuClass('verifications.php', $current_page); ?> px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    KYC
                    <?php if($pending_kyc > 0): ?>
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full transform translate-x-1/4 -translate-y-1/4 animate-pulse">
                            <?php echo $pending_kyc; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a href="chats.php" class="relative <?php echo menuClass('chats.php', $current_page); ?> px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    แชท
                    <?php if($unread_chats > 0): ?>
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full transform translate-x-1/4 -translate-y-1/4">
                            <?php echo $unread_chats; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="h-6 w-px bg-slate-200 mx-2"></div>

                <div class="relative group" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" class="<?php echo (strpos($current_page, 'borrow')!==false || strpos($current_page, 'return')!==false || strpos($current_page, 'report')!==false) ? 'text-gold-600 font-bold bg-gold-50/50' : 'text-slate-600 hover:text-gold-600 hover:bg-slate-50'; ?> px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        ระบบยืม-คืน
                        <svg class="w-3 h-3 ml-1 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" x-transition class="absolute right-0 top-full mt-2 w-56 z-50 origin-top-right">
                        <div class="bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden py-2">
                            <div class="px-4 py-2 text-xs font-bold text-slate-400 uppercase tracking-wider">จัดการรายการ</div>
                            <a href="borrow.php" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-gold-50 hover:text-gold-700 transition">ยืมหนังสือ</a>
                            <a href="return.php" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-gold-50 hover:text-gold-700 transition">รับคืนหนังสือ</a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <div class="px-4 py-2 text-xs font-bold text-slate-400 uppercase tracking-wider">รายงาน</div>
                            <a href="history.php" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-gold-50 hover:text-gold-700 transition">ประวัติทั้งหมด</a>
                            <a href="report_overdue.php" class="block px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">⚠️ รายการค้างส่ง</a>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="text-[10px] text-gold-600 uppercase tracking-widest font-bold">Administrator</div>
                    </div>
                    <div class="h-9 w-9 rounded-full bg-navy-900 text-gold-400 flex items-center justify-center font-serif font-bold text-sm shadow-md border border-gold-500/50">
                        <?php echo mb_substr($user_name, 0, 1); ?>
                    </div>
                    <a href="../logout.php" onclick="return confirm('ยืนยันออกจากระบบ?')" class="text-slate-400 hover:text-red-500 transition ml-2" title="ออกจากระบบ">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>

        </div>
    </div>
</nav>

<div class="container mx-auto px-4 py-8 flex-grow">