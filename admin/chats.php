<?php
require_once 'header.php';
?>

<div class="flex h-[calc(100vh-140px)] bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    
    <div class="w-1/3 border-r border-slate-200 bg-slate-50 flex flex-col">
        <div class="p-5 border-b border-slate-200 font-bold text-navy-900 bg-white flex items-center gap-2">
            <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <span class="font-serif tracking-wide">Customer Chats</span>
        </div>
        <div id="user-list" class="flex-1 overflow-y-auto custom-scrollbar">
            </div>
    </div>

    <div class="w-2/3 flex flex-col bg-slate-100 relative">
        <div id="chat-header" class="px-6 py-4 bg-white border-b border-slate-200 font-bold text-navy-900 shadow-sm hidden z-10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                <span>กำลังคุยกับ: <span id="current-user-name" class="text-gold-600 font-serif text-lg"></span></span>
            </div>
        </div>

        <div id="admin-chat-messages" class="flex-1 p-6 overflow-y-auto space-y-4">
            <div class="flex flex-col items-center justify-center h-full text-slate-400 gap-4">
                <div class="w-20 h-20 bg-slate-200 rounded-full flex items-center justify-center text-3xl">💬</div>
                <p>เลือกรายชื่อทางซ้ายเพื่อเริ่มสนทนา</p>
            </div>
        </div>

        <form id="admin-chat-form" class="p-4 bg-white border-t border-slate-200 hidden" onsubmit="sendAdminMessage(event)">
            <input type="hidden" id="current-partner-id">
            
            <div id="admin-img-preview" class="hidden mb-2 relative w-fit p-2 bg-slate-50 rounded border border-slate-200">
                <img src="" class="h-24 rounded">
                <button type="button" onclick="clearAdminImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow hover:bg-red-600 transition">×</button>
            </div>

            <div class="flex gap-3 items-center">
                <label class="cursor-pointer text-slate-400 hover:text-gold-500 transition p-2 hover:bg-gold-50 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <input type="file" id="admin-chat-img" accept="image/*" class="hidden" onchange="previewAdminImage(this)">
                </label>
                
                <input type="text" id="admin-chat-input" class="flex-grow border border-slate-200 bg-slate-50 rounded-full px-5 py-3 focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500 focus:bg-white transition" placeholder="พิมพ์ข้อความตอบกลับ...">
                
                <button type="submit" class="bg-navy-900 text-white px-6 py-3 rounded-full hover:bg-gold-500 hover:text-navy-900 shadow-md transition transform active:scale-95 font-bold">
                    ส่ง
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPartnerId = null;
const apiBase = '../chat_api.php';

// 1. โหลดรายชื่อผู้ใช้
function fetchUsers() {
    fetch(apiBase + '?action=get_users')
    .then(res => res.json())
    .then(users => {
        const list = document.getElementById('user-list');
        let html = '';
        users.forEach(u => {
            // Style เมื่อเลือก / ไม่เลือก
            const activeClass = (u.id == currentPartnerId) 
                ? 'bg-navy-50 border-l-4 border-gold-500 shadow-inner' 
                : 'hover:bg-white border-l-4 border-transparent hover:border-slate-200';
            
            const badge = u.unread > 0 
                ? `<span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full ml-auto shadow-sm animate-pulse">${u.unread}</span>` 
                : '';
            
            const avatarColor = (u.id == currentPartnerId) ? 'bg-navy-900 text-gold-500' : 'bg-slate-200 text-slate-500';

            html += `
                <div onclick="selectUser(${u.id}, '${u.fullname}')" class="p-4 border-b border-slate-100 cursor-pointer flex items-center gap-3 transition duration-200 ${activeClass}">
                    <div class="w-10 h-10 rounded-full ${avatarColor} flex items-center justify-center font-bold text-lg shadow-sm">
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
    currentPartnerId = id;
    document.getElementById('current-partner-id').value = id;
    document.getElementById('current-user-name').innerText = name;
    
    document.getElementById('chat-header').classList.remove('hidden');
    document.getElementById('admin-chat-form').classList.remove('hidden');
    
    fetchAdminMessages();
    fetchUsers(); 
}

// 3. ดึงข้อความ
function fetchAdminMessages() {
    if (!currentPartnerId) return;

    fetch(`${apiBase}?action=fetch&partner_id=${currentPartnerId}`)
    .then(res => res.json())
    .then(data => {
        const chatBox = document.getElementById('admin-chat-messages');
        let html = '';
        
        data.forEach(msg => {
            const isMe = msg.sender_id == <?php echo $_SESSION['user_id']; ?>;
            const align = isMe ? 'justify-end' : 'justify-start';
            // สีกล่องข้อความ: Admin=Navy/Gold, User=White/Slate
            const bg = isMe ? 'bg-navy-900 text-white rounded-br-none' : 'bg-white text-navy-900 rounded-bl-none border border-slate-200';
            const timeColor = isMe ? 'text-slate-400' : 'text-slate-400';
            
            let content = '';
            if (msg.type === 'text') content = `<p class="leading-relaxed">${msg.message}</p>`;
            else if (msg.type === 'image') content = `<img src="../uploads/chats/${msg.attachment}" class="rounded-lg max-w-[200px] cursor-pointer hover:opacity-90 transition" onclick="window.open(this.src)">`;
            else if (msg.type === 'voice') content = `<audio controls class="w-56 h-8 mt-1"><source src="../uploads/chats/${msg.attachment}" type="audio/webm"></audio>`;

            html += `
                <div class="flex ${align} mb-2">
                    <div class="${bg} px-4 py-2.5 rounded-2xl shadow-sm max-w-[75%] text-sm relative group">
                        ${content}
                        <div class="text-[9px] ${timeColor} text-right mt-1 opacity-70 group-hover:opacity-100 transition">
                            ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        </div>
                    </div>
                </div>
            `;
        });
        
        chatBox.innerHTML = html;
    });
}

// 4. ส่งข้อความ
async function sendAdminMessage(e) {
    e.preventDefault();
    const input = document.getElementById('admin-chat-input');
    const fileInput = document.getElementById('admin-chat-img');
    const message = input.value.trim();
    
    if (!message && !fileInput.files[0]) return;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', currentPartnerId);

    if (fileInput.files[0]) {
        formData.append('file', fileInput.files[0]);
        formData.append('msg_type', 'image');
    } else {
        formData.append('message', message);
        formData.append('msg_type', 'text');
    }

    await fetch(apiBase, { method: 'POST', body: formData });
    input.value = '';
    clearAdminImage();
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

setInterval(() => {
    fetchUsers();
    if(currentPartnerId) fetchAdminMessages();
}, 3000);

fetchUsers();
</script>

<?php require_once 'footer.php'; ?>