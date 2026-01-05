<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (file_exists('config/db.php')) { require_once 'config/db.php'; require_once 'includes/header.php'; }
elseif (file_exists('../config/db.php')) { require_once '../config/db.php'; require_once '../includes/header.php'; }

if (!isset($_SESSION['user_id'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({icon: 'warning', title: 'กรุณาเข้าสู่ระบบ', confirmButtonText: 'เข้าสู่ระบบ', confirmButtonColor: '#0f172a'}).then((r) => { if(r.isConfirmed) window.location.href = 'login.php'; });
        });
    </script>";
    require_once 'includes/footer.php'; exit;
}

$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    if (!empty($ids)) {
        $sql = "SELECT id, title, author, cover_image, sell_price, rent_price, rent_price_7, rent_price_15, rent_price_30 FROM books WHERE id IN ($ids)";
        $stmt = $pdo->query($sql);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($books as $book) {
            $cart_data = $_SESSION['cart'][$book['id']];
            $book['cart_qty'] = $cart_data['qty'];
            $book['cart_type'] = $cart_data['type'];
            $rent_days = $cart_data['rent_days'] ?? 7; 
            $book['rent_days'] = $rent_days;

            if ($cart_data['type'] == 'buy') {
                $price = $book['sell_price'];
                $book['type_label'] = 'ซื้อขาด';
                $book['type_class'] = 'bg-emerald-100 text-emerald-800';
            } else {
                if ($rent_days == 30) $price = ($book['rent_price_30'] > 0) ? $book['rent_price_30'] : $book['rent_price'] * 4;
                elseif ($rent_days == 15) $price = ($book['rent_price_15'] > 0) ? $book['rent_price_15'] : $book['rent_price'] * 2;
                else $price = ($book['rent_price_7'] > 0) ? $book['rent_price_7'] : $book['rent_price'];
                
                $book['type_label'] = 'เช่า ' . $rent_days . ' วัน';
                $book['type_class'] = 'bg-blue-100 text-blue-800';
            }

            $book['calculated_price'] = $price;
            $book['item_total'] = $price * $cart_data['qty'];
            $total_price += $book['item_total'];
            $cart_items[] = $book;
        }
    }
}

$addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
$addrStmt->execute([$_SESSION['user_id']]);
$addresses = $addrStmt->fetchAll();
?>

<div class="bg-slate-50 min-h-screen pb-20 font-sans pt-10">
    <div class="container mx-auto px-4 max-w-6xl">
        <h1 class="text-3xl font-serif font-bold text-slate-800 mb-8 flex items-center gap-3">
            <span class="bg-slate-900 text-gold-500 p-2 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg></span>
            ตะกร้าสินค้า
        </h1>

        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-16 text-center border border-slate-200">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <p class="text-slate-500 mb-8 text-xl font-light">ตะกร้าของคุณว่างเปล่า</p>
                <a href="index.php" class="bg-slate-900 text-white px-10 py-4 rounded-full font-bold hover:bg-gold-500 hover:text-slate-900 transition shadow-lg">
                    เลือกดูหนังสือ
                </a>
            </div>
        <?php else: ?>

            <div class="flex flex-col lg:flex-row gap-8">
                <div class="flex-grow space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-5 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                            <input type="checkbox" id="selectAll" checked onclick="toggleAll(this)" class="w-5 h-5 rounded text-slate-900 border-slate-300 focus:ring-slate-900">
                            <label for="selectAll" class="font-bold text-slate-700 cursor-pointer select-none">เลือกทั้งหมด (<?php echo count($cart_items); ?> รายการ)</label>
                        </div>
                        <div class="p-6 space-y-6">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="flex gap-6 items-start relative group">
                                <div class="pt-10">
                                    <input type="checkbox" class="item-checkbox w-5 h-5 rounded text-slate-900 border-slate-300 focus:ring-slate-900" 
                                           value="<?php echo $item['id']; ?>" data-price="<?php echo $item['item_total']; ?>" checked onchange="calculateTotal()">
                                </div>

                                <div class="w-28 aspect-[2/3] flex-shrink-0 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 shadow-md">
                                    <?php if(!empty($item['cover_image'])): ?>
                                        <img src="uploads/covers/<?php echo $item['cover_image']; ?>" class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>

                                <div class="flex-grow min-w-0 py-2">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider <?php echo $item['type_class']; ?>">
                                                <?php echo $item['type_label']; ?>
                                            </span>
                                            <h3 class="font-serif font-bold text-slate-800 text-xl mt-2 truncate pr-8">
                                                <a href="book_detail.php?id=<?php echo $item['id']; ?>" class="hover:text-gold-600 transition"><?php echo htmlspecialchars($item['title']); ?></a>
                                            </h3>
                                            <p class="text-sm text-slate-500 font-light italic"><?php echo htmlspecialchars($item['author']); ?></p>
                                        </div>
                                        <button onclick="removeItem(<?php echo $item['id']; ?>)" class="text-slate-300 hover:text-red-500 transition p-2 hover:bg-red-50 rounded-full">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                    
                                    <div class="flex justify-between items-end mt-6">
                                        <div class="text-sm text-slate-500">
                                            จำนวน: <span class="font-bold text-slate-800"><?php echo $item['cart_qty']; ?></span>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold text-slate-900 text-2xl"><?php echo number_format($item['item_total']); ?> <span class="text-sm font-normal text-slate-400">฿</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border-b border-slate-100 last:hidden"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-5 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                            <span class="font-bold text-slate-700 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                ที่อยู่สำหรับจัดส่ง
                            </span>
                            <a href="profile_addresses.php" class="text-sm text-gold-600 hover:text-gold-700 font-bold underline decoration-2 underline-offset-4">+ จัดการที่อยู่</a>
                        </div>
                        <div class="p-6">
                            <?php if (count($addresses) > 0): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($addresses as $index => $addr): ?>
                                    <label class="cursor-pointer relative group">
                                        <input type="radio" name="address_id" value="<?php echo $addr['id']; ?>" class="peer sr-only" <?php echo ($index === 0) ? 'checked' : ''; ?>>
                                        <div class="p-5 rounded-xl border-2 border-slate-100 hover:border-gold-300 peer-checked:border-slate-900 peer-checked:bg-slate-50 transition h-full shadow-sm relative">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="font-bold text-slate-800 text-lg"><?php echo htmlspecialchars($addr['recipient_name']); ?></span>
                                            </div>
                                            <p class="text-sm text-slate-600 leading-relaxed font-light"><?php echo htmlspecialchars($addr['address_line']); ?></p>
                                            <p class="text-xs text-slate-400 mt-3 font-mono">📞 <?php echo htmlspecialchars($addr['phone']); ?></p>
                                            
                                            <div class="absolute top-4 right-4 text-slate-900 opacity-0 peer-checked:opacity-100 transition-opacity">
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            </div>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                                    <p class="text-slate-500 mb-3">คุณยังไม่มีที่อยู่จัดส่ง</p>
                                    <a href="profile_addresses.php" class="text-sm bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg font-bold hover:bg-slate-100 transition">เพิ่มที่อยู่ใหม่</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-96 flex-shrink-0">
                    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 sticky top-28">
                        <h3 class="font-serif font-bold text-slate-800 mb-6 text-xl">สรุปคำสั่งซื้อ</h3>
                        <div class="flex justify-between mb-4 text-sm text-slate-600">
                            <span>ยอดรวม (<span id="selectedCount"><?php echo count($cart_items); ?></span> ชิ้น)</span>
                            <span id="displayTotal" class="font-bold"><?php echo number_format($total_price); ?> ฿</span>
                        </div>
                        <div class="border-t border-dashed border-slate-200 pt-6 mb-8">
                            <div class="flex justify-between items-end">
                                <span class="font-bold text-slate-800 text-lg">ยอดสุทธิ</span>
                                <span class="font-bold text-4xl text-slate-900" id="netTotal"><?php echo number_format($total_price); ?> <span class="text-lg font-normal text-slate-400">฿</span></span>
                            </div>
                        </div>

                        <button type="button" onclick="checkout()" class="w-full bg-slate-900 text-gold-400 py-4 rounded-xl font-bold hover:bg-black hover:text-gold-300 transition shadow-lg transform active:scale-95 flex justify-center items-center gap-2 group">
                            <span>ดำเนินการชำระเงิน</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
function calculateTotal() {
    let total = 0;
    let count = 0;
    document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        total += parseFloat(cb.getAttribute('data-price'));
        count++;
    });
    const formatted = total.toLocaleString();
    document.getElementById('displayTotal').innerText = formatted + ' ฿';
    document.getElementById('netTotal').innerHTML = formatted + ' <span class="text-lg font-normal text-slate-400">฿</span>';
    document.getElementById('selectedCount').innerText = count;
}

function toggleAll(source) {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = source.checked);
    calculateTotal();
}

function checkout() {
    let selectedItems = [];
    document.querySelectorAll('.item-checkbox:checked').forEach(cb => selectedItems.push(cb.value));

    if (selectedItems.length === 0) {
        Swal.fire({icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการ', confirmButtonColor: '#0f172a'});
        return;
    }

    const selectedAddress = document.querySelector('input[name="address_id"]:checked');
    if (!selectedAddress) {
        Swal.fire({icon: 'warning', title: 'ยังไม่ได้เลือกที่อยู่', text: 'กรุณาเลือกที่อยู่จัดส่ง', confirmButtonColor: '#0f172a'});
        return;
    }

    const itemsStr = selectedItems.join(',');
    window.location.href = `checkout.php?address_id=${selectedAddress.value}&items=${itemsStr}`;
}

function removeItem(bookId) {
    Swal.fire({
        title: 'ลบรายการ?', text: 'สินค้าจะถูกนำออกจากตะกร้า', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'ลบเลย', cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('book_id', bookId);
            fetch('cart_action.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { 
                if(data.status === 'success') location.reload(); 
            });
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>