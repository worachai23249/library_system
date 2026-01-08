<?php require_once 'includes/header.php'; ?>

<div class="relative bg-slate-900 overflow-hidden">
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80" class="w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent"></div>
    </div>
    
    <div class="relative container mx-auto px-6 py-24 md:py-32 flex flex-col items-center text-center">
        <span class="text-gold-400 font-bold tracking-widest uppercase text-sm mb-4 animate-bounce">Welcome to The Library</span>
        <h1 class="text-4xl md:text-6xl font-serif font-bold text-white mb-6 leading-tight drop-shadow-lg">
            เปิดโลกจินตนาการ <br> <span class="text-transparent bg-clip-text bg-gradient-to-r from-gold-300 to-yellow-600">ผ่านตัวหนังสือ</span>
        </h1>
        <p class="text-slate-300 text-lg md:text-xl max-w-2xl mb-10 font-light">
            คัดสรรหนังสือคุณภาพดีมาให้คุณเลือกสรร ทั้งนิยาย วรรณกรรม และหนังสือความรู้ พร้อมบริการส่งตรงถึงบ้าน
        </p>
        <div class="flex gap-4">
            <a href="#books-section" class="bg-gold-500 text-slate-900 px-8 py-3 rounded-full font-bold hover:bg-white hover:text-slate-900 transition duration-300 shadow-lg shadow-gold-500/30">
                เลือกดูหนังสือ
            </a>
            <a href="search.php" class="bg-transparent border border-white text-white px-8 py-3 rounded-full font-bold hover:bg-white hover:text-slate-900 transition duration-300">
                ค้นหา
            </a>
        </div>
    </div>
</div>

<div id="books-section" class="container mx-auto px-6 py-16">
    
    <div class="flex justify-between items-end mb-10 border-b border-slate-200 pb-4">
        <div>
            <h2 class="text-3xl font-serif font-bold text-slate-800">หนังสือแนะนำ</h2>
            <p class="text-slate-500 mt-2 font-light">รายการหนังสือยอดนิยมที่คุณไม่ควรพลาด</p>
        </div>
        <a href="search.php" class="hidden md:flex items-center gap-2 text-gold-600 font-bold hover:text-gold-700 transition">
            ดูทั้งหมด <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 md:gap-8">
        <?php
        // ดึงข้อมูลหนังสือ
        $sql = "SELECT b.*, c.name as category_name 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE b.status = 'available' 
                ORDER BY b.created_at DESC LIMIT 10";
        $stmt = $pdo->query($sql);
        $books = $stmt->fetchAll();

        foreach ($books as $book):
        ?>
            <div class="group bg-white rounded-xl shadow-sm hover:shadow-2xl transition duration-300 border border-slate-100 overflow-hidden flex flex-col h-full relative">
                
                <div class="absolute top-2 right-2 z-10 flex flex-col items-end gap-1">
                    <?php if($book['stock_rent'] > 0): ?>
                        <span class="bg-blue-600/90 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                            เช่า: <?php echo $book['stock_rent']; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if($book['stock_sale'] > 0): ?>
                        <span class="bg-emerald-600/90 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                            ขาย: <?php echo $book['stock_sale']; ?>
                        </span>
                    <?php endif; ?>

                    <?php if($book['stock_rent'] <= 0 && $book['stock_sale'] <= 0): ?>
                        <span class="bg-red-500/90 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                            สินค้าหมด
                        </span>
                    <?php endif; ?>
                </div>

                <div class="relative aspect-[2/3] overflow-hidden bg-slate-100">
                    <?php if ($book['cover_image']): ?>
                        <img src="uploads/covers/<?php echo $book['cover_image']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500 ease-in-out">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-300">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                    <?php endif; ?>
                    
                    <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center">
                        <span class="bg-white text-slate-900 px-6 py-2 rounded-full font-bold text-sm transform translate-y-4 group-hover:translate-y-0 transition duration-300 shadow-lg">
                            ดูรายละเอียด
                        </span>
                    </a>
                </div>

                <div class="p-4 flex flex-col flex-grow">
                    <div class="text-[10px] font-bold text-gold-600 uppercase tracking-wider mb-1">
                        <?php echo htmlspecialchars($book['category_name'] ?? 'General'); ?>
                    </div>
                    
                    <h3 class="font-serif font-bold text-slate-800 text-lg leading-tight mb-1 line-clamp-2 min-h-[3rem]">
                        <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="hover:text-gold-600 transition">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </a>
                    </h3>
                    
                    <p class="text-xs text-slate-500 mb-3 font-light">
                        โดย <?php echo htmlspecialchars($book['author']); ?>
                    </p>

                    <div class="mt-auto pt-3 border-t border-slate-50 flex justify-between items-center">
                        <div class="flex flex-col">
                            <?php if($book['stock_rent'] > 0): ?>
                                <span class="text-xs text-slate-400">เช่า <strong class="text-slate-700"><?php echo number_format($book['rent_price']); ?>฿</strong></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                             <?php if($book['stock_sale'] > 0): ?>
                                <span class="text-lg font-bold text-gold-600"><?php echo number_format($book['sell_price']); ?> ฿</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="mt-10 text-center md:hidden">
         <a href="search.php" class="inline-block bg-slate-100 text-slate-700 px-6 py-3 rounded-lg font-bold w-full hover:bg-slate-200">
            ดูหนังสือทั้งหมด
        </a>
    </div>

</div>

<div class="bg-slate-50 py-16 border-t border-slate-200">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="p-6">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-md text-gold-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h3 class="font-serif font-bold text-xl mb-2">หนังสือคุณภาพ</h3>
                <p class="text-slate-500 text-sm">คัดสรรเฉพาะหนังสือสภาพดี เนื้อหาเยี่ยม ทั้งปกแข็งและปกอ่อน</p>
            </div>
            <div class="p-6">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-md text-gold-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h3 class="font-serif font-bold text-xl mb-2">จัดส่งรวดเร็ว</h3>
                <p class="text-slate-500 text-sm">แพ็คกันกระแทกอย่างดี ส่งไว ถึงมือคุณภายใน 1-3 วันทำการ</p>
            </div>
            <div class="p-6">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-md text-gold-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-serif font-bold text-xl mb-2">บริการประทับใจ</h3>
                <p class="text-slate-500 text-sm">ทีมงานพร้อมดูแล แนะนำหนังสือ และติดตามพัสดุให้คุณตลอดเวลา</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>