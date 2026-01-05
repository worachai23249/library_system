<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$chkUser = $pdo->prepare("SELECT verification_status FROM members WHERE id = ?");
$chkUser->execute([$_SESSION['user_id']]);
$status = $chkUser->fetchColumn();

if ($status !== 'verified') {
    echo "<script>Swal.fire({icon:'error', title:'ไม่สามารถทำรายการ', text:'กรุณายืนยันตัวตน (KYC) ก่อน', confirmButtonColor:'#0f172a'}).then(()=>{window.location.href='verify.php'});</script>";
    exit;
}

if (empty($_SESSION['cart'])) { header("Location: index.php"); exit; }

$selected_items_str = $_GET['items'] ?? '';
if (empty($selected_items_str)) { header("Location: cart.php"); exit; }

$selected_ids = explode(',', $selected_items_str);
$total_price = 0;
$checkout_list = [];
$ids_in_cart = implode(',', array_keys($_SESSION['cart']));

if ($ids_in_cart) {
    $stmt = $pdo->query("SELECT * FROM books WHERE id IN ($ids_in_cart)");
    while ($book = $stmt->fetch()) {
        if (in_array($book['id'], $selected_ids)) {
            $cartItem = $_SESSION['cart'][$book['id']];
            $rent_days = $cartItem['rent_days'] ?? 7; 
            
            if ($cartItem['type'] == 'buy') {
                $price = $book['sell_price'];
                $rent_txt = "";
            } else {
                if ($rent_days == 30) $price = ($book['rent_price_30'] > 0) ? $book['rent_price_30'] : $book['rent_price'] * 4;
                elseif ($rent_days == 15) $price = ($book['rent_price_15'] > 0) ? $book['rent_price_15'] : $book['rent_price'] * 2;
                else $price = ($book['rent_price_7'] > 0) ? $book['rent_price_7'] : $book['rent_price'];
                $rent_txt = "(เช่า $rent_days วัน)";
            }
            
            $book['checkout_qty'] = $cartItem['qty'];
            $book['checkout_price'] = $price;
            $book['checkout_total'] = $price * $cartItem['qty'];
            $book['rent_info'] = $rent_txt;
            
            $total_price += $book['checkout_total'];
            $checkout_list[] = $book;
        }
    }
}

if (empty($checkout_list)) { header("Location: cart.php"); exit; }

$address_id = $_GET['address_id'] ?? 0;
$addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ?");
$addrStmt->execute([$address_id]);
$addr = $addrStmt->fetch();
$promptpay_id = "0812345678"; 
?>

<div class="bg-slate-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto px-4 max-w-5xl">
        <h1 class="text-3xl font-serif font-bold text-slate-800 mb-8 flex items-center gap-3">
            <span class="bg-gold-500 text-slate-900 p-2 rounded-lg shadow-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
            ยืนยันการสั่งซื้อ
        </h1>

        <form action="save_order.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="checkout_items" value="<?php echo htmlspecialchars($selected_items_str); ?>">
            <input type="hidden" name="address_id" value="<?php echo $address_id; ?>">
            <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-8 space-y-6">
                    
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h2 class="text-xl font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">รายการสินค้า (<?php echo count($checkout_list); ?>)</h2>
                        <div class="space-y-4">
                            <?php foreach($checkout_list as $item): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-16 bg-slate-100 rounded overflow-hidden">
                                            <?php if($item['cover_image']): ?>
                                                <img src="uploads/covers/<?php echo $item['cover_image']; ?>" class="w-full h-full object-cover">
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-700 text-base"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="text-slate-400 text-xs mt-1">
                                                <?php echo $item['rent_info']; ?> x <?php echo $item['checkout_qty']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="font-bold text-slate-800 text-lg"><?php echo number_format($item['checkout_total']); ?> ฿</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h2 class="text-xl font-bold text-slate-800 mb-4">ที่อยู่จัดส่ง</h2>
                        <?php if($addr): ?>
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <p class="font-bold text-slate-800 text-lg"><?php echo htmlspecialchars($addr['recipient_name']); ?></p>
                                <p class="text-slate-600 mt-1"><?php echo htmlspecialchars($addr['address_line']); ?></p>
                                <p class="text-slate-500 text-sm mt-3 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg> <?php echo htmlspecialchars($addr['phone']); ?></p>
                            </div>
                        <?php else: ?>
                            <p class="text-red-500">ไม่พบข้อมูลที่อยู่</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h2 class="text-xl font-bold text-slate-800 mb-6">ชำระเงิน (QR Code)</h2>
                        <div class="flex flex-col md:flex-row gap-10 items-start">
                            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center w-full md:w-auto flex-shrink-0">
                                <img src="https://promptpay.io/<?php echo $promptpay_id; ?>/<?php echo $total_price; ?>" class="w-48 h-auto object-contain mx-auto mb-2 mix-blend-multiply">
                                <p class="text-slate-500 text-xs uppercase tracking-wide font-bold">Scan to Pay</p>
                            </div>
                            <div class="flex-grow w-full">
                                <label class="block text-sm font-bold text-slate-700 mb-3">แนบหลักฐานการโอนเงิน</label>
                                <div class="relative">
                                    <input type="file" name="slip_image" accept="image/*" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 transition cursor-pointer border border-slate-200 rounded-xl p-2">
                                </div>
                                <p class="text-xs text-gold-600 mt-3 font-medium">* กรุณาตรวจสอบยอดเงินให้ถูกต้องก่อนโอน</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="bg-slate-900 text-white p-8 rounded-2xl shadow-xl shadow-slate-900/20 sticky top-28">
                        <h3 class="font-serif font-bold text-xl mb-6 text-gold-400">สรุปยอดชำระ</h3>
                        <div class="space-y-3 mb-8 border-b border-slate-700 pb-6">
                            <div class="flex justify-between text-slate-400 text-sm">
                                <span>ราคาสินค้า</span>
                                <span><?php echo number_format($total_price); ?> ฿</span>
                            </div>
                            <div class="flex justify-between text-slate-400 text-sm">
                                <span>ค่าจัดส่ง</span>
                                <span>0 ฿</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-end mb-8">
                            <span class="font-bold text-lg">ยอดสุทธิ</span>
                            <span class="font-bold text-4xl text-gold-500"><?php echo number_format($total_price); ?> <span class="text-lg text-slate-500">฿</span></span>
                        </div>
                        <button type="submit" class="w-full bg-gold-500 text-slate-900 py-4 rounded-xl font-bold hover:bg-gold-400 transition shadow-lg shadow-gold-500/20 transform active:scale-95">
                            แจ้งชำระเงิน
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>