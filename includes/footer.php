<?php
// ตรวจสอบหน้าปัจจุบัน (เผื่อกรณีไฟล์ footer ถูกเรียกใช้แยก)
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

// ฟังก์ชันเลือกสีจุด (ถ้าตรงหน้าปัจจุบัน = สีทอง, ถ้าไม่ตรง = สีเทา)
function footerDotClass($pageName, $current_page) {
    return ($current_page == $pageName) ? 'bg-gold-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]' : 'bg-slate-600';
}

// ฟังก์ชันเลือกสีข้อความ
function footerTextClass($pageName, $current_page) {
    return ($current_page == $pageName) ? 'text-gold-400 font-medium' : 'hover:text-gold-400 transition';
}
?>
<footer class="bg-slate-900 text-slate-300 py-12 border-t-4 border-gold-500 mt-auto">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
            
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-10 h-10 bg-gold-500 text-slate-900 rounded-full flex items-center justify-center shadow-lg shadow-gold-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <span class="text-xl font-serif font-bold text-white tracking-widest block">THE LIBRARY</span>
                        <span class="text-[10px] text-gold-500 uppercase tracking-widest block -mt-1">Premium Service</span>
                    </div>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed font-light">
                    เรามุ่งมั่นที่จะเป็นแหล่งรวมหนังสือคุณภาพ ทั้งวรรณกรรมและหนังสือวิชาการ เพื่อส่งเสริมการเรียนรู้และจินตนาการของคุณ พร้อมบริการที่รวดเร็วและใส่ใจ
                </p>
            </div>

            <div>
                <h4 class="text-white font-serif font-bold mb-6 text-lg">เมนูนำทาง</h4>
                <ul class="space-y-3 text-sm font-light">
                    <li>
                        <a href="index.php" class="<?php echo footerTextClass('index.php', $current_page); ?> flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo footerDotClass('index.php', $current_page); ?>"></span> 
                            หน้าแรก
                        </a>
                    </li>
                    <li>
                        <a href="search.php" class="<?php echo footerTextClass('search.php', $current_page); ?> flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo footerDotClass('search.php', $current_page); ?>"></span> 
                            ค้นหาหนังสือ
                        </a>
                    </li>
                    <li>
                        <a href="cart.php" class="<?php echo footerTextClass('cart.php', $current_page); ?> flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo footerDotClass('cart.php', $current_page); ?>"></span> 
                            ตะกร้าสินค้า
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="<?php echo footerTextClass('profile.php', $current_page); ?> flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo footerDotClass('profile.php', $current_page); ?>"></span> 
                            บัญชีของฉัน
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-serif font-bold mb-6 text-lg">ติดต่อเรา</h4>
                <ul class="space-y-4 text-sm font-light">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gold-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>สาขาแม่กรณ์ เชียงราย <br><span class="text-xs text-slate-500">เปิดทุกวัน 09:00 - 18:00 น.</span></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gold-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span class="font-mono text-lg tracking-wide">085-558-6077</span>
                    </li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-serif font-bold mb-6 text-lg">ติดตามข่าวสาร</h4>
                <p class="text-xs text-slate-400 mb-4">รับโปรโมชั่นและหนังสือมาใหม่ก่อนใคร</p>
                <div class="flex">
                    <input type="email" placeholder="อีเมลของคุณ" class="bg-slate-800 text-white px-4 py-3 rounded-l-lg text-sm w-full focus:outline-none focus:ring-1 focus:ring-gold-500 border border-slate-700">
                    <button class="bg-gold-500 text-slate-900 px-4 py-3 rounded-r-lg font-bold hover:bg-gold-400 transition border border-gold-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        </div>
        <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'member'): ?>
    <?php require_once 'chat_widget.php'; ?>
<?php endif; ?>
</footer>
</body>
</html>