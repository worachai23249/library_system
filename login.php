<?php
session_start();
require_once 'config/db.php'; 

// --- ส่วนเรียกใช้ PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ตรวจสอบว่ามีไฟล์ PHPMailer หรือยัง
if (file_exists('PHPMailer/src/Exception.php')) {
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
}
// ----------------------------

$sweetalert_script = "";

// ------------------------------------------------------------------
// PHP Logic (Login & Signup)
// ------------------------------------------------------------------
if (isset($_POST['signup'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'member';

    // 1. ตรวจสอบรูปแบบอีเมล (ป้องกัน Error: Invalid address)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sweetalert_script = "<script>
            Swal.fire({
                icon: 'warning',
                title: 'รูปแบบอีเมลไม่ถูกต้อง',
                text: 'กรุณากรอกอีเมลให้ถูกต้อง (ต้องมี @ และ .com)',
                confirmButtonText: 'ตกลง'
            }).then(() => { 
                document.getElementById('mainContainer').classList.add('right-panel-active');
            });
        </script>";
    } else {
        try {
            // 2. เช็คว่าอีเมลซ้ำไหม
            $check = $pdo->prepare("SELECT email FROM members WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $sweetalert_script = "<script>
                    Swal.fire({
                        icon: 'warning', 
                        title: 'อีเมลนี้มีผู้ใช้งานแล้ว', 
                        confirmButtonText: 'ตกลง'
                    }).then(() => { 
                        document.getElementById('mainContainer').classList.add('right-panel-active');
                    });
                </script>";
            } else {
                // 3. สุ่มรหัส OTP 6 หลัก
                $otp = rand(100000, 999999);
                
                // 4. บันทึกข้อมูล (is_email_verified = 0)
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // ตรวจสอบว่าใน Database มีคอลัมน์ otp_code หรือยัง (ถ้ารัน SQL แล้วจะไม่มีปัญหา)
                $stmt = $pdo->prepare("INSERT INTO members (fullname, email, password, role, otp_code, is_email_verified) VALUES (?, ?, ?, ?, ?, 0)");
                
                if ($stmt->execute([$fullname, $email, $passwordHash, $role, $otp])) {
                    
                    // 5. ส่งอีเมล (เฉพาะถ้ามี PHPMailer)
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        $mail = new PHPMailer(true);
                        try {
                            // ตั้งค่า SMTP (Gmail)
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            
                            // ************************************************
                            // ✅ ตั้งค่าอีเมลและรหัสผ่านของคุณให้แล้วครับ
                            $mail->Username   = 'worachai23249@gmail.com'; 
                            $mail->Password   = 'ynlytcikzrdvljth';      
                            // ************************************************
                            
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->CharSet    = 'UTF-8';

                            // ผู้ส่งและผู้รับ
                            $mail->setFrom('worachai23249@gmail.com', 'The Library System');
                            $mail->addAddress($email, $fullname);

                            // เนื้อหา
                            $mail->isHTML(true);
                            $mail->Subject = 'รหัสยืนยันการสมัครสมาชิก (OTP)';
                            $mail->Body    = "
                                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                                    <h2 style='color: #0f172a;'>ยินดีต้อนรับสู่ The Library</h2>
                                    <p>ขอบคุณที่สมัครสมาชิก รหัสยืนยันตัวตน (OTP) ของคุณคือ:</p>
                                    <h1 style='color: #d97706; letter-spacing: 5px; background: #fef3c7; display: inline-block; padding: 10px 20px; border-radius: 5px;'>{$otp}</h1>
                                    <p>กรุณานำรหัสนี้ไปกรอกในหน้าเว็บไซต์เพื่อยืนยันบัญชี</p>
                                    <hr>
                                    <small style='color: #888;'>หากคุณไม่ได้ทำรายการนี้ โปรดเพิกเฉยต่ออีเมลฉบับนี้</small>
                                </div>
                            ";

                            $mail->send();

                            // ส่งสำเร็จ -> ไปหน้ายืนยัน OTP
                            $_SESSION['pending_email'] = $email;
                            header("Location: verify_otp.php");
                            exit;

                        } catch (Exception $e) {
                            // ถ้าส่งไม่ผ่าน แต่บันทึก DB แล้ว ให้แจ้งเตือน
                            $sweetalert_script = "<script>Swal.fire({icon: 'error', title: 'ส่งอีเมลไม่สำเร็จ', text: 'ระบบบันทึกข้อมูลแล้ว แต่ส่งอีเมลไม่ได้: " . $mail->ErrorInfo . "'});</script>";
                        }
                    } else {
                        $sweetalert_script = "<script>Swal.fire({icon: 'error', title: 'ไม่พบ PHPMailer', text: 'กรุณาติดตั้งโฟลเดอร์ PHPMailer'});</script>";
                    }

                } else {
                    $sweetalert_script = "<script>Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถบันทึกข้อมูลได้'});</script>";
                }
            }
        } catch (PDOException $e) {
            $sweetalert_script = "<script>Swal.fire({icon: 'error', title: 'System Error', text: '" . $e->getMessage() . "'});</script>";
        }
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
            
            // ============================================================
            // ✅ BYPASS: อีเมลเหล่านี้จะเข้าสู่ระบบได้เลยโดยไม่ต้องยืนยัน OTP
            // ============================================================
            $bypass_emails = [
                'admin@library.com',
                '',
                '' // <-- อีเมลของคุณ เข้าได้ทันที!
            ]; 

            // เช็คว่ายืนยันอีเมลหรือยัง (ยกเว้นคนที่มีชื่อใน $bypass_emails)
            if ($user['is_email_verified'] == 0 && !in_array($email, $bypass_emails)) {
                 $_SESSION['pending_email'] = $email;
                 $sweetalert_script = "<script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'ยังไม่ได้ยืนยันอีเมล',
                        text: 'ระบบจะพาไปหน้ากรอกรหัส OTP',
                        confirmButtonText: 'ไปหน้ากรอกรหัส',
                        confirmButtonColor: '#d97706'
                    }).then(() => {
                        window.location.href = 'verify_otp.php';
                    });
                </script>";
            } else {
                // ล็อกอินผ่านฉลุย
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['profile_image'] = $user['profile_image'];

                $redirect_url = ($user['role'] == 'admin') ? 'admin/index.php' : 'index.php';
                
                $sweetalert_script = "<script>
                    const Toast = Swal.mixin({
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true
                    }); 
                    Toast.fire({ icon: 'success', title: 'เข้าสู่ระบบสำเร็จ' }).then(() => { window.location.href = '$redirect_url'; });
                </script>";
            }

        } else {
            $sweetalert_script = "<script>Swal.fire({icon: 'error', title: 'เข้าสู่ระบบไม่สำเร็จ', text: 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'});</script>";
        }
    } catch (PDOException $e) {
        $sweetalert_script = "<script>Swal.fire({icon: 'error', title: 'Error', text: '" . $e->getMessage() . "'});</script>";
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
        
        /* --- SLIDING ANIMATION STYLES --- */
        .container-box { position: relative; overflow: hidden; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; }

        /* Login Form (Default Left) */
        .sign-in-container { left: 0; width: 50%; z-index: 2; }

        /* Register Form (Default Left, Hidden) */
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }

        /* Animation States when Active */
        .container-box.right-panel-active .sign-in-container { transform: translateX(100%); }
        .container-box.right-panel-active .sign-up-container { transform: translateX(100%); opacity: 1; z-index: 5; animation: show 0.6s; }

        @keyframes show {
            0%, 49.99% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        /* Overlay (The Moving Part) */
        .overlay-container { position: absolute; top: 0; left: 50%; width: 50%; height: 100%; overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; }
        .container-box.right-panel-active .overlay-container { transform: translateX(-100%); }

        .overlay { background: #0f172a; background: linear-gradient(to right, #1e293b, #0f172a); background-repeat: no-repeat; background-size: cover; background-position: 0 0; color: #FFFFFF; position: relative; left: -100%; height: 100%; width: 200%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .container-box.right-panel-active .overlay { transform: translateX(50%); }

        .overlay-panel { position: absolute; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 40px; text-align: center; top: 0; height: 100%; width: 50%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .overlay-left { transform: translateX(-20%); }
        .container-box.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container-box.right-panel-active .overlay-right { transform: translateX(20%); }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .form-container { width: 100%; position: relative; }
            .sign-in-container, .sign-up-container { width: 100%; height: auto; opacity: 1; z-index: 1; transform: none !important; }
            .overlay-container { display: none; }
        }
        
        .input-group:focus-within label { color: #d97706; }
        .input-group:focus-within input { border-color: #f59e0b; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 bg-slate-900 bg-[url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center relative overflow-hidden">
    
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

    <div class="container-box w-full max-w-4xl min-h-[600px] bg-white relative z-10" id="mainContainer">
        
        <div class="form-container sign-up-container bg-white" id="mobileSignUp">
            <div class="h-full flex flex-col justify-center items-center px-8 md:px-12 py-8 text-center">
                <h2 class="text-3xl font-serif font-bold text-slate-800 mb-2">สร้างบัญชีใหม่</h2>
                <div class="h-1 w-16 bg-gold-500 rounded-full mb-6 mx-auto"></div>
                
                <form action="" method="post" class="w-full space-y-4 text-left">
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">ชื่อ-นามสกุล</label>
                        <input type="text" name="fullname" required 
                               oninvalid="this.setCustomValidity('กรุณากรอกชื่อ-นามสกุล')" 
                               oninput="this.setCustomValidity('')"
                               class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none transition" placeholder="สมชาย ใจดี">
                    </div>
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">อีเมล</label>
                        <input type="email" name="email" required 
                               oninvalid="this.setCustomValidity('กรุณากรอกอีเมลที่ถูกต้อง')" 
                               oninput="this.setCustomValidity('')"
                               class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none transition" placeholder="example@email.com">
                    </div>
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">รหัสผ่าน</label>
                        <input type="password" name="password" required 
                               oninvalid="this.setCustomValidity('กรุณากำหนดรหัสผ่าน')" 
                               oninput="this.setCustomValidity('')"
                               class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none transition" placeholder="••••••••">
                    </div>
                    <button type="submit" name="signup" class="w-full bg-gold-500 text-slate-900 font-bold py-3.5 rounded-xl hover:bg-gold-400 transition shadow-lg mt-2 transform active:scale-95">
                        สมัครสมาชิก
                    </button>
                </form>

                <div class="mt-6 md:hidden">
                    <p class="text-sm text-slate-500">มีบัญชีแล้ว?</p>
                    <button type="button" onclick="toggleMobileView('signin')" class="text-navy-900 font-bold hover:underline">เข้าสู่ระบบ</button>
                </div>
            </div>
        </div>

        <div class="form-container sign-in-container bg-white" id="mobileSignIn">
            <div class="h-full flex flex-col justify-center items-center px-8 md:px-12 py-8 text-center">
                <h2 class="text-3xl font-serif font-bold text-slate-800 mb-2">เข้าสู่ระบบ</h2>
                <div class="h-1 w-16 bg-gold-500 rounded-full mb-8 mx-auto"></div>
                
                <form action="" method="post" class="w-full space-y-5 text-left">
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1 transition-colors">อีเมล</label>
                        <input type="email" name="email" required 
                               oninvalid="this.setCustomValidity('กรุณากรอกอีเมล')" 
                               oninput="this.setCustomValidity('')"
                               class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm transition-all outline-none" placeholder="example@email.com">
                    </div>
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1 transition-colors">รหัสผ่าน</label>
                        <input type="password" name="password" required 
                               oninvalid="this.setCustomValidity('กรุณากรอกรหัสผ่าน')" 
                               oninput="this.setCustomValidity('')"
                               class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm transition-all outline-none" placeholder="••••••••">
                    </div>
                    <button type="submit" name="signin" class="w-full bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-slate-800 transition shadow-lg mt-2 transform active:scale-95">
                        เข้าสู่ระบบ
                    </button>
                </form>

                <div class="mt-8 md:hidden border-t border-slate-100 pt-6 w-full">
                    <p class="text-sm text-slate-500">ยังไม่มีบัญชี?</p>
                    <button type="button" onclick="toggleMobileView('signup')" class="text-gold-600 font-bold hover:underline mt-1">สมัครสมาชิก</button>
                </div>
            </div>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <div class="w-20 h-20 bg-white/10 backdrop-blur rounded-full flex items-center justify-center mb-6 border border-white/20">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    </div>
                    <h2 class="text-3xl font-serif font-bold mb-4">ยินดีต้อนรับกลับมา!</h2>
                    <p class="text-slate-300 mb-8 font-light leading-relaxed">
                        ถ้าคุณมีบัญชีผู้ใช้งานอยู่แล้ว <br>สามารถเข้าสู่ระบบเพื่อจัดการหนังสือได้ทันที
                    </p>
                    <button class="border border-white bg-transparent text-white px-8 py-2.5 rounded-full font-bold hover:bg-white hover:text-navy-900 transition tracking-wide transform active:scale-95" id="signInBtn">
                        เข้าสู่ระบบ
                    </button>
                </div>

                <div class="overlay-panel overlay-right">
                    <div class="w-20 h-20 bg-gradient-to-br from-gold-400 to-gold-600 rounded-full flex items-center justify-center mb-6 shadow-[0_0_20px_rgba(245,158,11,0.4)]">
                        <svg class="w-10 h-10 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h2 class="text-3xl font-serif font-bold mb-4">เข้าร่วมกับเรา</h2>
                    <p class="text-slate-300 mb-8 font-light leading-relaxed">
                        เริ่มต้นการเดินทางสู่โลกแห่งความรู้ <br>และคลังหนังสือพรีเมียม สมัครสมาชิกเลย
                    </p>
                    <button class="border border-gold-500 bg-transparent text-gold-500 px-8 py-2.5 rounded-full font-bold hover:bg-gold-500 hover:text-navy-900 transition tracking-wide transform active:scale-95" id="signUpBtn">
                        สมัครสมาชิกใหม่
                    </button>
                </div>
            </div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 pointer-events-none"></div>
        </div>
    </div>
    
    <div class="fixed bottom-4 text-center w-full z-20">
        <a href="index.php" class="text-slate-300 hover:text-white text-sm font-light transition tracking-wide">← กลับไปหน้าหลัก (Guest)</a>
    </div>

    <script>
        // Desktop Sliding Logic
        const signUpBtn = document.getElementById('signUpBtn');
        const signInBtn = document.getElementById('signInBtn');
        const container = document.getElementById('mainContainer');

        signUpBtn.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInBtn.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        // Mobile Switch Logic (Hidden/Block)
        function toggleMobileView(view) {
            const signInForm = document.getElementById('mobileSignIn');
            const signUpForm = document.getElementById('mobileSignUp');
            
            if (view === 'signup') {
                signInForm.classList.add('hidden');
                signUpForm.classList.remove('hidden');
            } else {
                signUpForm.classList.add('hidden');
                signInForm.classList.remove('hidden');
            }
        }
    </script>
    
    <?php echo $sweetalert_script; ?>
</body>
</html>