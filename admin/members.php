<?php
require_once 'header.php'; // เรียก Header ของ Admin

// ดึงข้อมูลสมาชิก (ไม่รวม Admin)
$sql = "SELECT * FROM members WHERE role != 'admin' ORDER BY id ASC";
$members = $pdo->query($sql)->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h1 class="text-3xl font-serif font-bold text-navy-900">👥 จัดการสมาชิก</h1>
        <p class="text-slate-500 text-sm mt-1">รายชื่อสมาชิกทั้งหมดและสถานะการยืนยันตัวตน</p>
    </div>
    <a href="member_form.php" class="bg-navy-900 text-white px-5 py-2.5 rounded-full shadow-lg hover:bg-gold-500 hover:text-navy-900 transition transform hover:-translate-y-0.5 flex items-center gap-2 font-bold text-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
        ลงทะเบียนสมาชิกใหม่
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="p-4 font-bold text-slate-500 text-sm w-20 text-center">ลำดับ</th>
                    <th class="p-4 font-bold text-slate-500 text-sm">ข้อมูลสมาชิก</th>
                    <th class="p-4 font-bold text-slate-500 text-sm">เบอร์โทรศัพท์</th>
                    <th class="p-4 font-bold text-slate-500 text-sm">วันที่สมัคร</th>
                    <th class="p-4 font-bold text-slate-500 text-sm text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php 
                    $i = 1; 
                    foreach ($members as $member): 
                ?>
                <tr class="hover:bg-slate-50/50 transition duration-150">
                    <td class="p-4 text-slate-400 font-bold text-center"><?php echo $i++; ?></td>
                    
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-navy-50 text-navy-700 flex items-center justify-center font-bold text-lg border border-navy-100">
                                <?php echo mb_substr($member['fullname'], 0, 1); ?>
                            </div>
                            <div>
                                <div class="font-bold text-navy-900 text-base"><?php echo htmlspecialchars($member['fullname']); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($member['email']); ?></div>
                                <?php if($member['role'] == ''): ?>
                                    <span class="inline-block mt-1 text-[10px] bg-gold-100 text-gold-700 px-1.5 py-0.5 rounded font-bold">รอระบุ Role</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-slate-600 font-medium">
                        <?php echo !empty($member['phone']) ? htmlspecialchars($member['phone']) : '<span class="text-slate-300">-</span>'; ?>
                    </td>
                    <td class="p-4 text-sm text-slate-500">
                        <?php echo isset($member['registered_at']) ? date('d/m/Y', strtotime($member['registered_at'])) : '-'; ?>
                    </td>
                    <td class="p-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="member_form.php?id=<?php echo $member['id']; ?>" class="text-slate-400 hover:text-gold-600 p-2 hover:bg-gold-50 rounded-lg transition" title="แก้ไข">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <a href="member_delete.php?id=<?php echo $member['id']; ?>" 
                               onclick="return confirm('ยืนยันที่จะลบสมาชิกคนนี้? \n(หากมีประวัติการยืมค้างอยู่ จะลบไม่ได้)');" 
                               class="text-slate-400 hover:text-red-600 p-2 hover:bg-red-50 rounded-lg transition" title="ลบ">
                               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if(count($members) == 0): ?>
        <div class="p-12 text-center text-slate-400 bg-white">
            <svg class="w-16 h-16 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <p>ยังไม่มีสมาชิกในระบบ</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>