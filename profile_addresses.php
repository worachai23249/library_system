<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// จัดการ Add/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_address'])) {
        $name = $_POST['recipient_name'];
        $addr = $_POST['address_line'];
        $phone = $_POST['phone'];
        $lat = !empty($_POST['lat']) ? $_POST['lat'] : null;
        $lng = !empty($_POST['lng']) ? $_POST['lng'] : null;

        $sql = "INSERT INTO addresses (user_id, recipient_name, address_line, phone, lat, lng) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$user_id, $name, $addr, $phone, $lat, $lng]);
        
    } elseif (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?")->execute([$_POST['delete_id'], $user_id]);
    }
    header("Location: profile_addresses.php"); exit;
}

$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
$addresses->execute([$user_id]);
$addresses = $addresses->fetchAll();

// ใส่ CSS ของ Leaflet Map ลงใน Header แบบ Inline ชั่วคราว (หรือจะไปใส่ใน header.php ก็ได้)
require_once 'includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
    /* ปรับแต่งแผนที่ให้เข้ากับธีม */
    #map { height: 300px; width: 100%; border-radius: 0.75rem; z-index: 1; }
    .leaflet-container { font-family: 'Kanit', sans-serif; }
</style>

<div class="bg-slate-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="flex flex-col md:flex-row gap-8">
            
            <aside class="w-full md:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                    <nav class="space-y-1">
                        <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span class="text-sm font-medium">ข้อมูลส่วนตัว</span>
                        </a>
                        <a href="profile_addresses.php" class="flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-md transition">
                            <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="text-sm font-medium">ที่อยู่จัดส่ง</span>
                        </a>
                        <a href="change_password.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span class="text-sm font-medium">เปลี่ยนรหัสผ่าน</span>
                        </a>
                        <a href="verify.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-medium">ยืนยันตัวตน (KYC)</span>
                        </a>
                        <a href="messages.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="text-sm font-medium">การแจ้งเตือน</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <main class="flex-grow">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 pb-4">
                        <h1 class="text-2xl font-serif font-bold text-slate-800 flex items-center gap-3">
                            <span class="bg-gold-100 text-gold-600 p-2 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></span>
                            ที่อยู่จัดส่ง
                        </h1>
                        <button onclick="openAddModal()" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-gold-500 hover:text-slate-900 transition shadow-lg transform hover:-translate-y-0.5 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            เพิ่มที่อยู่ใหม่
                        </button>
                    </div>

                    <?php if (count($addresses) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="border border-slate-200 rounded-xl p-6 hover:border-gold-400 hover:shadow-md transition relative group bg-slate-50/50">
                                    <h3 class="font-bold text-slate-800 text-lg mb-1"><?php echo htmlspecialchars($addr['recipient_name']); ?></h3>
                                    <p class="text-slate-600 text-sm leading-relaxed mb-3 h-12 overflow-hidden"><?php echo htmlspecialchars($addr['address_line']); ?></p>
                                    
                                    <div class="flex items-center justify-between mt-4">
                                        <div class="flex items-center gap-2 text-xs text-slate-400 font-mono">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            <?php echo htmlspecialchars($addr['phone']); ?>
                                        </div>
                                        <?php if($addr['lat'] && $addr['lng']): ?>
                                            <a href="https://www.google.com/maps?q=<?php echo $addr['lat']; ?>,<?php echo $addr['lng']; ?>" target="_blank" class="text-[10px] bg-slate-200 text-slate-600 px-2 py-1 rounded hover:bg-gold-500 hover:text-white transition flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                ดูแผนที่
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition">
                                        <form method="POST" onsubmit="return confirm('ลบที่อยู่นี้?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $addr['id']; ?>">
                                            <button type="submit" class="text-slate-300 hover:text-red-500 p-1 bg-white rounded-full shadow-sm hover:shadow-md transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                            <p class="text-slate-500 mb-4">คุณยังไม่มีที่อยู่จัดส่ง</p>
                            <button onclick="openAddModal()" class="text-gold-600 font-bold hover:underline">เพิ่มที่อยู่ตอนนี้</button>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<div id="add-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-2xl rounded-2xl shadow-2xl p-0 overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                เพิ่มที่อยู่ใหม่
            </h3>
            <button onclick="closeAddModal()" class="text-slate-400 hover:text-red-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-6 overflow-y-auto">
            <form method="POST" class="space-y-4" id="addressForm">
                <input type="hidden" name="add_address" value="1">
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">ชื่อผู้รับ</label>
                        <input type="text" name="recipient_name" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">รายละเอียดที่อยู่ (บ้านเลขที่, ซอย, ถนน)</label>
                    <textarea name="address_line" rows="2" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 flex justify-between items-center">
                        <span>ปักหมุดตำแหน่งจัดส่ง</span>
                        <button type="button" onclick="getCurrentLocation()" class="text-[10px] bg-gold-100 text-gold-700 px-2 py-1 rounded hover:bg-gold-200 transition flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            ใช้ตำแหน่งปัจจุบัน
                        </button>
                    </label>
                    <div id="map" class="border-2 border-slate-200 rounded-xl overflow-hidden shadow-inner"></div>
                    <p class="text-[10px] text-slate-400 mt-1">* เลื่อนหมุดเพื่อระบุตำแหน่งที่แน่นอน</p>
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeAddModal()" class="flex-1 bg-white border border-slate-200 text-slate-600 py-3 rounded-xl font-bold hover:bg-slate-50 transition">ยกเลิก</button>
                    <button type="submit" class="flex-1 bg-slate-900 text-white py-3 rounded-xl font-bold hover:bg-gold-500 hover:text-slate-900 transition">บันทึกที่อยู่</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
let map, marker;

function initMap() {
    // พิกัดเริ่มต้น (กรุงเทพฯ หรือศูนย์กลางไทย)
    const defaultLat = 13.7563;
    const defaultLng = 100.5018;

    if (!map) {
        map = L.map('map').setView([defaultLat, defaultLng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);

        // เมื่อลากหมุดเสร็จ ให้เก็บค่า
        marker.on('dragend', function(event) {
            const position = marker.getLatLng();
            updateInputs(position.lat, position.lng);
        });

        // คลิกที่แผนที่เพื่อย้ายหมุด
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateInputs(e.latlng.lat, e.latlng.lng);
        });
    }
}

function updateInputs(lat, lng) {
    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lng;
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            map.setView([lat, lng], 16);
            marker.setLatLng([lat, lng]);
            updateInputs(lat, lng);
        }, function() {
            alert("ไม่สามารถระบุตำแหน่งได้ กรุณาเปิด GPS");
        });
    } else {
        alert("Browser ของคุณไม่รองรับ Geolocation");
    }
}

function openAddModal() {
    document.getElementById('add-modal').classList.remove('hidden');
    
    // ต้องรอให้ Modal แสดงผลก่อน ค่อยโหลดแผนที่ (ไม่งั้นแผนที่จะเป็นสีเทา)
    setTimeout(() => {
        initMap();
        map.invalidateSize(); // สั่งให้แผนที่คำนวณขนาดใหม่
    }, 200);
}

function closeAddModal() {
    document.getElementById('add-modal').classList.add('hidden');
}
</script>

<?php require_once 'includes/footer.php'; ?>