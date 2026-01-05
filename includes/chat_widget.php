<div class="fixed bottom-6 right-6 z-50">
    <button onclick="toggleChat()" class="bg-green-500 hover:bg-green-600 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center transition transform hover:scale-110 relative">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <span id="chat-badge" class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full hidden">0</span>
    </button>
</div>

<div id="chat-window" class="fixed bottom-24 right-6 w-80 md:w-96 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 hidden flex flex-col overflow-hidden" style="height: 500px;">
    
    <div class="bg-green-500 text-white p-4 flex justify-between items-center shadow-md">
        <div class="font-bold flex items-center gap-2">
            <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
            ติดต่อร้านค้า (Admin)
        </div>
        <button onclick="toggleChat()" class="hover:text-gray-200"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
    </div>

    <div id="chat-messages" class="flex-grow p-4 overflow-y-auto bg-gray-100 space-y-3">
        </div>

    <div class="p-3 bg-white border-t border-gray-200">
        <div id="img-preview" class="hidden mb-2 relative w-fit">
            <img src="" class="h-16 rounded border">
            <button onclick="clearImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">×</button>
        </div>

        <form id="chat-form" class="flex items-center gap-2" onsubmit="sendMessage(event)">
            <label class="cursor-pointer text-gray-400 hover:text-green-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <input type="file" id="chat-img" accept="image/*" class="hidden" onchange="previewImage(this)">
            </label>

            <button type="button" id="mic-btn" class="text-gray-400 hover:text-red-500 transition relative">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
            </button>

            <input type="text" id="chat-input" class="flex-grow border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-green-500" placeholder="พิมพ์ข้อความ...">
            
            <button type="submit" class="bg-green-500 text-white p-2 rounded-full hover:bg-green-600 shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </form>
        
        <div id="recording-status" class="hidden text-xs text-red-500 text-center mt-1 animate-pulse">
            กำลังบันทึกเสียง... (กดอีกครั้งเพื่อส่ง)
        </div>
    </div>
</div>

<script>
const myId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
const apiBase = '<?php echo defined('BASE_URL') ? BASE_URL : '/library_system'; ?>'; // ปรับ Path ให้ตรง
let isChatOpen = false;
let chatInterval;

// --- ฟังก์ชันพื้นฐาน ---
function toggleChat() {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.classList.toggle('hidden');
    isChatOpen = !chatWindow.classList.contains('hidden');
    
    if (isChatOpen) {
        fetchMessages();
        chatInterval = setInterval(fetchMessages, 3000); // Polling ทุก 3 วิ
        document.getElementById('chat-badge').classList.add('hidden');
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
    } else {
        clearInterval(chatInterval);
    }
}

// --- ส่งข้อความ (Text / Image / Voice) ---
async function sendMessage(e, voiceBlob = null) {
    if(e) e.preventDefault();
    
    const input = document.getElementById('chat-input');
    const fileInput = document.getElementById('chat-img');
    const message = input.value.trim();
    
    if (!message && !fileInput.files[0] && !voiceBlob) return;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', 1); // User ส่งหา Admin เสมอ

    if (voiceBlob) {
        formData.append('file', voiceBlob, 'voice.webm');
        formData.append('msg_type', 'voice');
    } else if (fileInput.files[0]) {
        formData.append('file', fileInput.files[0]);
        formData.append('msg_type', 'image');
    } else {
        formData.append('message', message);
        formData.append('msg_type', 'text');
    }

    try {
        await fetch('chat_api.php', { method: 'POST', body: formData });
        input.value = '';
        clearImage();
        fetchMessages(); // โหลดใหม่ทันที
    } catch (err) { console.error(err); }
}

// --- ดึงข้อความ ---
function fetchMessages() {
    fetch('chat_api.php?action=fetch&partner_id=1')
    .then(res => res.json())
    .then(data => {
        const chatBox = document.getElementById('chat-messages');
        let html = '';
        
        data.forEach(msg => {
            const isMe = msg.sender_id == myId;
            const align = isMe ? 'justify-end' : 'justify-start';
            const bg = isMe ? 'bg-green-500 text-white rounded-l-lg rounded-tr-lg' : 'bg-white text-gray-800 rounded-r-lg rounded-tl-lg';
            
            let content = '';
            if (msg.type === 'text') {
                content = `<p class="text-sm">${msg.message}</p>`;
            } else if (msg.type === 'image') {
                content = `<img src="uploads/chats/${msg.attachment}" class="rounded-lg max-w-[150px] cursor-pointer" onclick="window.open(this.src)">`;
            } else if (msg.type === 'voice') {
                content = `<audio controls class="w-48 h-8 mt-1"><source src="uploads/chats/${msg.attachment}" type="audio/webm"></audio>`;
            }

            html += `
                <div class="flex ${align} mb-2">
                    <div class="${bg} p-3 shadow-sm max-w-[80%] break-words">
                        ${content}
                        <div class="text-[10px] ${isMe?'text-green-100':'text-gray-400'} text-right mt-1">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    </div>
                </div>
            `;
        });
        
        chatBox.innerHTML = html;
        // Scroll to bottom ถ้าไม่ได้เลื่อนขึ้นไปอ่านอยู่
        // chatBox.scrollTop = chatBox.scrollHeight; 
    });
}

// --- จัดการรูปภาพ ---
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('#img-preview img').src = e.target.result;
            document.getElementById('img-preview').classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
function clearImage() {
    document.getElementById('chat-img').value = '';
    document.getElementById('img-preview').classList.add('hidden');
}

// --- จัดการเสียง (Voice Recorder) ---
let mediaRecorder;
let audioChunks = [];
let isRecording = false;

document.getElementById('mic-btn').addEventListener('click', async () => {
    if (!isRecording) {
        // เริ่มอัด
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.start();
            isRecording = true;
            document.getElementById('recording-status').classList.remove('hidden');
            document.getElementById('mic-btn').classList.add('text-red-600');

            mediaRecorder.ondataavailable = event => { audioChunks.push(event.data); };
            
            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                sendMessage(null, audioBlob); // ส่งทันทีเมื่อหยุด
                audioChunks = [];
            };
        } catch (err) {
            alert('ไม่สามารถเข้าถึงไมโครโฟนได้');
        }
    } else {
        // หยุดอัดและส่ง
        mediaRecorder.stop();
        isRecording = false;
        document.getElementById('recording-status').classList.add('hidden');
        document.getElementById('mic-btn').classList.remove('text-red-600');
    }
});
</script>