<?php
// ตรวจสอบว่าไม่ได้ถูกเรียกโดยตรง
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Direct access not allowed');
}
?>

</div> <footer class="bg-navy-900 text-slate-300 border-t-4 border-gold-500 mt-auto py-8">
    <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-6">
        
        <div class="flex items-center gap-4">
             <div class="w-10 h-10 bg-gold-500 text-navy-900 rounded-full flex items-center justify-center shadow-lg shadow-gold-500/20 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
            <div>
                <div class="flex items-baseline gap-2">
                    <span class="font-serif font-bold text-white text-lg tracking-wider">THE LIBRARY</span>
                    <span class="text-[10px] text-gold-500 uppercase font-bold tracking-widest bg-navy-800 px-1.5 py-0.5 rounded border border-navy-700">Admin Panel</span>
                </div>
                <p class="text-xs text-slate-500 mt-1 font-light">
                    &copy; <?php echo date('Y'); ?> Library Management System. All rights reserved.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-6 text-xs text-slate-400 font-medium">
            <a href="../index.php" target="_blank" class="hover:text-gold-400 transition flex items-center gap-1 group py-2">
                ไปหน้าเว็บไซต์หลัก 
                <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
            
            <span class="text-slate-700 h-4 w-px bg-slate-700"></span>
            
            <div class="flex items-center gap-2 cursor-default" title="System Version">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>System Online</span>
            </div>
            
            <span class="text-slate-600 font-mono">v2.5.0</span>
        </div>
    </div>
</footer>

</body>
</html>