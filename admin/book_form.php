<?php 
require_once 'header.php'; 

// ดึงหมวดหมู่
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
$book = null;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $book = $stmt->fetch();
}
?>

<div class="max-w-5xl mx-auto mt-8 mb-12">
    <div class="flex items-center gap-4 mb-8">
        <a href="books.php" class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-navy-900 hover:border-navy-900 transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-3xl font-serif font-bold text-navy-900">
                <?php echo $book ? '✏️ แก้ไขข้อมูลหนังสือ' : '📖 เพิ่มหนังสือใหม่'; ?>
            </h1>
            <p class="text-slate-500 text-sm mt-1">กรอกข้อมูลหนังสือ ราคา และจัดการรูปภาพ</p>
        </div>
    </div>

    <form action="book_save.php" method="post" enctype="multipart/form-data">
        <?php if ($book): ?>
            <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
            <input type="hidden" name="old_cover" value="<?php echo $book['cover_image'] ?? ''; ?>">
            <input type="hidden" name="old_back_cover" value="<?php echo $book['back_cover_image'] ?? ''; ?>">
        <?php endif; ?>

        <input type="hidden" name="price" value="<?php echo $book['price'] ?? '0'; ?>">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center">
                    <label class="block text-sm font-bold text-navy-900 mb-4">🖼️ รูปปกหน้า (Front Cover)</label>
                    <div class="relative w-full aspect-[2/3] bg-slate-50 rounded-lg border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden mb-4 group hover:border-gold-400 transition cursor-pointer" onclick="document.getElementById('cover_input').click()">
                        <img id="preview_front" src="<?php echo !empty($book['cover_image']) ? '../uploads/covers/'.$book['cover_image'] : ''; ?>" 
                             class="absolute w-full h-full object-cover <?php echo !empty($book['cover_image']) ? '' : 'hidden'; ?>">
                        <div id="placeholder_front" class="text-slate-400 flex flex-col items-center <?php echo !empty($book['cover_image']) ? 'hidden' : ''; ?>">
                            <svg class="w-10 h-10 mb-2 group-hover:text-gold-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-xs">คลิกเพื่ออัปโหลด</span>
                        </div>
                    </div>
                    <input type="file" name="cover_image" id="cover_input" class="hidden" accept="image/*" onchange="previewImage(this, 'preview_front', 'placeholder_front')">
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center">
                    <label class="block text-sm font-bold text-navy-900 mb-4">🖼️ รูปปกหลัง (Back Cover)</label>
                    <div class="relative w-full aspect-[2/3] bg-slate-50 rounded-lg border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden mb-4 group hover:border-gold-400 transition cursor-pointer" onclick="document.getElementById('back_cover_input').click()">
                        <img id="preview_back" src="<?php echo !empty($book['back_cover_image']) ? '../uploads/covers/'.$book['back_cover_image'] : ''; ?>" 
                             class="absolute w-full h-full object-cover <?php echo !empty($book['back_cover_image']) ? '' : 'hidden'; ?>">
                        <div id="placeholder_back" class="text-slate-400 flex flex-col items-center <?php echo !empty($book['back_cover_image']) ? 'hidden' : ''; ?>">
                            <svg class="w-10 h-10 mb-2 group-hover:text-gold-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-xs">คลิกเพื่ออัปโหลด</span>
                        </div>
                    </div>
                    <input type="file" name="back_cover_image" id="back_cover_input" class="hidden" accept="image/*" onchange="previewImage(this, 'preview_back', 'placeholder_back')">
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                    <h3 class="font-bold text-navy-800 mb-6 flex items-center gap-2 text-lg border-b border-slate-100 pb-4">
                        <span class="w-1.5 h-6 bg-navy-900 rounded-full"></span> ข้อมูลทั่วไป
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-navy-700 mb-2">ชื่อหนังสือ <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($book['title'] ?? ''); ?>" 
                                   class="w-full border-slate-200 border rounded-lg px-4 py-3 focus:ring-1 focus:ring-gold-500 focus:border-gold-500 focus:outline-none bg-slate-50 focus:bg-white transition" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-navy-700 mb-2">ผู้แต่ง <span class="text-red-500">*</span></label>
                            <input type="text" name="author" value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>" 
                                   class="w-full border-slate-200 border rounded-lg px-4 py-3 focus:ring-1 focus:ring-gold-500 focus:border-gold-500 focus:outline-none bg-slate-50 focus:bg-white transition" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-navy-700 mb-2">ISBN</label>
                            <input type="text" name="isbn" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>" 
                                   class="w-full border-slate-200 border rounded-lg px-4 py-3 focus:ring-1 focus:ring-gold-500 focus:border-gold-500 focus:outline-none bg-slate-50 focus:bg-white transition font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-navy-700 mb-2">หมวดหมู่</label>
                            <select name="category_id" class="w-full border-slate-200 border rounded-lg px-4 py-3 focus:ring-1 focus:ring-gold-500 focus:border-gold-500 focus:outline-none bg-slate-50 focus:bg-white transition">
                                <?php foreach ($cats as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($book && $book['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo $cat['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-navy-700 mb-2">📝 เนื้อหาโดยสังเขป</label>
                        <textarea name="description" rows="4" 
                                  class="w-full border-slate-200 border rounded-lg px-4 py-3 focus:ring-1 focus:ring-gold-500 focus:border-gold-500 focus:outline-none bg-slate-50 focus:bg-white transition"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="bg-slate-50 p-8 rounded-2xl border border-slate-200">
                    <h3 class="font-bold text-navy-800 mb-6 flex items-center gap-2 text-lg">
                        <span class="w-1.5 h-6 bg-gold-500 rounded-full"></span> จัดการสต็อกและราคา
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        <div class="bg-white p-6 rounded-xl border border-blue-100 shadow-sm relative overflow-hidden group hover:border-blue-300 transition">
                            <div class="absolute top-0 right-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-8 -mt-8"></div>
                            <h4 class="font-bold text-blue-800 border-b border-blue-50 pb-3 mb-4 flex items-center gap-2">
                                📘 สำหรับเช่า
                            </h4>
                            
                            <div class="mb-5">
                                <label class="block text-slate-500 text-xs font-bold uppercase mb-1">จำนวนคงเหลือ (เล่ม)</label>
                                <input type="number" name="stock_rent" value="<?php echo $book['stock_rent'] ?? '1'; ?>" min="0" 
                                       class="w-full border-slate-200 border rounded-lg px-3 py-2 font-bold text-center text-blue-700 bg-blue-50 focus:ring-2 focus:ring-blue-200 outline-none">
                            </div>

                            <label class="block text-slate-500 text-xs font-bold uppercase mb-2">ราคาเช่า (บาท)</label>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-500 font-bold w-12">7 วัน</span>
                                    <input type="number" step="0.01" name="rent_price_7" placeholder="0.00"
                                           value="<?php echo $book['rent_price_7'] ?? '0'; ?>" 
                                           class="flex-1 border-slate-200 border rounded px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-500 font-bold w-12">15 วัน</span>
                                    <input type="number" step="0.01" name="rent_price_15" placeholder="0.00"
                                           value="<?php echo $book['rent_price_15'] ?? '0'; ?>" 
                                           class="flex-1 border-slate-200 border rounded px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-500 font-bold w-12">30 วัน</span>
                                    <input type="number" step="0.01" name="rent_price_30" placeholder="0.00"
                                           value="<?php echo $book['rent_price_30'] ?? '0'; ?>" 
                                           class="flex-1 border-slate-200 border rounded px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none">
                                </div>
                            </div>
                            <input type="hidden" name="rent_price" value="<?php echo $book['rent_price'] ?? '0'; ?>">
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-emerald-100 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition">
                            <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-50 rounded-bl-full -mr-8 -mt-8"></div>
                            <h4 class="font-bold text-emerald-800 border-b border-emerald-50 pb-3 mb-4 flex items-center gap-2">
                                📗 สำหรับขายขาด
                            </h4>
                            
                            <div class="mb-5">
                                <label class="block text-slate-500 text-xs font-bold uppercase mb-1">จำนวนคงเหลือ (เล่ม)</label>
                                <input type="number" name="stock_sale" value="<?php echo $book['stock_sale'] ?? '0'; ?>" min="0" 
                                       class="w-full border-slate-200 border rounded-lg px-3 py-2 font-bold text-center text-emerald-700 bg-emerald-50 focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div class="mt-2">
                                <label class="block text-slate-500 text-xs font-bold uppercase mb-2">ราคาขายหน้าปก (บาท)</label>
                                <input type="number" name="sell_price" value="<?php echo $book['sell_price'] ?? '0'; ?>" 
                                       class="w-full border-slate-200 border rounded-lg px-4 py-2.5 text-sm font-bold focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pt-8 border-t border-slate-200 mt-8">
            <a href="books.php" class="px-8 py-3 rounded-full border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition">ยกเลิก</a>
            <button type="submit" class="bg-navy-900 text-white px-10 py-3 rounded-full hover:bg-gold-500 hover:text-navy-900 font-bold shadow-lg transform active:scale-95 transition">บันทึกข้อมูล</button>
        </div>
    </form>
</div>

<script>
function previewImage(input, imgId, placeholderId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(imgId).src = e.target.result;
            document.getElementById(imgId).classList.remove('hidden');
            document.getElementById(placeholderId).classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'footer.php'; ?>