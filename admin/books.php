<?php
// ถอยหลัง 1 ชั้น (../) เพื่อไปหาไฟล์ config และ header
require_once 'header.php';

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // ใช้ BASE_URL เพื่อเด้งกลับหน้าแรกได้ถูกต้องแน่นอน
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '..') . "/index.php"); 
    exit;
}

// ดึงข้อมูลหนังสือ + ชื่อหมวดหมู่
$sql = "SELECT books.*, categories.name as category_name 
        FROM books 
        LEFT JOIN categories ON books.category_id = categories.id 
        ORDER BY books.id DESC";
$books = $pdo->query($sql)->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h1 class="text-3xl font-serif font-bold text-navy-900">📦 จัดการหนังสือ</h1>
        <p class="text-slate-500 text-sm mt-1">รายการหนังสือทั้งหมด การจัดการสต็อกและข้อมูลหนังสือ</p>
    </div>
    <a href="book_form.php" class="bg-navy-900 text-white px-5 py-2.5 rounded-full shadow-lg hover:bg-gold-500 hover:text-navy-900 transition transform hover:-translate-y-0.5 flex items-center gap-2 font-bold text-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        เพิ่มหนังสือใหม่
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="p-4 font-bold text-slate-500 text-sm text-center w-28">รูปปก</th>
                    <th class="p-4 font-bold text-slate-500 text-sm">ISBN / ชื่อเรื่อง</th>
                    <th class="p-4 font-bold text-slate-500 text-sm">หมวดหมู่</th>
                    <th class="p-4 font-bold text-slate-500 text-sm text-center">สถานะสต็อก</th>
                    <th class="p-4 font-bold text-slate-500 text-sm text-center">สถานะ</th>
                    <th class="p-4 font-bold text-slate-500 text-sm text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($books as $book): ?>
                <tr class="hover:bg-slate-50/50 transition duration-150 group">
                    
                    <td class="p-4 text-center align-middle">
                        <div class="relative w-16 h-24 mx-auto shadow-sm rounded overflow-hidden bg-slate-200 group-hover:shadow-md transition">
                            <?php if(!empty($book['cover_image'])): ?>
                                <img src="../uploads/covers/<?php echo $book['cover_image']; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-xs text-slate-400">No Pic</div>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="p-4 align-top">
                        <div class="text-xs font-mono text-slate-400 mb-1"><?php echo htmlspecialchars($book['isbn']); ?></div>
                        <div class="font-bold text-navy-900 text-base line-clamp-2 leading-tight mb-1" title="<?php echo htmlspecialchars($book['title']); ?>">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </div>
                        <div class="text-sm text-slate-500 flex items-center gap-1">
                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                            <?php echo htmlspecialchars($book['author']); ?>
                        </div>
                    </td>

                    <td class="p-4 align-top">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                            <?php echo htmlspecialchars($book['category_name']); ?>
                        </span>
                    </td>

                    <td class="p-4 align-top text-center">
                        <div class="flex flex-col gap-1.5 items-center justify-center">
                            <?php if(isset($book['stock_rent'])): ?>
                                <span class="text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 min-w-[60px]">
                                    เช่า: <?php echo $book['stock_rent']; ?>
                                </span>
                            <?php endif; ?>
                            <?php if(isset($book['stock_sale'])): ?>
                                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 min-w-[60px]">
                                    ขาย: <?php echo $book['stock_sale']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="p-4 text-center align-middle">
                        <?php 
                            $status_colors = [
                                'available' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'borrowed' => 'bg-red-100 text-red-800 border-red-200',
                                'lost' => 'bg-slate-100 text-slate-800 border-slate-200',
                                'repair' => 'bg-amber-100 text-amber-800 border-amber-200'
                            ];
                            $st = $book['status'];
                            $label = match($st) {
                                'available' => 'ปกติ', 'borrowed' => 'ถูกยืม', 'lost' => 'หาย', 'repair' => 'ซ่อม', default => $st
                            };
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $status_colors[$st] ?? 'bg-slate-100 text-slate-800'; ?>">
                            <?php echo $label; ?>
                        </span>
                    </td>
                    
                    <td class="p-4 text-center align-middle">
                        <div class="flex items-center justify-center gap-2">
                            <a href="book_form.php?id=<?php echo $book['id']; ?>" class="w-9 h-9 flex items-center justify-center bg-white border border-slate-200 text-slate-500 rounded-lg hover:bg-gold-50 hover:text-gold-600 hover:border-gold-200 transition shadow-sm" title="แก้ไข">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </a>
                            <a href="book_delete.php?id=<?php echo $book['id']; ?>" 
                               onclick="return confirm('ยืนยันที่จะลบหนังสือเล่มนี้?');" 
                               class="w-9 h-9 flex items-center justify-center bg-white border border-slate-200 text-slate-500 rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition shadow-sm" title="ลบ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if(count($books) == 0): ?>
            <div class="p-16 text-center text-slate-400 bg-white">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-navy-900">ยังไม่มีหนังสือในระบบ</h3>
                <p class="text-sm mt-1">กดปุ่ม "เพิ่มหนังสือใหม่" เพื่อเริ่มต้นจัดการคลังหนังสือของคุณ</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>