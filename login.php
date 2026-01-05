<?php
session_start();
require_once 'config/db.php'; 

$sweetalert_script = "";

// ------------------------------------------------------------------
// 1. Logic เดิม (PHP)
// ------------------------------------------------------------------
if (isset($_POST['signup'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'member';

    try {
        $check = $pdo->prepare("SELECT email FROM members WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            // อีเมลซ้ำ: ให้พลิกไปหน้า Register ค้างไว้
            $sweetalert_script = "<script>Swal.fire({icon: 'warning', title: 'อีเมลนี้มีผู้ใช้งานแล้ว', confirmButtonColor: '#d97706'}).then(() => { flipCard(true); });</script>";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO members (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$fullname, $email, $passwordHash, $role])) {
                $sweetalert_script = "<script>Swal.fire({icon: 'success', title: 'สมัครสมาชิกสำเร็จ!', text: 'ยินดีต้อนรับสู่ The Library', confirmButtonColor: '#0f172a'}).then(() => { window.location.href = 'login.php'; });</script>";
            } else {
                $sweetalert_script = "<script>Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');</script>";
            }
        }
    } catch (PDOException $e) {
        $sweetalert_script = "<script>Swal.fire('System Error', '" . $e->getMessage() . "', 'error');</script>";
    }
}

if (isset($_POST['signin'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['profile_image'] = $user['profile_image'];

            $redirect_url = ($user['role'] == 'admin') ? 'admin/index.php' : 'index.php';
            $sweetalert_script = "<script>const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true}); Toast.fire({icon: 'success', title: 'เข้าสู่ระบบสำเร็จ'}).then(() => { window.location.href = '$redirect_url'; });</script>";
        } else {
            $sweetalert_script = "<script>Swal.fire({icon: 'error', title: 'เข้าสู่ระบบไม่สำเร็จ', text: 'อีเมลหรือรหัสผ่านไม่ถูกต้อง', confirmButtonColor: '#d97706'});</script>";
        }
    } catch (PDOException $e) {
        $sweetalert_script = "<script>Swal.fire('Error', '" . $e->getMessage() . "', 'error');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - The Library</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706' },
                        navy: { 800: '#1e293b', 900: '#0f172a' }
                    },
                    fontFamily: {
                        serif: ['"Playfair Display"', 'serif'],
                        sans: ['"Kanit"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Kanit', sans-serif; }
        
        /* --- 3D Flip Classes --- */
        .perspective-1000 { perspective: 1000px; }
        .transform-style-3d { transform-style: preserve-3d; }
        .backface-hidden { 
            -webkit-backface-visibility: hidden; 
            backface-visibility: hidden; 
        }
        .rotate-y-180 { transform: rotateY(180deg); }
        
        .input-group:focus-within label { color: #d97706; }
        .input-group:focus-within svg { color: #d97706; }
        .input-group:focus-within input { border-color: #f59e0b; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 bg-slate-900 bg-[url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center relative overflow-hidden">
    
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

    <div class="perspective-1000 w-full max-w-4xl h-[650px] relative z-10">
        
        <div id="flip-card-inner" class="relative w-full h-full transition-transform duration-700 transform-style-3d shadow-2xl rounded-3xl">
            
            <div id="front-face" class="absolute w-full h-full backface-hidden bg-white rounded-3xl flex overflow-hidden border border-white/10 z-20">
                <div class="hidden md:flex w-5/12 bg-slate-900 text-white flex-col justify-center items-center p-8 relative">
                    <div class="relative z-10 text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-gold-400 to-gold-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_20px_rgba(245,158,11,0.4)]">
                            <svg class="w-10 h-10 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <h2 class="text-3xl font-serif font-bold mb-3">Welcome Back</h2>
                        <p class="text-slate-400 mb-8 text-sm font-light px-4">เข้าสู่ระบบเพื่อจัดการคลังหนังสือของคุณ</p>
                        <button type="button" onclick="flipCard(true)" class="border border-gold-500 text-gold-500 px-8 py-2.5 rounded-full hover:bg-gold-500 hover:text-slate-900 transition font-bold tracking-wide cursor-pointer z-50">
                            สมัครสมาชิกใหม่ ➜
                        </button>
                    </div>
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                </div>

                <div class="w-full md:w-7/12 p-8 md:p-12 bg-white flex flex-col justify-center relative">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-serif font-bold text-slate-800 mb-2">Sign In</h2>
                        <div class="h-1 w-16 bg-gold-500 mx-auto rounded-full"></div>
                    </div>
                    <form action="" method="post" class="space-y-5 relative z-10">
                        <div class="input-group group">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1 transition-colors">Email</label>
                            <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm transition-all outline-none" placeholder="example@email.com">
                        </div>
                        <div class="input-group group">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1 transition-colors">Password</label>
                            <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm transition-all outline-none" placeholder="••••••••">
                        </div>
                        <button type="submit" name="signin" class="w-full bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-slate-800 transition shadow-lg mt-2 transform active:scale-95">
                            เข้าสู่ระบบ
                        </button>
                    </form>
                    
                    <div class="mt-8 text-center md:hidden border-t border-slate-100 pt-6 relative z-10">
                        <p class="text-sm text-slate-500">ยังไม่มีบัญชี?</p>
                        <button type="button" onclick="flipCard(true)" class="text-gold-600 font-bold hover:underline mt-1 cursor-pointer">สมัครสมาชิก</button>
                    </div>
                </div>
            </div>

            <div id="back-face" class="absolute w-full h-full backface-hidden bg-white rounded-3xl flex overflow-hidden border border-white/10 rotate-y-180 z-10">
                
                <div class="w-full md:w-7/12 p-8 md:p-12 bg-white flex flex-col justify-center relative">
                    <div class="text-center mb-6">
                        <h2 class="text-3xl font-serif font-bold text-slate-800 mb-2">Create Account</h2>
                        <div class="h-1 w-16 bg-gold-500 mx-auto rounded-full"></div>
                    </div>
                    <form action="" method="post" class="space-y-4 relative z-10">
                        <div class="input-group group">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Full Name</label>
                            <input type="text" name="fullname" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none" placeholder="Somchai Jaidee">
                        </div>
                        <div class="input-group group">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</label>
                            <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none" placeholder="example@email.com">
                        </div>
                        <div class="input-group group">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Password</label>
                            <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none" placeholder="••••••••">
                        </div>
                        <button type="submit" name="signup" class="w-full bg-gold-500 text-slate-900 font-bold py-3.5 rounded-xl hover:bg-gold-400 transition shadow-lg mt-2 transform active:scale-95">
                            สมัครสมาชิก
                        </button>
                    </form>

                    <div class="mt-6 text-center md:hidden border-t border-slate-100 pt-6 relative z-10">
                        <p class="text-sm text-slate-500">มีบัญชีแล้ว?</p>
                        <button type="button" onclick="flipCard(false)" class="text-slate-900 font-bold hover:underline mt-1 cursor-pointer">เข้าสู่ระบบ</button>
                    </div>
                </div>

                <div class="hidden md:flex w-5/12 bg-slate-900 text-white flex-col justify-center items-center p-8 relative">
                    <div class="relative z-10 text-center">
                        <div class="w-20 h-20 bg-white/10 backdrop-blur rounded-full flex items-center justify-center mx-auto mb-6 border border-white/20">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <h2 class="text-3xl font-serif font-bold mb-3">Join The Library</h2>
                        <p class="text-slate-400 mb-8 text-sm font-light px-4">เริ่มต้นการเดินทางสู่โลกแห่งความรู้ สมัครสมาชิกกับเราวันนี้</p>
                        <button type="button" onclick="flipCard(false)" class="border border-white text-white px-8 py-2.5 rounded-full hover:bg-white hover:text-slate-900 transition font-bold tracking-wide cursor-pointer z-50">
                            ➜ ฉันมีบัญชีแล้ว
                        </button>
                    </div>
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                </div>

            </div>

        </div>
    </div>
    
    <div class="fixed bottom-4 text-center w-full z-20">
        <a href="index.php" class="text-slate-300 hover:text-white text-sm font-light transition tracking-wide">← กลับไปหน้าหลัก (Guest)</a>
    </div>

    <script>
        // ฟังก์ชันสั่งพลิกการ์ด (Flip Control) + จัดการ Z-Index
        function flipCard(isRegister) {
            const cardInner = document.getElementById('flip-card-inner');
            const frontFace = document.getElementById('front-face');
            const backFace = document.getElementById('back-face');

            if (isRegister) {
                // หมุนไปหน้า Register
                cardInner.classList.add('rotate-y-180');
                
                // สลับ Z-Index เพื่อให้หน้า Register (Back) กดได้
                // รอให้หมุนไปครึ่งทาง (300ms) แล้วค่อยสลับ จะเนียนที่สุด
                setTimeout(() => {
                    frontFace.classList.remove('z-20');
                    frontFace.classList.add('z-10');
                    
                    backFace.classList.remove('z-10');
                    backFace.classList.add('z-20');
                }, 150);
                
            } else {
                // หมุนกลับมาหน้า Login
                cardInner.classList.remove('rotate-y-180');
                
                // สลับ Z-Index กลับมา
                setTimeout(() => {
                    backFace.classList.remove('z-20');
                    backFace.classList.add('z-10');
                    
                    frontFace.classList.remove('z-10');
                    frontFace.classList.add('z-20');
                }, 150);
            }
        }
    </script>
    
    <?php echo $sweetalert_script; ?>
</body>
</html>