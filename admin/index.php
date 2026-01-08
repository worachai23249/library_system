<?php
// 1. ส่วนจัดการ API (ทำงานเมื่อมีการเรียกผ่าน AJAX)
if (isset($_GET['action']) && $_GET['action'] == 'fetch_chart') {
    require_once '../config/db.php';
    session_start();
    
    // ตรวจสอบสิทธิ์ Admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403); exit;
    }
    
    date_default_timezone_set('Asia/Bangkok');
    header('Content-Type: application/json');

    $filter = $_GET['filter'] ?? 'monthly';
    $rent_data = [];
    $sale_data = [];
    $labels = [];

    // กำหนดค่าตามตัวเลือก
    if ($filter == 'daily') {
        $dateFormatSQL = '%Y-%m-%d';
        $dateFormatPHP = 'd M';
        $interval = '30 DAY';
        $loop_count = 29;
        $step = 'days';
        $title = "รายได้ย้อนหลัง 30 วัน";
    } elseif ($filter == 'yearly') {
        $dateFormatSQL = '%Y';
        $dateFormatPHP = 'Y';
        $interval = '5 YEAR';
        $loop_count = 4;
        $step = 'years';
        $title = "รายได้ย้อนหลัง 5 ปี";
    } else { // monthly
        $dateFormatSQL = '%Y-%m';
        $dateFormatPHP = 'M Y';
        $interval = '12 MONTH';
        $loop_count = 11;
        $step = 'months';
        $title = "รายได้ย้อนหลัง 12 เดือน";
    }

    // สร้างโครงข้อมูล (Set 0 ไว้ก่อน)
    for ($i = $loop_count; $i >= 0; $i--) {
        $time = strtotime("-$i $step");
        // Key สำหรับแมพข้อมูล
        if ($filter == 'daily') $key = date('Y-m-d', $time);
        elseif ($filter == 'yearly') $key = date('Y', $time);
        else $key = date('Y-m', $time);
        
        $labels[] = date($dateFormatPHP, $time);
        $rent_data[$key] = 0;
        $sale_data[$key] = 0;
    }

    // Query 1: Online Orders
    $sql_online = "SELECT DATE_FORMAT(o.created_at, '$dateFormatSQL') as t_period, oi.type, SUM(oi.price * oi.qty) as total
                   FROM order_items oi JOIN orders o ON oi.order_id = o.id
                   WHERE o.status IN ('paid', 'shipped', 'completed') AND o.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                   GROUP BY t_period, oi.type";
    $stmt = $pdo->query($sql_online);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($rent_data[$row['t_period']]) && $row['type'] == 'rent') $rent_data[$row['t_period']] += (float)$row['total'];
        if (isset($sale_data[$row['t_period']]) && $row['type'] == 'sale') $sale_data[$row['t_period']] += (float)$row['total'];
    }

    // Query 2: Walk-in Transactions (แก้ชื่อฟิลด์ราคาให้ถูกต้องตาม DB: rent_price)
    $sql_walkin = "SELECT DATE_FORMAT(t.borrow_date, '$dateFormatSQL') as t_period, t.type,
                   SUM(CASE WHEN t.type = 'sale' THEN b.sell_price WHEN t.type = 'rent' THEN b.rent_price ELSE 0 END) as total
                   FROM transactions t JOIN books b ON t.book_id = b.id
                   WHERE t.borrow_date >= DATE_SUB(NOW(), INTERVAL $interval)
                   GROUP BY t_period, t.type";
    $stmt = $pdo->query($sql_walkin);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($rent_data[$row['t_period']]) && $row['type'] == 'rent') $rent_data[$row['t_period']] += (float)$row['total'];
        if (isset($sale_data[$row['t_period']]) && $row['type'] == 'sale') $sale_data[$row['t_period']] += (float)$row['total'];
    }

    echo json_encode([
        'labels' => array_values($labels),
        'rent' => array_values($rent_data),
        'sale' => array_values($sale_data),
        'title' => $title
    ]);
    exit;
}

// 2. ส่วนหน้าเว็บปกติ (HTML)
require_once 'header.php'; 
date_default_timezone_set('Asia/Bangkok');

// --- Statistics Queries ---
$books_count = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$members_count = $pdo->query("SELECT COUNT(*) FROM members WHERE role != 'admin'")->fetchColumn();
$orders_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
try {
    $borrowed_count = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'borrowed'")->fetchColumn();
    $overdue_count = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'borrowed' AND due_date < CURDATE()")->fetchColumn();
} catch (Exception $e) { $borrowed_count = 0; $overdue_count = 0; }

// --- Charts Data (Initial Load) ---
$months = []; $rent_init = []; $sale_init = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[$m] = date('M Y', strtotime("-$i months"));
    $rent_init[$m] = 0; $sale_init[$m] = 0;
}
$s1 = $pdo->query("SELECT DATE_FORMAT(o.created_at, '%Y-%m') as m, oi.type, SUM(oi.price*oi.qty) as t FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE o.status IN ('paid','shipped','completed') AND o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY m, oi.type");
while($r=$s1->fetch()){ if(isset($rent_init[$r['m']]) && $r['type']=='rent') $rent_init[$r['m']]+=$r['t']; if(isset($sale_init[$r['m']]) && $r['type']=='sale') $sale_init[$r['m']]+=$r['t']; }
// แก้ไข rent_price ตามชื่อ DB
$s2 = $pdo->query("SELECT DATE_FORMAT(t.borrow_date, '%Y-%m') as m, t.type, SUM(CASE WHEN t.type='sale' THEN b.sell_price ELSE b.rent_price END) as t FROM transactions t JOIN books b ON t.book_id=b.id WHERE t.borrow_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY m, t.type");
while($r=$s2->fetch()){ if(isset($rent_init[$r['m']]) && $r['type']=='rent') $rent_init[$r['m']]+=$r['t']; if(isset($sale_init[$r['m']]) && $r['type']=='sale') $sale_init[$r['m']]+=$r['t']; }

// คำนวณค่าแกน Y สูงสุด (Max) เพื่อให้กราฟอยู่กึ่งกลาง
$max_val_init = max(array_merge(array_values($rent_init), array_values($sale_init)));
$y_max_init = ($max_val_init > 0) ? $max_val_init * 2 : 100;

$cat_stats = $pdo->query("SELECT c.name, COUNT(b.id) as count FROM categories c LEFT JOIN books b ON c.id=b.category_id GROUP BY c.id")->fetchAll(PDO::FETCH_ASSOC);
$cat_labels = []; $cat_data = []; $colors = ['#0f172a','#d97706','#64748b','#ef4444','#cbd5e1','#fbbf24','#334155'];
foreach($cat_stats as $r){ $cat_labels[]=$r['name']; $cat_data[]=$r['count']; }

$status_stats = $pdo->query("SELECT status, COUNT(*) as count FROM books GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$s_av = $status_stats['available']??0; $s_br = $status_stats['borrowed']??0; $s_ot = ($status_stats['lost']??0)+($status_stats['repair']??0)+($status_stats['sold']??0);
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4 border-b border-slate-200 pb-4">
    <div>
        <h1 class="text-3xl font-serif font-bold text-navy-900">Dashboard</h1>
        <p class="text-slate-500 mt-1 font-light">ภาพรวมสถานะระบบห้องสมุดและสถิติการใช้งาน</p>
    </div>
    <div class="text-sm bg-white px-4 py-2 rounded-full shadow-sm border border-slate-200 text-slate-500 flex items-center gap-2">
        <svg class="w-4 h-4 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        <?php echo date('d F Y | H:i'); ?>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">หนังสือทั้งหมด</p>
                <h2 class="text-3xl font-serif font-bold text-navy-900 mt-2"><?php echo number_format($books_count); ?></h2>
            </div>
            <div class="w-12 h-12 rounded-xl bg-navy-900 text-gold-400 flex items-center justify-center text-xl shadow-md group-hover:scale-110 transition">📚</div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-50 text-xs text-slate-400">สถานะคลังหนังสือปัจจุบัน</div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">สมาชิก</p>
                <h2 class="text-3xl font-serif font-bold text-navy-900 mt-2"><?php echo number_format($members_count); ?></h2>
            </div>
            <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:border-gold-500 transition">👥</div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-50 text-xs text-slate-400">ผู้ใช้งานที่ลงทะเบียนแล้ว</div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition group relative overflow-hidden">
        <div class="absolute right-0 top-0 w-20 h-20 bg-gold-100 rounded-bl-full -mr-10 -mt-10 opacity-50"></div>
        <div class="flex justify-between items-start relative z-10">
            <div>
                <p class="text-xs text-gold-600 uppercase font-bold tracking-wider">รอตรวจสอบ</p>
                <h2 class="text-3xl font-serif font-bold text-gold-600 mt-2"><?php echo number_format($orders_count); ?></h2>
                <a href="orders.php" class="text-xs underline text-slate-400 hover:text-gold-600 mt-1 inline-block">จัดการทันที</a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gold-500 text-white flex items-center justify-center text-xl shadow-md group-hover:rotate-12 transition">📦</div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-red-100 hover:shadow-md transition group relative">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs text-red-500 uppercase font-bold tracking-wider">เกินกำหนดส่ง</p>
                <h2 class="text-3xl font-serif font-bold text-red-600 mt-2"><?php echo number_format($overdue_count); ?></h2>
                <a href="report_overdue.php" class="text-xs underline text-red-400 hover:text-red-600 mt-1 inline-block">ติดตามทวงคืน</a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl border border-red-100 animate-pulse">⚠️</div>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-6 relative">
    <div id="chartLoading" class="hidden absolute inset-0 bg-white/80 z-10 flex items-center justify-center rounded-2xl">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-slate-200 border-t-gold-500"></div>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h3 class="font-serif font-bold text-navy-900 flex items-center gap-2 text-lg">
                <span class="w-1.5 h-6 bg-gold-500 rounded-full"></span>
                <span id="chartTitle">รายได้ย้อนหลัง 12 เดือน</span>
            </h3>
            <div class="flex gap-4 text-xs font-bold text-slate-600 mt-2">
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gold-500"></span> ยอดเช่า</div>
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> ยอดซื้อขาด</div>
            </div>
        </div>
        
        <div class="flex items-center bg-slate-100 p-1 rounded-lg">
            <button onclick="updateChart('daily')" id="btn-daily" class="px-4 py-1.5 rounded-md text-xs font-bold transition text-slate-500 hover:bg-white hover:shadow-sm">รายวัน</button>
            <button onclick="updateChart('monthly')" id="btn-monthly" class="px-4 py-1.5 rounded-md text-xs font-bold transition bg-navy-900 text-white shadow-md">รายเดือน</button>
            <button onclick="updateChart('yearly')" id="btn-yearly" class="px-4 py-1.5 rounded-md text-xs font-bold transition text-slate-500 hover:bg-white hover:shadow-sm">รายปี</button>
        </div>
    </div>

    <div class="relative h-72 w-full">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-serif font-bold text-navy-900 mb-6 flex items-center gap-2 text-lg">
            <span class="w-1 h-6 bg-navy-800 rounded-full"></span> สัดส่วนหนังสือตามหมวดหมู่
        </h3>
        <div class="relative h-64"><canvas id="catChart"></canvas></div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-serif font-bold text-navy-900 mb-6 flex items-center gap-2 text-lg">
             <span class="w-1 h-6 bg-slate-500 rounded-full"></span> สถานะหนังสือในคลัง
        </h3>
        <div class="relative h-64 flex justify-center"><canvas id="statusChart"></canvas></div>
    </div>
</div>

<div class="space-y-10 pb-12">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-serif font-bold text-navy-900 flex items-center gap-2">
            <span class="text-gold-500">📂</span> รายการหนังสือล่าสุด
        </h2>
        <a href="book_form.php" class="bg-navy-900 text-white px-5 py-2.5 rounded-full shadow-lg hover:bg-gold-500 hover:text-navy-900 transition duration-300 text-sm font-bold flex items-center gap-2 transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> เพิ่มหนังสือใหม่
        </a>
    </div>
    <?php foreach ($categories as $cat): 
        $stmt = $pdo->prepare("SELECT * FROM books WHERE category_id = ? ORDER BY id DESC LIMIT 6");
        $stmt->execute([$cat['id']]); $books = $stmt->fetchAll(); if (count($books) == 0) continue;
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-navy-800 flex items-center gap-2 text-lg"><?php echo htmlspecialchars($cat['name']); ?></h3>
            <a href="books.php" class="text-xs font-bold text-gold-600 hover:text-navy-900 transition">ดูทั้งหมด →</a>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <?php foreach ($books as $book): $bookJson = htmlspecialchars(json_encode($book, JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
            <div onclick="showBookDetails(<?php echo $bookJson; ?>)" class="group cursor-pointer flex flex-col items-center text-center">
                <div class="w-full aspect-[2/3] bg-slate-200 rounded-lg overflow-hidden mb-3 shadow-md relative group-hover:shadow-xl transition duration-300">
                    <?php if(!empty($book['cover_image'])): ?><img src="../uploads/covers/<?php echo $book['cover_image']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500"><?php else: ?><div class="w-full h-full flex items-center justify-center text-slate-400 text-xs bg-slate-100">No Image</div><?php endif; ?>
                    <div class="absolute top-2 right-2 flex flex-col items-end gap-1"><span class="text-[10px] font-bold px-2 py-0.5 rounded shadow-sm backdrop-blur-md <?php echo $book['stock_rent']>0?'bg-emerald-500/80 text-white':'bg-slate-800/80 text-white'; ?>"><?php echo $book['stock_rent']; ?> เล่ม</span></div>
                </div>
                <h4 class="text-sm font-bold text-navy-900 line-clamp-2 leading-tight group-hover:text-gold-600 transition"><?php echo htmlspecialchars($book['title']); ?></h4>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
// --- 1. Revenue Chart ---
const ctxRev = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctxRev, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_values($months)); ?>,
        datasets: [
            { label: 'ยอดเช่า (Rent)', data: <?php echo json_encode(array_values($rent_init)); ?>, borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', borderWidth: 3, tension: 0.4, pointBackgroundColor: '#fff', pointBorderColor: '#f59e0b', pointRadius: 5, fill: true },
            { label: 'ยอดซื้อขาด (Sale)', data: <?php echo json_encode(array_values($sale_init)); ?>, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 3, tension: 0.4, pointBackgroundColor: '#fff', pointBorderColor: '#10b981', pointRadius: 5, fill: true }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', titleFont: {family:'Kanit'}, bodyFont: {family:'Kanit'}, padding: 10, callbacks: { label: function(c){ let l=c.dataset.label||''; if(l)l+=': '; if(c.parsed.y!==null)l+=new Intl.NumberFormat('th-TH',{style:'currency',currency:'THB'}).format(c.parsed.y); return l; } } } },
        scales: {
            y: { 
                beginAtZero: true, 
                grid: {color:'#f1f5f9', borderDash:[5,5]}, 
                ticks: {font:{family:'Kanit'}, color:'#64748b'},
                // ✅ เพิ่มบรรทัดนี้: กำหนด Max แกน Y ให้เส้นอยู่กลาง
                max: <?php echo $y_max_init; ?>
            },
            x: { 
                grid: {display:false}, 
                ticks: {font:{family:'Kanit'}, color:'#64748b'},
                // ✅ เพิ่มบรรทัดนี้: offset: true ทำให้กราฟไม่ชิดขอบซ้ายขวา
                offset: true 
            }
        }
    }
});

// 🔥 Function: Update Chart
function updateChart(filter) {
    document.getElementById('chartLoading').classList.remove('hidden');
    
    ['daily', 'monthly', 'yearly'].forEach(f => {
        const btn = document.getElementById('btn-' + f);
        btn.className = (f === filter) 
            ? "px-4 py-1.5 rounded-md text-xs font-bold transition bg-navy-900 text-white shadow-md"
            : "px-4 py-1.5 rounded-md text-xs font-bold transition text-slate-500 hover:bg-white hover:shadow-sm";
    });

    fetch(`index.php?action=fetch_chart&filter=${filter}`)
        .then(response => response.json())
        .then(data => {
            revenueChart.data.labels = data.labels;
            revenueChart.data.datasets[0].data = data.rent;
            revenueChart.data.datasets[1].data = data.sale;

            // ✅ คำนวณค่าสูงสุดใหม่ เพื่อปรับแกน Y ให้อยู่กึ่งกลางเสมอ
            const allValues = [...data.rent, ...data.sale];
            const maxVal = Math.max(...allValues);
            revenueChart.options.scales.y.max = (maxVal > 0) ? maxVal * 2 : 100;
            
            revenueChart.update();

            document.getElementById('chartTitle').innerText = data.title;
            setTimeout(() => { document.getElementById('chartLoading').classList.add('hidden'); }, 300);
        })
        .catch(err => { console.error(err); document.getElementById('chartLoading').classList.add('hidden'); });
}

// --- Other Charts ---
const ctxCat = document.getElementById('catChart').getContext('2d');
new Chart(ctxCat, { type: 'doughnut', data: { labels: <?php echo json_encode($cat_labels); ?>, datasets: [{ data: <?php echo json_encode($cat_data); ?>, backgroundColor: <?php echo json_encode($colors); ?>, borderWidth: 0, hoverOffset: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: {font:{family:'Kanit'}, color:'#64748b'} } }, cutout: '70%' } });

const ctxStatus = document.getElementById('statusChart').getContext('2d');
new Chart(ctxStatus, { type: 'bar', data: { labels: ['พร้อมบริการ','ถูกยืม','อื่นๆ'], datasets: [{ label: 'จำนวนเล่ม', data: [<?php echo $s_av; ?>,<?php echo $s_br; ?>,<?php echo $s_ot; ?>], backgroundColor: ['#10b981','#ef4444','#64748b'], borderRadius: 4, barThickness: 40 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: {color:'#f1f5f9'}, ticks: {font:{family:'Kanit'}, color:'#64748b'} }, x: { grid: {display:false}, ticks: {font:{family:'Kanit'}, color:'#64748b'} } }, plugins: { legend: {display:false} } } });

function showBookDetails(b) {
    let img = b.cover_image ? `../uploads/covers/${b.cover_image}` : 'https://via.placeholder.com/150?text=No+Image';
    Swal.fire({ title: `<h3 class="font-serif text-navy-900">${b.title}</h3>`, html: `<p class="text-slate-600">ผู้แต่ง: ${b.author}</p>`, imageUrl: img, imageHeight: 250, showCancelButton: true, confirmButtonText: 'แก้ไขข้อมูล', cancelButtonText: 'ปิด', confirmButtonColor: '#d97706', cancelButtonColor: '#64748b', customClass: { popup: 'rounded-2xl', image: 'rounded-lg shadow-md' } }).then((r) => { if(r.isConfirmed) window.location.href=`book_form.php?id=${b.id}`; });
}
</script>

<?php require_once 'footer.php'; ?>