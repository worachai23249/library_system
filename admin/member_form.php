<?php
require_once 'header.php';

$member = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $member = $stmt->fetch();
}
?>

<div class="max-w-xl mx-auto mt-10 mb-12">
    <div class="flex items-center gap-4 mb-8">
        <a href="members.php" class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-navy-900 hover:border-navy-900 transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <h2 class="text-3xl font-serif font-bold text-navy-900">
            <?php echo $member ? '✏️ แก้ไขข้อมูลสมาชิก' : '👤 ลงทะเบียนสมาชิกใหม่'; ?>
        </h2>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-lg border border-slate-100">
        <form action="member_save.php" method="post" class="space-y-6">
            <?php if ($member): ?>
                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-bold text-navy-700 mb-2">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </span>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($member['fullname'] ?? ''); ?>" 
                           class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition" 
                           placeholder="ระบุชื่อจริง นามสกุลจริง" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-navy-700 mb-2">อีเมล (ใช้สำหรับ Login) <span class="text-red-500">*</span></label>
                <div class="relative">
                     <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" 
                           class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition" 
                           placeholder="name@example.com" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-navy-700 mb-2">เบอร์โทรศัพท์</label>
                <div class="relative">
                     <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </span>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" 
                           class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition" 
                           placeholder="0xx-xxx-xxxx">
                </div>
            </div>

            <div class="pt-2">
                <label class="block text-sm font-bold text-navy-700 mb-2">
                    รหัสผ่าน 
                    <?php if($member): ?>
                        <span class="text-xs font-normal text-slate-400 ml-1">(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</span>
                    <?php endif; ?>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </span>
                    <input type="password" name="password" 
                           class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition" 
                           placeholder="••••••••"
                           <?php echo $member ? '' : 'required'; ?> >
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t border-slate-100 mt-6">
                <button type="submit" class="flex-1 bg-navy-900 text-white py-3 rounded-lg hover:bg-gold-500 hover:text-navy-900 font-bold shadow-md transition transform active:scale-95">
                    บันทึกข้อมูล
                </button>
                <a href="members.php" class="flex-1 bg-white text-slate-600 border border-slate-200 py-3 rounded-lg hover:bg-slate-50 font-bold text-center transition">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>