<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

// 1. ตรวจสอบชื่อหน้าปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);

// 2. ฟังก์ชันเลือกคลาสสีเมนู
function menuClass($pageName, $current_page) {
    $active = "text-gold-600 font-bold underline decoration-gold-500 decoration-2 underline-offset-8";
    $inactive = "text-slate-600 font-medium hover:text-slate-900 hover:text-gold-600 transition";
    return ($current_page == $pageName) ? $active : $inactive;
}

// 3. ดึงข้อมูลผู้ใช้ + นับจำนวนสินค้า + ✅ นับข้อความที่ยังไม่อ่าน
$cartQty = 0;
$unreadMsg = 0;
$currentUser = null;

if (isset($_SESSION['user_id'])) {
    // ดึงข้อมูล User
    $stmtUser = $pdo->prepare("SELECT fullname, profile_image FROM members WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch();

    // นับสินค้าในตะกร้า
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) { $cartQty += $item['qty']; }
    }

    // ✅ นับข้อความที่ยังไม่อ่าน (is_read = 0)
    $stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND is_read = 0");
    $stmtMsg->execute([$_SESSION['user_id']]);
    $unreadMsg = $stmtMsg->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Library | คลังปัญญาเหนือระดับ</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
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
        .glass-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <nav class="glass-nav fixed w-full z-50 top-0 transition-all duration-300" x-data="{ atTop: true }" @scroll.window="atTop = (window.pageYOffset < 20)">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-slate-900 text-gold-400 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition duration-500 border border-gold-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <div>
                    <span class="text-xl font-serif font-bold tracking-wide text-slate-900 group-hover:text-gold-600 transition">THE LIBRARY</span>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest -mt-1">Premium Books</p>
                </div>
            </a>

            <div class="hidden md:flex items-center gap-8">
                <a href="index.php" class="text-sm <?php echo menuClass('index.php', $current_page); ?>">หน้าแรก</a>
                <a href="search.php" class="text-sm <?php echo menuClass('search.php', $current_page); ?>">ค้นหาหนังสือ</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="history.php" class="text-sm <?php echo menuClass('history.php', $current_page); ?>">ประวัติการยืม</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/index.php" class="text-xs font-bold text-red-600 bg-red-50 px-3 py-1.5 rounded-full border border-red-100 hover:bg-red-600 hover:text-white transition">Backend</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-4">
                <a href="cart.php" class="relative group p-2 rounded-full hover:bg-slate-100 transition <?php echo ($current_page == 'cart.php') ? 'text-gold-600 bg-slate-50' : 'text-slate-700'; ?>">
                    <svg class="w-6 h-6 group-hover:text-gold-600 transition" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"></path></svg>
                    <?php if($cartQty > 0): ?>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-gold-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full shadow-md border-2 border-white"><?php echo $cartQty; ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 focus:outline-none ml-2 relative">
                            <div class="relative">
                                <?php if(!empty($currentUser['profile_image'])): ?>
                                    <img src="uploads/profiles/<?php echo $currentUser['profile_image']; ?>" class="w-9 h-9 rounded-full border-2 border-white shadow-sm object-cover hover:border-gold-400 transition">
                                <?php else: ?>
                                    <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 shadow-inner hover:bg-slate-300 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                <?php endif; ?>

                                <?php if($unreadMsg > 0): ?>
                                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 border-2 border-white rounded-full flex items-center justify-center text-[9px] text-white font-bold shadow-sm animate-pulse">
                                        <?php echo $unreadMsg > 9 ? '9+' : $unreadMsg; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </button>
                        
                        <div x-show="open" x-transition class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl border border-slate-100 py-2 z-50" style="display: none;">
                            <div class="px-4 py-3 border-b border-gray-100 mb-1 bg-slate-50">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Signed in as</p>
                                <p class="text-sm font-bold text-slate-800 truncate"><?php echo htmlspecialchars($currentUser['fullname'] ?? 'Member'); ?></p>
                            </div>
                            
                            <a href="profile.php" class="flex items-center justify-between px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-gold-600 transition group">
                                <span>โปรไฟล์ของฉัน</span>
                                <svg class="w-4 h-4 text-slate-300 group-hover:text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </a>
                            
                            <a href="messages.php" class="flex items-center justify-between px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-gold-600 transition group">
                                <span>การแจ้งเตือน</span>
                                <?php if($unreadMsg > 0): ?>
                                    <span class="bg-red-100 text-red-600 py-0.5 px-2 rounded-full text-xs font-bold"><?php echo $unreadMsg; ?></span>
                                <?php else: ?>
                                    <svg class="w-4 h-4 text-slate-300 group-hover:text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <?php endif; ?>
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>
                            
                            <a href="logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                ออกจากระบบ
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-bold bg-slate-900 text-white px-6 py-2.5 rounded-full hover:bg-gold-500 hover:text-slate-900 shadow-lg transition duration-300 transform hover:-translate-y-0.5">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="h-20"></div>