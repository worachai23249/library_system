<?php
session_start();
require_once 'config/db.php'; 

// --- ส่วนเรียกใช้ PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists('PHPMailer/src/Exception.php')) {
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
}

// ==================================================================
//  HANDLE AJAX REQUESTS (รับค่าจาก JS แล้วส่ง JSON กลับ ไม่โหลดหน้าใหม่)
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด'];

    // --- ส่วนสมัครสมาชิก (Signup) ---
    if (isset($_POST['action']) && $_POST['action'] === 'signup') {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = 'member';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'warning', 'message' => 'รูปแบบอีเมลไม่ถูกต้อง']);
            exit;
        }

        try {
            $check = $pdo->prepare("SELECT email FROM members WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                echo json_encode(['status' => 'warning', 'message' => 'อีเมลนี้มีผู้ใช้งานแล้ว']);
                exit;
            }

            $otp = rand(100000, 999999);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO members (fullname, email, password, role, otp_code, is_email_verified) VALUES (?, ?, ?, ?, ?, 0)");
            
            if ($stmt->execute([$fullname, $email, $passwordHash, $role, $otp])) {
                // ส่งอีเมล
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'worachai23249@gmail.com'; 
                        $mail->Password   = 'ynlytcikzrdvljth';      
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->CharSet    = 'UTF-8';

                        $mail->setFrom('worachai23249@gmail.com', 'The Library System');
                        $mail->addAddress($email, $fullname);
                        $mail->isHTML(true);
                        $mail->Subject = 'รหัสยืนยันการสมัครสมาชิก (OTP)';
                        $mail->Body    = "
                            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                                <h2 style='color: #0f172a;'>ยินดีต้อนรับสู่ The Library</h2>
                                <p>รหัส OTP ของคุณคือ:</p>
                                <h1 style='color: #d97706; background: #fef3c7; display: inline-block; padding: 10px 20px;'>{$otp}</h1>
                            </div>
                        ";
                        $mail->send();
                        
                        $_SESSION['pending_email'] = $email;
                        echo json_encode(['status' => 'success', 'redirect' => 'verify_otp.php']);
                    } catch (Exception $e) {
                        echo json_encode(['status' => 'error', 'message' => 'บันทึกได้ แต่ส่งเมลไม่สำเร็จ: ' . $mail->ErrorInfo]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'ไม่พบ PHPMailer']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกข้อมูลลงฐานข้อมูลได้']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // --- ส่วนเข้าสู่ระบบ (Signin) ---
    if (isset($_POST['action']) && $_POST['action'] === 'signin') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $bypass_emails = ['admin@library.com', '', '']; // ใส่ Bypass ตรงนี้ตามเดิม

                if ($user['is_email_verified'] == 0 && !in_array($email, $bypass_emails)) {
                    $_SESSION['pending_email'] = $email;
                    echo json_encode([
                        'status' => 'warning', 
                        'message' => 'ยังไม่ได้ยืนยันอีเมล', 
                        'redirect' => 'verify_otp.php',
                        'confirmBtn' => true
                    ]);
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['fullname'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['profile_image'] = $user['profile_image'];

                    $redirect_url = ($user['role'] == 'admin') ? 'admin/index.php' : 'index.php';
                    
                    echo json_encode([
                        'status' => 'success', 
                        'message' => 'เข้าสู่ระบบสำเร็จ', 
                        'redirect' => $redirect_url
                    ]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
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
        .container-box { position: relative; overflow: hidden; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; }
        .sign-in-container { left: 0; width: 50%; z-index: 2; }
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }
        .container-box.right-panel-active .sign-in-container { transform: translateX(100%); }
        .container-box.right-panel-active .sign-up-container { transform: translateX(100%); opacity: 1; z-index: 5; animation: show 0.6s; }
        @keyframes show { 0%, 49.99% { opacity: 0; z-index: 1; } 50%, 100% { opacity: 1; z-index: 5; } }
        .overlay-container { position: absolute; top: 0; left: 50%; width: 50%; height: 100%; overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; }
        .container-box.right-panel-active .overlay-container { transform: translateX(-100%); }
        .overlay { background: linear-gradient(to right, #1e293b, #0f172a); color: #FFFFFF; position: relative; left: -100%; height: 100%; width: 200%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .container-box.right-panel-active .overlay { transform: translateX(50%); }
        .overlay-panel { position: absolute; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 40px; text-align: center; top: 0; height: 100%; width: 50%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .overlay-left { transform: translateX(-20%); }
        .container-box.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container-box.right-panel-active .overlay-right { transform: translateX(20%); }
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
                
                <form id="signupForm" class="w-full space-y-4 text-left">
                    <input type="hidden" name="action" value="signup">
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">ชื่อ-นามสกุล</label>
                        <input type="text" name="fullname" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none transition" placeholder="สมชาย ใจดี">
                    </div>
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">อีเมล</label>
                        <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none transition" placeholder="example@email.com">
                    </div>
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">รหัสผ่าน</label>
                        <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm outline-none transition" placeholder="••••••••">
                    </div>
                    <button type="submit" class="w-full bg-gold-500 text-slate-900 font-bold py-3.5 rounded-xl hover:bg-gold-400 transition shadow-lg mt-2 transform active:scale-95">
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
                
                <form id="signinForm" class="w-full space-y-5 text-left">
                    <input type="hidden" name="action" value="signin">
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1 transition-colors">อีเมล</label>
                        <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm transition-all outline-none" placeholder="example@email.com">
                    </div>
                    <div class="input-group group">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1 transition-colors">รหัสผ่าน</label>
                        <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-xl text-sm transition-all outline-none" placeholder="••••••••">
                    </div>
                    <button type="submit" class="w-full bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-slate-800 transition shadow-lg mt-2 transform active:scale-95">
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
                    <h2 class="text-3xl font-serif font-bold mb-4">ยินดีต้อนรับกลับมา!</h2>
                    <p class="text-slate-300 mb-8 font-light leading-relaxed">เข้าสู่ระบบเพื่อจัดการหนังสือของคุณ</p>
                    <button class="border border-white bg-transparent text-white px-8 py-2.5 rounded-full font-bold hover:bg-white hover:text-navy-900 transition tracking-wide transform active:scale-95" id="signInBtn">เข้าสู่ระบบ</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h2 class="text-3xl font-serif font-bold mb-4">เข้าร่วมกับเรา</h2>
                    <p class="text-slate-300 mb-8 font-light leading-relaxed">เริ่มต้นใช้งาน Library System วันนี้</p>
                    <button class="border border-gold-500 bg-transparent text-gold-500 px-8 py-2.5 rounded-full font-bold hover:bg-gold-500 hover:text-navy-900 transition tracking-wide transform active:scale-95" id="signUpBtn">สมัครสมาชิกใหม่</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="fixed bottom-4 text-center w-full z-20">
        <a href="index.php" class="text-slate-300 hover:text-white text-sm font-light transition tracking-wide">← กลับไปหน้าหลัก (Guest)</a>
    </div>

    <script>
        // UI Animation Logic
        const signUpBtn = document.getElementById('signUpBtn');
        const signInBtn = document.getElementById('signInBtn');
        const container = document.getElementById('mainContainer');

        signUpBtn.addEventListener('click', () => { container.classList.add("right-panel-active"); });
        signInBtn.addEventListener('click', () => { container.classList.remove("right-panel-active"); });

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

        // ============================================
        // AJAX FORM SUBMISSION (แก้ปัญหาหน้ากระพริบ)
        // ============================================
        
        async function handleFormSubmit(event, formId) {
            event.preventDefault(); // หยุดการรีโหลดหน้า
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // เปลี่ยนปุ่มเป็น Loading
            const originalText = submitBtn.innerText;
            submitBtn.innerText = 'กำลังประมวลผล...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    // Success Case
                    const Toast = Swal.mixin({
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true
                    });
                    
                    if (result.redirect) {
                        Toast.fire({ icon: 'success', title: result.message || 'สำเร็จ' }).then(() => {
                            window.location.href = result.redirect;
                        });
                    } else {
                        Swal.fire({ icon: 'success', title: 'สำเร็จ', text: result.message });
                    }

                } else if (result.status === 'warning') {
                    // Warning Case
                    Swal.fire({
                        icon: 'warning',
                        title: 'แจ้งเตือน',
                        text: result.message,
                        confirmButtonText: result.confirmBtn ? 'ตกลง' : 'ปิด',
                        confirmButtonColor: '#d97706'
                    }).then(() => {
                        if (result.redirect) window.location.href = result.redirect;
                    });
                } else {
                    // Error Case
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: result.message });
                }

            } catch (error) {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'System Error', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' });
            } finally {
                // คืนค่าปุ่มกลับมาเหมือนเดิม
                submitBtn.innerText = originalText;
                submitBtn.disabled = false;
            }
        }

        // แนบ Event Listener
        document.getElementById('signinForm').addEventListener('submit', (e) => handleFormSubmit(e, 'signinForm'));
        document.getElementById('signupForm').addEventListener('submit', (e) => handleFormSubmit(e, 'signupForm'));

    </script>
</body>
</html>