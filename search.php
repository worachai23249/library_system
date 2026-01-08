<?php
require_once 'config/db.php';

// 1. รับค่าการค้นหาและกรอง
$search = $_GET['q'] ?? '';
$category_id = $_GET['cat'] ?? '';

// 2. ดึงข้อมูลหมวดหมู่
$catStmt = $pdo->query("SELECT c.*, COUNT(b.id) as book_count 
                        FROM categories c 
                        LEFT JOIN books b ON c.id = b.category_id 
                        GROUP BY c.id 
                        ORDER BY c.name ASC");
$categories = $catStmt->fetchAll();

// 3. เตรียม Query ดึงหนังสือ
$sql = "SELECT * FROM books WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id) {
    $sql .= " AND category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$current_category_name = 'หนังสือทั้งหมด';
if ($search) {
    $current_category_name = "ผลการค้นหา: \"$search\"";
} elseif ($category_id) {
    foreach ($categories as $c) {
        if ($c['id'] == $category_id) {
            $current_category_name = $c['name'];
            break;
        }
    }
}

if (isset($_GET['ajax'])) {
    renderBooksContent($books, $current_category_name);
    exit;
}

function renderBooksContent($books, $title) {
    ?>
    <div class="flex justify-between items-end mb-8 animate-fade-in border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-3xl font-serif font-bold text-slate-800"><?php echo htmlspecialchars($title); ?></h1>
            <div class="h-1 w-20 bg-gold-500 mt-2 rounded-full"></div>
        </div>
        <span class="text-sm text-slate-500 font-light">พบ <?php echo count($books); ?> รายการ</span>
    </div>

    <?php if (count($books) > 0): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8 animate-fade-in">
            <?php foreach ($books as $item): ?>
                <a href="book_detail.php?id=<?php echo $item['id']; ?>" class="group bg-white rounded-xl shadow-sm hover:shadow-2xl border border-slate-100 overflow-hidden transition duration-500 flex flex-col h-full transform hover:-translate-y-1">
                    <div class="w-full aspect-[2/3] relative overflow-hidden bg-slate-100">
                        <?php if($item['cover_image']): ?>
                            <img src="uploads/covers/<?php echo $item['cover_image']; ?>" 
                                 class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700 ease-in-out">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-slate-200 text-slate-400 text-xs">No Cover</div>
                        <?php endif; ?>
                        
                        <div class="absolute top-2 right-2 flex flex-col items-end gap-1">
                            <?php if($item['stock_rent'] > 0): ?>
                                <span class="bg-blue-600/90 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg tracking-wider">
                                    เช่า: <?php echo $item['stock_rent']; ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if($item['stock_sale'] > 0): ?>
                                <span class="bg-emerald-600/90 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg tracking-wider">
                                    ขาย: <?php echo $item['stock_sale']; ?>
                                </span>
                            <?php endif; ?>

                            <?php if($item['stock_rent'] <= 0 && $item['stock_sale'] <= 0): ?>
                                <span class="bg-red-500/90 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg tracking-wider">
                                    สินค้าหมด
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-5 flex flex-col flex-grow relative">
                        <div class="absolute -top-4 right-4 w-8 h-8 bg-gold-500 rounded-full flex items-center justify-center shadow-lg text-white group-hover:bg-slate-900 transition duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>

                        <h3 class="font-serif font-bold text-slate-800 text-lg mb-1 line-clamp-2 leading-tight group-hover:text-gold-600 transition pt-2">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        <p class="text-xs text-slate-500 mb-4 font-light italic">โดย <?php echo htmlspecialchars($item['author']); ?></p>
                        
                        <div class="mt-auto pt-4 border-t border-slate-50 flex justify-between items-center">
                            <div class="flex flex-col">
                                <?php if($item['stock_rent'] > 0): ?>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-wide">ราคาเช่าเริ่มต้น</span>
                                    <span class="text-slate-800 font-bold text-lg">
                                        <?php 
                                            $startPrice = ($item['rent_price_7'] > 0) ? $item['rent_price_7'] : $item['rent_price'];
                                            echo number_format($startPrice); 
                                        ?> <span class="text-xs font-normal text-slate-500">บาท</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <?php if($item['stock_sale'] > 0): ?>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-wide">ราคาขาย</span>
                                    <div class="text-gold-600 font-bold text-lg"><?php echo number_format($item['sell_price']); ?> ฿</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-24 bg-white rounded-xl border border-dashed border-slate-300">
            <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            <p class="text-slate-500 text-lg font-serif">ไม่พบหนังสือในหมวดหมู่นี้</p>
            <button onclick="loadCategory('')" class="inline-block mt-4 text-gold-600 font-bold hover:underline cursor-pointer">
                ดูหนังสือทั้งหมด
            </button>
        </div>
    <?php endif; ?>
    <?php
}

require_once 'includes/header.php';
?>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
</style>

<div class="bg-slate-50 min-h-screen font-sans pb-20 pt-6">
    <div class="container mx-auto px-4 max-w-7xl mb-10">
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 p-6 border border-slate-100">
            <form id="searchForm" onsubmit="handleSearch(event)" class="flex gap-4 max-w-3xl mx-auto items-center">
                <div class="relative flex-grow group">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-gold-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" id="searchInput" name="q" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="ค้นหาชื่อหนังสือ, ผู้แต่ง, ISBN..." 
                           class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 transition outline-none text-slate-700 font-medium placeholder-slate-400">
                </div>
                <button type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-gold-500 hover:text-slate-900 transition shadow-lg transform hover:-translate-y-0.5 duration-300">
                    ค้นหา
                </button>
            </form>
        </div>
    </div>

    <div class="container mx-auto px-4 max-w-7xl">
        <div class="flex flex-col lg:flex-row gap-10">
            
            <aside class="w-full lg:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-28">
                    <h3 class="font-serif font-bold text-xl text-slate-800 mb-6 flex items-center gap-2 border-b border-slate-100 pb-4">
                        <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                        หมวดหมู่หนังสือ
                    </h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="javascript:void(0)" onclick="loadCategory('')" 
                               id="cat-link-all"
                               class="cat-link flex justify-between items-center px-4 py-3 rounded-xl text-sm font-medium transition duration-300 <?php echo !$category_id ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50 hover:text-gold-600'; ?>">
                                <span>ทั้งหมด</span>
                                <span class="<?php echo !$category_id ? 'bg-gold-500 text-slate-900' : 'bg-slate-100 text-slate-500'; ?> py-0.5 px-2.5 rounded-full text-xs font-bold">
                                    <?php echo array_sum(array_column($categories, 'book_count')); ?>
                                </span>
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="javascript:void(0)" onclick="loadCategory('<?php echo $cat['id']; ?>')" 
                                   id="cat-link-<?php echo $cat['id']; ?>"
                                   class="cat-link flex justify-between items-center px-4 py-3 rounded-xl text-sm font-medium transition duration-300 <?php echo $category_id == $cat['id'] ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50 hover:text-gold-600'; ?>">
                                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                    <span class="<?php echo $category_id == $cat['id'] ? 'bg-gold-500 text-slate-900' : 'bg-slate-100 text-slate-500'; ?> py-0.5 px-2.5 rounded-full text-xs font-bold">
                                        <?php echo $cat['book_count']; ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <main class="flex-grow" id="results-container">
                <?php renderBooksContent($books, $current_category_name); ?>
            </main>
        </div>
    </div>
</div>

<script>
let currentCat = '<?php echo $category_id; ?>';
let currentSearch = '<?php echo $search; ?>';

function loadData() {
    const container = document.getElementById('results-container');
    container.style.opacity = '0.5';
    container.style.transform = 'scale(0.99)';
    
    let url = `search.php?ajax=1`;
    if (currentCat) url += `&cat=${currentCat}`;
    if (currentSearch) url += `&q=${encodeURIComponent(currentSearch)}`;

    fetch(url)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            container.style.transform = 'scale(1)';
            container.style.transition = 'all 0.3s ease-out';
            
            let newUrl = 'search.php';
            const params = [];
            if (currentCat) params.push(`cat=${currentCat}`);
            if (currentSearch) params.push(`q=${encodeURIComponent(currentSearch)}`);
            if (params.length > 0) newUrl += '?' + params.join('&');
            
            window.history.pushState({cat: currentCat, q: currentSearch}, '', newUrl);
            updateSidebarActive();
        })
        .catch(err => console.error('Error:', err));
}

function loadCategory(catId) {
    currentCat = catId;
    loadData();
}

function handleSearch(e) {
    e.preventDefault();
    currentSearch = document.getElementById('searchInput').value;
    loadData();
}

function updateSidebarActive() {
    document.querySelectorAll('.cat-link').forEach(el => {
        el.className = 'cat-link flex justify-between items-center px-4 py-3 rounded-xl text-sm font-medium transition duration-300 text-slate-600 hover:bg-slate-50 hover:text-gold-600';
        el.querySelector('span:last-child').className = 'bg-slate-100 text-slate-500 py-0.5 px-2.5 rounded-full text-xs font-bold';
    });

    let activeId = currentCat ? `cat-link-${currentCat}` : 'cat-link-all';
    const activeEl = document.getElementById(activeId);
    if (activeEl) {
        activeEl.className = 'cat-link flex justify-between items-center px-4 py-3 rounded-xl text-sm font-medium transition duration-300 bg-slate-900 text-white shadow-md';
        activeEl.querySelector('span:last-child').className = 'bg-gold-500 text-slate-900 py-0.5 px-2.5 rounded-full text-xs font-bold';
    }
}

window.onpopstate = function(event) {
    if (event.state) {
        currentCat = event.state.cat || '';
        currentSearch = event.state.q || '';
        document.getElementById('searchInput').value = currentSearch;
        window.location.reload();
    }
};
</script>

<?php require_once 'includes/footer.php'; ?>