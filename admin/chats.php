<?php
require_once 'header.php';
?>

<div class="flex h-[calc(100vh-140px)] bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    
    <div class="w-1/3 border-r border-slate-200 bg-slate-50 flex flex-col">
        <div class="p-5 border-b border-slate-200 font-bold text-navy-900 bg-white flex items-center gap-2 shadow-sm z-10">
            <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <span class="font-serif tracking-wide">Customer Chats</span>
        </div>
        <div id="user-list" class="flex-1 overflow-y-auto custom-scrollbar">
            </div>
    </div>

    <div class="w-2/3 flex flex-col bg-[#f1f5f9] relative">
        <div id="chat-header" class="px-6 py-4 bg-white border-b border-slate-200 font-bold text-navy-900 shadow-sm hidden z-10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-navy-900 text-gold-500 flex items-center justify-center font-bold text-lg shadow-sm" id="header-avatar">
                        U
                    </div>
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                </div>
                <div>
                    <div class="text-sm text-slate-500 font-normal">กำลังสนทนากับ</div>
                    <div id="current-user-name" class="text-navy-900 font-bold text-lg leading-tight"></div>
                </div>
            </div>
        </div>

        <div id="admin-chat-messages" class="flex-1 p-6 overflow-y-auto space-y-4">
            <div class="flex flex-col items-center justify-center h-full text-slate-400 gap-4">
                <div class="w-24 h-24 bg-slate-200 rounded-full flex items-center justify-center text-4xl shadow-inner">💬</div>
                <p>เลือกรายชื่อทางซ้ายเพื่อเริ่มสนทนา</p>
            </div>
        </div>

        <div id="input-area-wrapper" class="hidden bg-white border-t border-slate-200 p-4">
            <div id="admin-img-preview" class="hidden mb-2 relative w-fit p-2 bg-slate-50 rounded border border-slate-200 mx-auto">
                <img src="" class="h-24 rounded object-cover shadow-sm">
                <button type="button" onclick="clearAdminImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow hover:bg-red-600 transition">×</button>
            </div>

            <div id="recording-status" class="hidden text-xs text-red-500 text-center mb-2 animate-pulse font-bold bg-red-50 py-1.5 rounded-lg border border-red-100">
                🎙️ กำลังบันทึกเสียง... (กดที่ไมค์อีกครั้งเพื่อส่ง)
            </div>

            <form id="admin-chat-form" class="flex gap-3 items-center" onsubmit="sendAdminMessage(event)">
                <input type="hidden" id="current-partner-id">
                
                <label class="cursor-pointer text-slate-400 hover:text-gold-500 transition p-2 hover:bg-gold-50 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <input type="file" id="admin-chat-img" accept="image/*" class="hidden" onchange="previewAdminImage(this)">
                </label>
                
                <input type="text" id="admin-chat-input" class="flex-grow border border-slate-200 bg-slate-50 rounded-full px-5 py-3 focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500 focus:bg-white transition text-sm" placeholder="พิมพ์ข้อความตอบกลับ...">
                
                <button type="button" id="mic-btn" class="text-slate-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-full transition duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                </button>

                <button type="submit" class="bg-navy-900 text-white px-6 py-3 rounded-full hover:bg-gold-500 hover:text-navy-900 shadow-md transition transform active:scale-95 font-bold flex items-center gap-2">
                    <span>ส่ง</span>
                    <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>

<div id="image-modal" class="fixed inset-0 z-[60] bg-black/90 hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeImageModal()">
    <div class="relative max-w-4xl max-h-full transform scale-95 transition-transform duration-300" id="modal-content">
        <img id="modal-img" src="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl object-contain">
        <button onclick="closeImageModal()" class="absolute -top-4 -right-4 bg-white text-black rounded-full w-8 h-8 flex items-center justify-center font-bold shadow-lg hover:bg-gray-200 transition transform hover:scale-110">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    #image-modal:not(.hidden) { opacity: 1; }
    #image-modal:not(.hidden) #modal-content { transform: scale(100%); }
</style>

<script>
let currentPartnerId = null;
const apiBase = '../chat_api.php';
// ✅ ตัวแปรสำคัญ: เก็บข้อมูลล่าสุดไว้เปรียบเทียบ (แก้เสียงตัด)
let lastChatData = ''; 

// 1. โหลดรายชื่อผู้ใช้
function fetchUsers() {
    fetch(apiBase + '?action=get_users')
    .then(res => res.json())
    .then(users => {
        const list = document.getElementById('user-list');
        
        let html = '';
        users.forEach(u => {
            const isActive = (u.id == currentPartnerId);
            const activeClass = isActive 
                ? 'bg-white border-l-4 border-gold-500 shadow-md z-10' 
                : 'hover:bg-white border-l-4 border-transparent hover:border-slate-200';
            
            const badge = u.unread > 0 
                ? `<span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full ml-auto shadow-sm animate-pulse">${u.unread}</span>` 
                : '';
            
            const avatarColor = isActive ? 'bg-navy-900 text-gold-500' : 'bg-slate-200 text-slate-500';

            html += `
                <div onclick="selectUser(${u.id}, '${u.fullname}')" class="p-4 border-b border-slate-100 cursor-pointer flex items-center gap-3 transition duration-200 ${activeClass}">
                    <div class="w-10 h-10 rounded-full ${avatarColor} flex items-center justify-center font-bold text-lg shadow-sm flex-shrink-0">
                        ${u.fullname.charAt(0)}
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="font-bold text-navy-900 text-sm truncate">${u.fullname}</div>
                        <div class="text-xs text-slate-400 truncate">สมาชิกห้องสมุด</div>
                    </div>
                    ${badge}
                </div>
            `;
        });
        list.innerHTML = html;
    });
}

// 2. เลือกแชท
function selectUser(id, name) {
    if (currentPartnerId !== id) {
        currentPartnerId = id;
        lastChatData = ''; // Reset ข้อมูลเมื่อเปลี่ยนคนคุย
        document.getElementById('current-partner-id').value = id;
        document.getElementById('current-user-name').innerText = name;
        document.getElementById('header-avatar').innerText = name.charAt(0);
        
        document.getElementById('chat-header').classList.remove('hidden');
        document.getElementById('input-area-wrapper').classList.remove('hidden'); // แสดงกล่องพิมพ์
        
        fetchAdminMessages();
    }
    fetchUsers(); 
}

// 3. ดึงข้อความ (Logic เดียวกับฝั่งลูกค้า เพื่อไม่ให้เสียงตัด)
function fetchAdminMessages() {
    if (!currentPartnerId) return;

    fetch(`${apiBase}?action=fetch&partner_id=${currentPartnerId}`)
    .then(res => res.json())
    .then(data => {
        // ✅ ตรวจสอบว่าข้อมูลมีการเปลี่ยนแปลงหรือไม่
        const currentDataString = JSON.stringify(data);
        if (currentDataString === lastChatData) {
            return; // ข้อมูลเหมือนเดิม -> ไม่ทำอะไรเลย (เสียงไม่ตัด)
        }
        // ข้อมูลเปลี่ยน -> อัปเดตและวาดหน้าจอใหม่
        lastChatData = currentDataString;

        const chatBox = document.getElementById('admin-chat-messages');
        let html = '';
        
        data.forEach(msg => {
            const isMe = msg.sender_id == <?php echo $_SESSION['user_id']; ?>; // Admin ID
            const align = isMe ? 'justify-end' : 'justify-start';
            
            const bg = isMe 
                ? 'bg-navy-900 text-white rounded-2xl rounded-tr-sm shadow-md' 
                : 'bg-white text-navy-900 rounded-2xl rounded-tl-sm shadow-sm border border-slate-200';
            
            let content = '';
            if (msg.type === 'text') {
                content = `<p class="leading-relaxed text-sm">${msg.message}</p>`;
            } 
            else if (msg.type === 'image') {
                content = `<img src="../uploads/chats/${msg.attachment}" class="rounded-lg max-w-[250px] cursor-pointer hover:opacity-90 transition border border-white/20" onclick="openImageModal(this.src)">`;
            } 
            else if (msg.type === 'voice') {
                content = `<audio controls class="w-64 h-8 mt-1"><source src="../uploads/chats/${msg.attachment}"></audio>`;
            }

            const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const timeColor = isMe ? 'text-slate-300' : 'text-slate-400';

            html += `
                <div class="flex ${align} mb-3 group">
                    <div class="max-w-[75%]">
                        <div class="${bg} px-4 py-3 relative">
                            ${content}
                        </div>
                        <div class="text-[10px] ${timeColor} text-right mt-1 opacity-70 group-hover:opacity-100 transition pr-1">
                            ${time}
                        </div>
                    </div>
                </div>
            `;
        });
        
        chatBox.innerHTML = html;
        chatBox.scrollTop = chatBox.scrollHeight;
    });
}

// 4. ส่งข้อความ (รองรับ Text, Image, Voice)
async function sendAdminMessage(e, voiceBlob = null, fileExt = 'webm') {
    if(e) e.preventDefault();
    
    const input = document.getElementById('admin-chat-input');
    const fileInput = document.getElementById('admin-chat-img');
    const message = input.value.trim();
    
    if (!message && !fileInput.files[0] && !voiceBlob) return;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', currentPartnerId);

    if (voiceBlob) {
        formData.append('file', voiceBlob, 'voice.' + fileExt);
        formData.append('msg_type', 'voice');
    } else if (fileInput.files[0]) {
        formData.append('file', fileInput.files[0]);
        formData.append('msg_type', 'image');
    } else {
        formData.append('message', message);
        formData.append('msg_type', 'text');
    }

    await fetch(apiBase, { method: 'POST', body: formData });
    
    input.value = '';
    clearAdminImage();
    lastChatData = ''; // Reset เพื่อให้โหลดใหม่ทันที
    fetchAdminMessages();
}

function previewAdminImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('#admin-img-preview img').src = e.target.result;
            document.getElementById('admin-img-preview').classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
function clearAdminImage() {
    document.getElementById('admin-chat-img').value = '';
    document.getElementById('admin-img-preview').classList.add('hidden');
}

// 5. บันทึกเสียง (Mic Logic)
let mediaRecorder;
let audioChunks = [];
let isRecording = false;

document.getElementById('mic-btn').addEventListener('click', async () => {
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            // ตรวจสอบนามสกุลไฟล์ที่เครื่องรองรับ (Safari = mp4, Chrome = webm)
            let mimeType = 'audio/webm';
            if (MediaRecorder.isTypeSupported('audio/mp4')) {
                mimeType = 'audio/mp4';
            }
            
            mediaRecorder = new MediaRecorder(stream, { mimeType: mimeType });
            mediaRecorder.start();
            isRecording = true;
            
            document.getElementById('recording-status').classList.remove('hidden');
            document.getElementById('mic-btn').classList.add('text-red-500', 'bg-red-50');
            
            mediaRecorder.ondataavailable = e => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };
            
            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: mimeType });
                const ext = mimeType.includes('mp4') ? 'mp4' : 'webm';
                sendAdminMessage(null, audioBlob, ext);
                audioChunks = [];
                stream.getTracks().forEach(track => track.stop());
            };
        } catch (err) { 
            console.error(err);
            alert('ไม่สามารถเข้าถึงไมโครโฟน หรืออุปกรณ์ไม่รองรับ'); 
        }
    } else {
        mediaRecorder.stop();
        isRecording = false;
        document.getElementById('recording-status').classList.add('hidden');
        document.getElementById('mic-btn').classList.remove('text-red-500', 'bg-red-50');
    }
});

// Image Popup Logic
function openImageModal(src) {
    const modal = document.getElementById('image-modal');
    document.getElementById('modal-img').src = src;
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        document.getElementById('modal-content').classList.add('scale-100');
    }, 10);
}
function closeImageModal() {
    const modal = document.getElementById('image-modal');
    modal.classList.remove('opacity-100');
    document.getElementById('modal-content').classList.remove('scale-100');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('modal-img').src = '';
    }, 300);
}

// Auto Refresh Interval
setInterval(() => {
    fetchUsers();
    if(currentPartnerId) fetchAdminMessages();
}, 3000);

// Initial Load
fetchUsers();
</script>

<?php require_once 'footer.php'; ?>