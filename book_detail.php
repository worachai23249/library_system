<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT books.*, categories.name as category_name FROM books LEFT JOIN categories ON books.category_id = categories.id WHERE books.id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) { echo "<div class='container mx-auto p-10 text-center text-slate-500'>ไม่พบข้อมูลหนังสือ</div>"; require_once 'includes/footer.php'; exit; }

// ตรวจสอบสถานะ User และ KYC
$is_logged_in = isset($_SESSION['user_id']) ? 'true' : 'false';
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') ? 'true' : 'false';
$verification_status = 'none';

if (isset($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare("SELECT verification_status FROM members WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch();
    if ($user) {
        $verification_status = $user['verification_status'];
    }
}

// Default ราคาเช่า
$base_rent = $book['rent_price'] > 0 ? $book['rent_price'] : 10;
$p7  = $book['rent_price_7'] > 0 ? $book['rent_price_7'] : $base_rent;
$p15 = $book['rent_price_15'] > 0 ? $book['rent_price_15'] : $base_rent * 2;
$p30 = $book['rent_price_30'] > 0 ? $book['rent_price_30'] : $base_rent * 4;
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="bg-slate-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <nav class="flex text-sm text-slate-500 mb-8 bg-white px-6 py-3 rounded-full shadow-sm border border-slate-100 w-fit">
            <a href="index.php" class="hover:text-gold-600 transition">หน้าแรก</a>
            <span class="mx-3 text-slate-300">/</span>
            <a href="search.php?cat=<?php echo $book['category_id']; ?>" class="hover:text-gold-600 transition"><?php echo htmlspecialchars($book['category_name']); ?></a>
            <span class="mx-3 text-slate-300">/</span>
            <span class="text-slate-800 font-medium truncate max-w-xs"><?php echo htmlspecialchars($book['title']); ?></span>
        </nav>

        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-12">
                
                <div class="md:col-span-5 bg-slate-50 border-r border-slate-100 p-8 pt-12 flex flex-col justify-start">
                    <div class="w-full relative group perspective-1000">
                        <?php if($book['cover_image']): ?>
                            <img id="main-image" src="uploads/covers/<?php echo $book['cover_image']; ?>" 
                                 class="w-full h-auto object-cover block rounded-xl shadow-2xl border border-slate-200 transform transition duration-500 hover:scale-[1.02] hover:rotate-1">
                        <?php else: ?>
                            <div id="main-image-placeholder" class="w-full aspect-[2/3] bg-slate-200 flex items-center justify-center text-slate-400 text-xl font-serif p-4 text-center rounded-xl shadow-inner">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!empty($book['cover_image']) || !empty($book['back_cover_image'])): ?>
                    <div class="flex gap-4 justify-center items-center mt-8">
                        <?php if(!empty($book['cover_image'])): ?>
                            <div class="cursor-pointer border-2 border-transparent hover:border-gold-500 rounded-lg p-1 transition" onclick="changeImage('uploads/covers/<?php echo $book['cover_image']; ?>')">
                                <img src="uploads/covers/<?php echo $book['cover_image']; ?>" class="w-16 h-24 object-cover rounded shadow-sm">
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($book['back_cover_image'])): ?>
                            <div class="cursor-pointer border-2 border-transparent hover:border-gold-500 rounded-lg p-1 transition" onclick="changeImage('uploads/covers/<?php echo $book['back_cover_image']; ?>')">
                                <img src="uploads/covers/<?php echo $book['back_cover_image']; ?>" class="w-16 h-24 object-cover rounded shadow-sm">
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="md:col-span-7 p-8 md:p-12 flex flex-col">
                    <h1 class="text-2xl md:text-3xl font-serif font-bold text-slate-900 mb-4 leading-snug break-words tracking-tight">
                        <?php 
                        $title_html = htmlspecialchars($book['title']);
                        // เช็คว่ามีวงเล็บหรือไม่ ถ้ามีให้ตัดบรรทัดและลดขนาดตัวอักษร
                        if (strpos($title_html, '(') !== false) {
                            $title_html = str_replace('(', '<br><span class="text-lg md:text-xl text-slate-500 font-medium font-sans">(' , $title_html);
                            $title_html = str_replace(')', ')</span>', $title_html);
                        }
                        echo $title_html; 
                        ?>
                    </h1>
                    
                    <p class="text-lg text-slate-500 mb-8 font-light italic">โดย 
                        <a href="search.php?q=<?php echo urlencode($book['author']); ?>" class="text-gold-600 hover:underline font-medium">
                            <?php echo htmlspecialchars($book['author']); ?>
                        </a>
                    </p>

                    <div class="flex bg-slate-100 p-1.5 rounded-xl mb-8 self-start shadow-inner">
                        <button onclick="switchMode('rent')" id="tab-rent" class="px-8 py-2.5 rounded-lg text-sm font-bold transition shadow-md bg-white text-slate-900 ring-1 ring-black/5">
                            เช่าหนังสือ
                        </button>
                        <button onclick="switchMode('buy')" id="tab-buy" class="px-8 py-2.5 rounded-lg text-sm font-bold text-slate-500 hover:text-slate-700 transition">
                            ซื้อเก็บ
                        </button>
                    </div>

                    <div id="panel-rent" class="block animate-fade-in">
                        <?php if($book['stock_rent'] > 0): ?>
                            <p class="text-sm font-bold text-slate-700 mb-4 uppercase tracking-wide">เลือกระยะเวลาเช่า</p>
                            <div class="grid grid-cols-3 gap-4 mb-8">
                                <label class="cursor-pointer group">
                                    <input type="radio" name="rent_days" value="7" checked class="peer hidden">
                                    <div class="border border-slate-200 rounded-2xl p-4 text-center peer-checked:border-gold-500 peer-checked:bg-gold-50 peer-checked:shadow-md transition group-hover:border-gold-300">
                                        <div class="text-xs text-slate-500 mb-1">7 วัน</div>
                                        <div class="font-bold text-slate-800 text-lg"><?php echo number_format($p7); ?> ฿</div>
                                    </div>
                                </label>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="rent_days" value="15" class="peer hidden">
                                    <div class="border border-slate-200 rounded-2xl p-4 text-center peer-checked:border-gold-500 peer-checked:bg-gold-50 peer-checked:shadow-md transition group-hover:border-gold-300">
                                        <div class="text-xs text-slate-500 mb-1">15 วัน</div>
                                        <div class="font-bold text-slate-800 text-lg"><?php echo number_format($p15); ?> ฿</div>
                                    </div>
                                </label>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="rent_days" value="30" class="peer hidden">
                                    <div class="border border-slate-200 rounded-2xl p-4 text-center peer-checked:border-gold-500 peer-checked:bg-gold-50 peer-checked:shadow-md transition group-hover:border-gold-300">
                                        <div class="text-xs text-slate-500 mb-1">30 วัน</div>
                                        <div class="font-bold text-slate-800 text-lg"><?php echo number_format($p30); ?> ฿</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="flex gap-4">
                                <button onclick="handleAddToCart('rent', false)" class="flex-1 border border-slate-300 text-slate-700 py-4 rounded-xl font-bold hover:border-slate-800 hover:text-slate-900 transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    เพิ่มลงตะกร้า
                                </button>
                                <button onclick="handleAddToCart('rent', true)" class="flex-1 bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-gold-500 hover:text-slate-900 transition shadow-lg flex items-center justify-center gap-2">
                                    เช่าทันที
                                </button>
                            </div>
                            <p class="mt-4 text-xs text-slate-400 text-center">มีสินค้าเช่า <?php echo $book['stock_rent']; ?> เล่ม</p>

                        <?php else: ?>
                            <div class="bg-red-50 text-red-600 p-6 rounded-xl text-center font-bold border border-red-100">
                                ❌ สินค้าหมดชั่วคราว
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="panel-buy" class="hidden animate-fade-in">
                        <?php if($book['stock_sale'] > 0): ?>
                            <div class="mb-8 p-6 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center">
                                <div>
                                    <p class="text-slate-500 text-xs uppercase font-bold tracking-wide">ราคาขายสุทธิ</p>
                                    <p class="text-4xl font-serif font-bold text-slate-900 mt-1"><?php echo number_format($book['sell_price']); ?> <span class="text-base font-sans text-slate-400 font-normal">บาท</span></p>
                                </div>
                                <div class="flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                                    <button onclick="updateQty(-1)" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:bg-slate-100 transition">-</button>
                                    <input type="number" id="buy-qty" value="1" min="1" max="<?php echo $book['stock_sale']; ?>" class="w-12 h-10 text-center border-l border-r border-slate-100 focus:outline-none text-slate-800 font-bold bg-transparent" readonly>
                                    <button onclick="updateQty(1)" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:bg-slate-100 transition">+</button>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <button onclick="handleAddToCart('buy', false)" class="flex-1 border border-slate-300 text-slate-700 py-4 rounded-xl font-bold hover:border-slate-800 hover:text-slate-900 transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    เพิ่มลงตะกร้า
                                </button>
                                <button onclick="handleAddToCart('buy', true)" class="flex-1 bg-gold-500 text-slate-900 py-4 rounded-xl font-bold hover:bg-gold-400 transition shadow-lg shadow-gold-500/30 flex items-center justify-center gap-2">
                                    ซื้อทันที
                                </button>
                            </div>
                            <p class="mt-4 text-xs text-slate-400 text-center">มีสินค้าขาย <?php echo $book['stock_sale']; ?> เล่ม</p>
                        <?php else: ?>
                            <div class="bg-red-50 text-red-600 p-6 rounded-xl text-center font-bold border border-red-100">
                                ❌ สินค้าหมดชั่วคราว
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-10 pt-8 border-t border-slate-100">
                        <h3 class="font-serif font-bold text-xl text-slate-800 mb-4">เรื่องย่อ</h3>
                        <div class="text-slate-600 leading-relaxed text-sm font-light space-y-4">
                            <?php echo !empty($book['description']) ? nl2br(htmlspecialchars($book['description'])) : "- ไม่มีรายละเอียด -"; ?>
                        </div>
                        <div class="mt-6 flex items-center gap-2 text-xs text-slate-400 font-mono">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            ISBN: <?php echo htmlspecialchars($book['isbn']); ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// รับค่าตัวแปรจาก PHP
const isLoggedIn = <?php echo $is_logged_in; ?>;
const isAdmin = <?php echo $is_admin; ?>;
const verificationStatus = '<?php echo $verification_status; ?>';
const maxStockSale = <?php echo $book['stock_sale'] ?? 0; ?>;

function switchMode(mode) {
    const tabRent = document.getElementById('tab-rent');
    const tabBuy = document.getElementById('tab-buy');
    const panelRent = document.getElementById('panel-rent');
    const panelBuy = document.getElementById('panel-buy');

    if (mode === 'rent') {
        tabRent.classList.add('bg-white', 'text-slate-900', 'shadow-md');
        tabRent.classList.remove('text-slate-500');
        tabBuy.classList.remove('bg-white', 'text-slate-900', 'shadow-md');
        tabBuy.classList.add('text-slate-500');
        panelRent.classList.remove('hidden');
        panelBuy.classList.add('hidden');
    } else {
        tabBuy.classList.add('bg-white', 'text-slate-900', 'shadow-md');
        tabBuy.classList.remove('text-slate-500');
        tabRent.classList.remove('bg-white', 'text-slate-900', 'shadow-md');
        tabRent.classList.add('text-slate-500');
        panelRent.classList.add('hidden');
        panelBuy.classList.remove('hidden');
    }
}

function updateQty(change) {
    const qtyInput = document.getElementById('buy-qty');
    let currentQty = parseInt(qtyInput.value);
    let newQty = currentQty + change;
    if (newQty < 1) newQty = 1;
    if (newQty > maxStockSale) {
        newQty = maxStockSale;
        Swal.fire({icon: 'warning', title: 'แจ้งเตือน', text: 'สินค้ามีจำกัดเพียง ' + maxStockSale + ' ชิ้น', confirmButtonColor: '#0f172a'});
    }
    qtyInput.value = newQty;
}

function flyToCart() {
    const img = document.getElementById('main-image') || document.getElementById('main-image-placeholder');
    const cartIcon = document.querySelector('a[href="cart.php"]');

    if (img && cartIcon) {
        const imgClone = img.cloneNode(true);
        const imgRect = img.getBoundingClientRect();
        const cartRect = cartIcon.getBoundingClientRect();

        imgClone.style.position = 'fixed';
        imgClone.style.top = imgRect.top + 'px';
        imgClone.style.left = imgRect.left + 'px';
        imgClone.style.width = imgRect.width + 'px';
        imgClone.style.height = imgRect.height + 'px';
        imgClone.style.opacity = '0.9';
        imgClone.style.zIndex = '9999';
        imgClone.style.transition = 'all 0.8s cubic-bezier(0.2, 1, 0.3, 1)';
        imgClone.style.borderRadius = '1rem';
        imgClone.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1)';
        
        document.body.appendChild(imgClone);

        setTimeout(() => {
            imgClone.style.top = (cartRect.top + 10) + 'px';
            imgClone.style.left = (cartRect.left + 10) + 'px';
            imgClone.style.width = '20px';
            imgClone.style.height = '30px';
            imgClone.style.opacity = '0';
            imgClone.style.transform = 'rotate(360deg) scale(0.1)';
        }, 50);

        setTimeout(() => { imgClone.remove(); }, 850);
    }
}

function handleAddToCart(type, isBuyNow) {
    // 1. เช็คล็อกอิน
    if (!isLoggedIn) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณาเข้าสู่ระบบ',
            text: 'ท่านต้องเข้าสู่ระบบเพื่อใช้งานตะกร้าสินค้า',
            confirmButtonText: 'เข้าสู่ระบบ',
            confirmButtonColor: '#0f172a'
        }).then(() => { window.location.href = 'login.php'; });
        return;
    }

    // 2. เช็คว่าเป็น Admin หรือไม่
    if (isAdmin) { window.location.href = 'admin/index.php'; return; }

    // 3. เช็คสถานะ KYC
    if (verificationStatus !== 'verified') {
        let msg = 'ท่านต้องยืนยันตัวตน (KYC) ก่อนจึงจะสามารถเช่าหรือซื้อหนังสือได้';
        let btnText = 'ยืนยันตัวตนทันที';
        
        if(verificationStatus === 'pending') {
            msg = 'เอกสารของท่านกำลังอยู่ระหว่างการตรวจสอบ กรุณารอเจ้าหน้าที่อนุมัติ';
            btnText = 'ดูสถานะ';
        } else if (verificationStatus === 'rejected') {
            msg = 'การยืนยันตัวตนไม่ผ่าน กรุณาส่งเอกสารใหม่';
            btnText = 'ส่งเอกสารใหม่';
        }

        Swal.fire({
            icon: 'warning',
            title: 'กรุณายืนยันตัวตน',
            text: msg,
            confirmButtonText: btnText,
            confirmButtonColor: '#d97706',
            showCancelButton: true,
            cancelButtonText: 'ไว้ทีหลัง'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'verify.php';
            }
        });
        return;
    }

    // 4. ดำเนินการเพิ่มลงตะกร้า
    let rentDays = 7;
    let qty = 1;

    if (type === 'rent') {
        const selectedRadio = document.querySelector('input[name="rent_days"]:checked');
        if (selectedRadio) rentDays = selectedRadio.value;
    } else {
        qty = document.getElementById('buy-qty').value;
    }

    if (!isBuyNow) flyToCart();

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('book_id', <?php echo $book['id']; ?>);
    formData.append('type', type);
    formData.append('rent_days', rentDays);
    formData.append('qty', qty);

    fetch('cart_action.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (isBuyNow) {
                window.location.href = 'cart.php';
            } else {
                location.reload(); 
            }
        } else {
            Swal.fire({icon: 'error', title: 'ขออภัย', text: data.message, confirmButtonColor: '#ef4444'});
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    });
}

function changeImage(src) {
    const mainImg = document.getElementById('main-image');
    mainImg.style.opacity = '0.5';
    setTimeout(() => {
        mainImg.src = src;
        mainImg.style.opacity = '1';
    }, 200);
}
</script>

<?php require_once 'includes/footer.php'; ?>