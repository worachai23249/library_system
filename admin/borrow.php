<?php
require_once 'header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit;
}

// 1. ดึงรายชื่อสมาชิก (JSON)
$members = $pdo->query("SELECT id, fullname, phone FROM members ORDER BY fullname ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. ดึงหนังสือ
$books = $pdo->query("SELECT * FROM books WHERE status = 'available' ORDER BY title ASC")->fetchAll();
$books_js = [];
foreach($books as $b) {
    $books_js[] = ['id' => $b['id'], 'title' => $b['title'], 'isbn' => $b['isbn']];
}
$books_json = json_encode($books_js);

$today = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime('+7 days'));
?>

<div class="max-w-3xl mx-auto mt-6">
    <div class="mb-6 flex items-center gap-3">
        <div class="bg-navy-900 text-gold-400 p-2 rounded-lg shadow-md">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        </div>
        <h1 class="text-3xl font-serif font-bold text-navy-900">ทำรายการ Walk-in</h1>
    </div>

    <form action="borrow_save.php" method="post" class="space-y-6">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-navy-800 mb-4 flex items-center gap-2 pb-2 border-b border-slate-100">
                <span class="w-1 h-5 bg-gold-500 rounded-full"></span> 👤 ข้อมูลผู้ทำรายการ
            </h3>

            <div class="flex gap-6 mb-4">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="radio" name="user_type" value="member" class="w-4 h-4 text-navy-900 focus:ring-navy-900" checked onchange="toggleUserType()">
                    <span class="text-slate-700 font-bold group-hover:text-navy-900 transition">สมาชิก</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="radio" name="user_type" value="guest" class="w-4 h-4 text-navy-900 focus:ring-navy-900" onchange="toggleUserType()">
                    <span class="text-slate-700 font-bold group-hover:text-navy-900 transition">ลูกค้าทั่วไป</span>
                </label>
            </div>

            <div id="member_select_box" class="mb-4">
                <select name="member_id" id="member_id" class="w-full border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition" onchange="fillMemberInfo()">
                    <option value="">-- เลือกสมาชิก --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?php echo $m['id']; ?>" data-phone="<?php echo htmlspecialchars($m['phone']); ?>">
                            <?php echo htmlspecialchars($m['fullname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="guest_input_box" class="mb-4 hidden">
                <input type="text" name="guest_name" id="guest_name" class="w-full border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition" placeholder="ระบุชื่อลูกค้า...">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" name="borrower_phone" id="borrower_phone" class="w-full border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:border-gold-500 outline-none transition" placeholder="08x-xxx-xxxx" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">เลขบัตร ปชช. (ถ้ามี)</label>
                    <input type="text" name="borrower_citizen_id" class="w-full border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:border-gold-500 outline-none transition" placeholder="13 หลัก" maxlength="13">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                <label class="block text-navy-800 font-bold mb-2 text-sm">วันที่ทำรายการ</label>
                <input type="date" name="borrow_date" value="<?php echo $today; ?>" class="w-full border border-slate-200 rounded-lg p-2.5 focus:border-gold-500 outline-none transition text-slate-700">
            </div>
            
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                <label class="block text-navy-800 font-bold mb-2 text-sm">รูปแบบธุรกรรม</label>
                <div class="flex bg-slate-100 rounded-lg p-1">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="type" value="rent" class="peer sr-only" checked onchange="toggleDueDate()">
                        <div class="text-center py-2 rounded-md text-sm font-bold text-slate-500 peer-checked:bg-white peer-checked:text-navy-900 peer-checked:shadow-sm transition">📖 เช่าอ่าน</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="type" value="sale" class="peer sr-only" onchange="toggleDueDate()">
                        <div class="text-center py-2 rounded-md text-sm font-bold text-slate-500 peer-checked:bg-white peer-checked:text-emerald-600 peer-checked:shadow-sm transition">💰 ซื้อขาด</div>
                    </label>
                </div>
            </div>
        </div>

        <div class="bg-gold-50/50 p-6 rounded-2xl border border-gold-200/50">
            <label class="block text-navy-900 font-bold mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                สแกนบาร์โค้ด (Barcode / ISBN)
            </label>
            <input type="text" id="barcode_input" 
                   class="w-full border-2 border-gold-300 rounded-xl p-3 text-lg font-mono focus:ring-4 focus:ring-gold-100 outline-none mb-4 bg-white shadow-sm placeholder-slate-400" 
                   placeholder="คลิกแล้วยิงบาร์โค้ด..." 
                   autofocus
                   onkeypress="handleBarcode(event)">
            
            <label class="block text-slate-700 text-sm font-bold mb-1">หรือ เลือกหนังสือจากรายการ</label>
            <select name="book_id" id="book_id" class="w-full border border-slate-300 rounded-lg p-3 bg-white focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition" required>
                <option value="">-- เลือกหนังสือ --</option>
                <?php foreach ($books as $b): ?>
                    <option value="<?php echo $b['id']; ?>" data-isbn="<?php echo $b['isbn']; ?>">
                        <?php echo htmlspecialchars($b['title']); ?> (<?php echo htmlspecialchars($b['isbn']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-8" id="due_date_container">
            <div class="bg-red-50 p-4 rounded-xl border border-red-100">
                <label class="block text-red-700 font-bold mb-2 text-sm">กำหนดส่งคืน</label>
                <input type="date" name="due_date" value="<?php echo $default_due_date; ?>" class="w-full border border-red-200 bg-white rounded-lg p-2.5 text-red-600 focus:ring-2 focus:ring-red-100 outline-none">
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" id="submit_btn" class="flex-1 bg-navy-900 text-white font-bold py-3.5 rounded-xl hover:bg-gold-500 hover:text-navy-900 transition shadow-lg transform active:scale-95 text-lg">
                บันทึกรายการ
            </button>
            <a href="return.php" class="px-8 py-3.5 text-slate-600 font-bold hover:bg-slate-100 rounded-xl border border-slate-200 transition">
                ยกเลิก
            </a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const allBooks = <?php echo $books_json; ?>;

function handleBarcode(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const code = e.target.value.trim();
        if(!code) return;

        const foundBook = allBooks.find(b => b.isbn === code);
        
        if (foundBook) {
            document.getElementById('book_id').value = foundBook.id;
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true,
                didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; }
            });
            Toast.fire({ icon: 'success', title: 'พบหนังสือ: ' + foundBook.title });
            e.target.value = '';
        } else {
            Swal.fire({ icon: 'error', title: 'ไม่พบหนังสือ', text: 'ISBN: ' + code, timer: 1500, showConfirmButton: false });
            e.target.value = '';
        }
    }
}

function toggleUserType() {
    const type = document.querySelector('input[name="user_type"]:checked').value;
    const isMember = (type === 'member');
    
    document.getElementById('member_select_box').classList.toggle('hidden', !isMember);
    document.getElementById('guest_input_box').classList.toggle('hidden', isMember);
    
    document.getElementById('member_id').required = isMember;
    document.getElementById('guest_name').required = !isMember;
    
    if(!isMember) {
        document.getElementById('member_id').value = '';
        document.getElementById('borrower_phone').value = '';
    }
}

function fillMemberInfo() {
    const select = document.getElementById('member_id');
    const phone = select.options[select.selectedIndex].getAttribute('data-phone');
    document.getElementById('borrower_phone').value = phone || '';
}

function toggleDueDate() {
    const type = document.querySelector('input[name="type"]:checked').value;
    const isRent = (type === 'rent');
    const container = document.getElementById('due_date_container');
    const btn = document.getElementById('submit_btn');

    if(isRent) {
        container.style.display = 'block';
        btn.className = "flex-1 bg-navy-900 text-white font-bold py-3.5 rounded-xl hover:bg-gold-500 hover:text-navy-900 transition shadow-lg transform active:scale-95 text-lg";
        btn.innerText = "ยืนยันการยืม";
    } else {
        container.style.display = 'none';
        btn.className = "flex-1 bg-emerald-600 text-white font-bold py-3.5 rounded-xl hover:bg-emerald-700 transition shadow-lg transform active:scale-95 text-lg";
        btn.innerText = "ยืนยันการขาย";
    }
}
</script>

<?php require_once 'footer.php'; ?>