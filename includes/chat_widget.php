<div class="fixed bottom-6 right-6 z-50">
    <button onclick="toggleChat()" class="group bg-[#0f172a] hover:bg-[#1e293b] text-[#fbbf24] w-16 h-16 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-105 hover:-translate-y-1 border-2 border-[#d97706]">
        <svg class="w-8 h-8 group-hover:animate-wiggle" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
        
        <span id="chat-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full border-2 border-white hidden animate-bounce shadow-sm">0</span>
    </button>
</div>

<div id="chat-window" class="fixed bottom-24 right-6 w-80 md:w-96 bg-[#f8fafc] rounded-2xl shadow-2xl border border-slate-200 z-50 hidden flex flex-col overflow-hidden font-sans transform origin-bottom-right transition-all duration-300 scale-95 opacity-0 h-[500px]">
    
    <div class="bg-gradient-to-r from-[#0f172a] to-[#1e293b] text-white p-4 flex justify-between items-center shadow-md relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-[#fbbf24] rounded-full opacity-10 blur-xl"></div>
        
        <div class="font-bold flex items-center gap-3 relative z-10">
            <div class="relative">
                <div class="w-10 h-10 bg-white text-[#0f172a] rounded-full flex items-center justify-center font-serif font-bold text-xl border-2 border-[#fbbf24] shadow-sm">
                    L
                </div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-[#0f172a]"></div>
            </div>
            <div>
                <div class="text-sm font-serif tracking-wide text-[#fbbf24]">THE LIBRARY</div>
                <div class="text-[10px] text-slate-300 opacity-80">สอบถามบรรณารักษ์</div>
            </div>
        </div>
        
        <button onclick="toggleChat()" class="text-slate-400 hover:text-white transition transform hover:rotate-90 relative z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div id="chat-messages" class="flex-grow p-4 overflow-y-auto space-y-4 custom-scrollbar bg-[#f1f5f9]">
        </div>

    <div class="p-3 bg-white border-t border-slate-100">
        <div id="img-preview" class="hidden mb-2 relative w-fit mx-auto bg-slate-50 p-2 rounded-lg border border-slate-200">
            <img src="" class="h-20 rounded shadow-sm object-cover">
            <button onclick="clearImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-md hover:bg-red-600 transition">×</button>
        </div>

        <form id="chat-form" class="flex items-end gap-2" onsubmit="sendMessage(event)">
            <label class="cursor-pointer text-slate-400 hover:text-[#d97706] hover:bg-[#fffbeb] p-2 rounded-full transition duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <input type="file" id="chat-img" accept="image/*" class="hidden" onchange="previewImage(this)">
            </label>

            <div class="flex-grow relative">
                <input type="text" id="chat-input" class="w-full bg-slate-100 border border-transparent rounded-full px-4 py-2.5 text-sm text-[#0f172a] focus:outline-none focus:bg-white focus:border-[#fbbf24] focus:ring-1 focus:ring-[#fbbf24] transition placeholder-slate-400" placeholder="พิมพ์ข้อความ...">
            </div>

            <button type="button" id="mic-btn" class="text-slate-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-full transition duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
            </button>

            <button type="submit" class="bg-[#0f172a] hover:bg-[#fbbf24] hover:text-[#0f172a] text-white p-2.5 rounded-full shadow-md transition transform active:scale-90 duration-200">
                <svg class="w-5 h-5 transform rotate-90" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </form>
        
        <div id="recording-status" class="hidden text-xs text-red-500 text-center mt-2 animate-pulse font-bold bg-red-50 py-1.5 rounded-lg border border-red-100">
            🎙️ กำลังบันทึกเสียง... (กดอีกครั้งเพื่อส่ง)
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
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    @keyframes wiggle { 0%, 100% { transform: rotate(-3deg); } 50% { transform: rotate(3deg); } }
    .group:hover .group-hover\:animate-wiggle { animation: wiggle 0.3s ease-in-out infinite; }
    #image-modal:not(.hidden) { opacity: 1; }
    #image-modal:not(.hidden) #modal-content { transform: scale(100%); }
</style>

<script>
const myId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
const apiBase = '<?php echo defined('BASE_URL') ? BASE_URL : '/library_system'; ?>'; 
let isChatOpen = false;
let chatInterval;
// ✅ เพิ่มตัวแปรสำหรับจำข้อมูลล่าสุด (ป้องกันการรีเฟรชหน้าจอถ้าข้อมูลไม่เปลี่ยน)
let lastChatData = ''; 

function toggleChat() {
    const chatWindow = document.getElementById('chat-window');
    const badge = document.getElementById('chat-badge');
    
    if (chatWindow.classList.contains('hidden')) {
        chatWindow.classList.remove('hidden');
        setTimeout(() => {
            chatWindow.classList.remove('scale-95', 'opacity-0');
            chatWindow.classList.add('scale-100', 'opacity-100');
        }, 10);
        
        isChatOpen = true;
        fetchMessages();
        chatInterval = setInterval(fetchMessages, 3000);
        badge.classList.add('hidden');
        badge.innerText = '0';
        setTimeout(scrollToBottom, 200);
    } else {
        chatWindow.classList.remove('scale-100', 'opacity-100');
        chatWindow.classList.add('scale-95', 'opacity-0');
        setTimeout(() => chatWindow.classList.add('hidden'), 300);
        
        isChatOpen = false;
        clearInterval(chatInterval);
    }
}

async function sendMessage(e, voiceBlob = null, fileExt = 'webm') {
    if(e) e.preventDefault();
    const input = document.getElementById('chat-input');
    const fileInput = document.getElementById('chat-img');
    const message = input.value.trim();
    
    if (!message && !fileInput.files[0] && !voiceBlob) return;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', 1);

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

    try {
        const baseUrl = apiBase.endsWith('/') ? apiBase : apiBase + '/';
        await fetch(baseUrl + 'chat_api.php', { method: 'POST', body: formData });
        
        input.value = '';
        clearImage();
        // Reset lastChatData เพื่อให้ระบบรู้ว่าต้องอัปเดตทันที
        lastChatData = ''; 
        fetchMessages();
    } catch (err) { console.error(err); }
}

function fetchMessages() {
    const baseUrl = apiBase.endsWith('/') ? apiBase : apiBase + '/';
    
    fetch(baseUrl + 'chat_api.php?action=fetch&partner_id=1')
    .then(res => res.json())
    .then(data => {
        // ✅ ตรวจสอบว่าข้อมูลเปลี่ยนไปหรือไม่
        const currentDataString = JSON.stringify(data);
        if (currentDataString === lastChatData) {
            return; // ถ้าข้อมูลเหมือนเดิม ให้หยุดทำงาน (ไม่แตะต้อง DOM) เพื่อให้เสียงไม่ตัด
        }
        // ถ้าข้อมูลเปลี่ยน ให้อัปเดตค่าล่าสุด
        lastChatData = currentDataString;

        const chatBox = document.getElementById('chat-messages');
        const badge = document.getElementById('chat-badge');
        let html = '';
        let unreadCount = 0;
        
        const uploadsPath = baseUrl + 'uploads/chats/';
        
        data.forEach(msg => {
            const isMe = msg.sender_id == myId;
            const align = isMe ? 'justify-end' : 'justify-start';
            const bubbleColor = isMe 
                ? 'bg-[#0f172a] text-white shadow-md border border-[#0f172a]' 
                : 'bg-white text-[#0f172a] shadow-sm border border-slate-200';
            const rounded = isMe ? 'rounded-2xl rounded-tr-sm' : 'rounded-2xl rounded-tl-sm';
            
            if (!isMe && msg.is_read == 0) unreadCount++;

            let content = '';
            if (msg.type === 'text') {
                content = `<p class="leading-relaxed">${msg.message}</p>`;
            } 
            else if (msg.type === 'image') {
                content = `<img src="${uploadsPath}${msg.attachment}" class="rounded-lg max-w-[180px] cursor-pointer hover:opacity-90 transition border border-white/20" onclick="openImageModal(this.src)">`;
            } 
            else if (msg.type === 'voice') {
                content = `<audio controls class="w-48 h-8 mt-1"><source src="${uploadsPath}${msg.attachment}"></audio>`;
            }

            const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const timeColor = isMe ? 'text-slate-300' : 'text-slate-400';

            html += `
                <div class="flex ${align} mb-3 items-end gap-2 group">
                    ${!isMe ? '<div class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-xs font-bold text-[#0f172a] overflow-hidden shadow-sm flex-shrink-0"><span class="font-serif">L</span></div>' : ''}
                    <div class="flex flex-col ${isMe?'items-end':'items-start'} max-w-[80%]">
                        <div class="${bubbleColor} px-4 py-2.5 ${rounded} text-sm break-words relative">
                            ${content}
                        </div>
                        <span class="text-[10px] ${timeColor} mt-1 opacity-70 group-hover:opacity-100 transition">${time}</span>
                    </div>
                </div>
            `;
        });
        
        // อัปเดตหน้าจอเฉพาะเมื่อข้อมูลเปลี่ยน
        chatBox.innerHTML = html;
        
        // ถ้าเป็นการเปิดครั้งแรก หรือมีการส่งข้อความใหม่ ให้เลื่อนลงล่างสุด
        if (isChatOpen) {
            // เช็คว่า User กำลังดูอยู่ด้านล่างหรือไม่ (ถ้าดูข้อความเก่าอยู่ ไม่ควรเลื่อน)
            // แต่เพื่อความง่าย ให้เลื่อนลงล่างเสมอเมื่อมีข้อความใหม่
            scrollToBottom(); 
        }

        if (!isChatOpen && unreadCount > 0) {
            badge.classList.remove('hidden');
            badge.innerText = unreadCount;
        }
    });
}

function scrollToBottom() {
    const chatBox = document.getElementById('chat-messages');
    chatBox.scrollTop = chatBox.scrollHeight;
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.querySelector('#img-preview img').src = e.target.result;
            document.getElementById('img-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function clearImage() {
    document.getElementById('chat-img').value = '';
    document.getElementById('img-preview').classList.add('hidden');
}

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

let mediaRecorder;
let audioChunks = [];
let isRecording = false;
document.getElementById('mic-btn').addEventListener('click', async () => {
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            let mimeType = 'audio/webm';
            if (MediaRecorder.isTypeSupported('audio/mp4')) mimeType = 'audio/mp4';
            
            mediaRecorder = new MediaRecorder(stream, { mimeType: mimeType });
            mediaRecorder.start();
            isRecording = true;
            document.getElementById('recording-status').classList.remove('hidden');
            document.getElementById('mic-btn').classList.add('text-red-500', 'bg-red-50');
            mediaRecorder.ondataavailable = e => { if (e.data.size > 0) audioChunks.push(e.data); };
            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: mimeType });
                const ext = mimeType.includes('mp4') ? 'mp4' : 'webm';
                sendMessage(null, audioBlob, ext);
                audioChunks = [];
                stream.getTracks().forEach(track => track.stop());
            };
        } catch (err) { alert('ไม่สามารถเข้าถึงไมโครโฟน หรืออุปกรณ์ไม่รองรับ'); }
    } else {
        mediaRecorder.stop();
        isRecording = false;
        document.getElementById('recording-status').classList.add('hidden');
        document.getElementById('mic-btn').classList.remove('text-red-500', 'bg-red-50');
    }
});
</script>