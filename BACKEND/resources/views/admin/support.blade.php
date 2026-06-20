<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tiket Pengaduan Seller</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-scroll::-webkit-scrollbar { width: 6px; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .active-room { background-color: #eff6ff; border-left: 4px solid #2563eb; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex font-sans overflow-hidden">
    
    <!-- Sidebar: Daftar Tiket -->
    <aside id="sidebar-tickets" class="w-full md:w-1/3 max-w-sm bg-white border-r border-gray-200 flex flex-col h-full shadow-sm z-10">
        <div class="p-4 bg-blue-700 text-white shadow-md relative">
            <a href="{{ route('admin.dashboard') }}" class="absolute top-4 right-4 text-white hover:text-blue-200 bg-white/20 p-1.5 rounded-md transition-colors" title="Kembali ke Dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
            </a>
            <h1 class="text-xl font-bold">Admin Support</h1>
            <p class="text-xs text-blue-200 mt-1">Halo, {{ $admin->name }}</p>
        </div>
        
        <div class="flex-1 overflow-y-auto chat-scroll p-2" id="room-list">
            <div class="text-center text-sm text-gray-400 mt-10">Memuat daftar tiket...</div>
        </div>
    </aside>

    <!-- Main Chat Area -->
    <main id="main-chat" class="hidden md:flex flex-1 flex-col h-full bg-slate-50 relative">
        <!-- Default State (No Room Selected) -->
        <div id="no-room-state" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p>Pilih tiket dari sidebar untuk membalas pesan.</p>
        </div>

        <!-- Active Room State -->
        <div id="active-room-state" class="hidden flex-col h-full w-full">
            <!-- Header -->
            <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center shadow-sm">
                <div class="flex items-center gap-3">
                    <button id="back-to-tickets-btn" class="md:hidden p-2 -ml-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors" onclick="showTicketsList()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800" id="header-seller-name">Seller Name</h2>
                        <p class="text-xs text-gray-500">Tiket ID: #<span id="header-room-id"></span></p>
                    </div>
                </div>
                <div class="text-sm font-semibold text-gray-500" id="header-status">
                    Status: OPEN
                </div>
            </div>

            <!-- Messages -->
            <div id="chat-box" class="flex-1 p-6 overflow-y-auto chat-scroll space-y-4">
                <!-- Messages injected here -->
            </div>

            <!-- Input -->
            <div class="p-4 bg-white border-t border-gray-200">
                <form id="chat-form" class="flex space-x-3" onsubmit="sendMessage(event)">
                    <input type="text" id="message-input" class="flex-1 border border-gray-300 rounded-full px-5 py-3 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm bg-gray-50" placeholder="Ketik balasan untuk Seller..." autocomplete="off">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded-full px-8 py-2 font-medium transition-colors shadow-sm">
                        Kirim
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        const adminId = {{ $admin->id }};
        let currentRoomId = null;
        let lastMessageCount = 0;
        
        const roomListEl = document.getElementById('room-list');
        const chatBox = document.getElementById('chat-box');
        const noRoomState = document.getElementById('no-room-state');
        const activeRoomState = document.getElementById('active-room-state');
        const messageInput = document.getElementById('message-input');

        // ==== ROOM LIST POLLING ====
        async function fetchRooms() {
            try {
                const res = await fetch('/api/support/rooms');
                const data = await res.json();
                renderRooms(data.rooms);
            } catch (e) {
                console.error(e);
            }
        }

        function renderRooms(rooms) {
            roomListEl.innerHTML = '';
            if (rooms.length === 0) {
                roomListEl.innerHTML = '<div class="text-center text-sm text-gray-400 mt-10">Tidak ada tiket.</div>';
                return;
            }

            rooms.forEach(room => {
                const isActive = room.id === currentRoomId;
                const time = new Date(room.updated_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                const html = `
                    <div onclick="selectRoom(${room.id}, '${room.seller.name}', '${room.status}')" class="p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors ${isActive ? 'active-room' : ''}">
                        <div class="flex justify-between items-start mb-1">
                            <h3 class="font-semibold text-gray-800 truncate">${room.seller.name}</h3>
                            <span class="text-xs text-gray-400 whitespace-nowrap ml-2">${time}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs ${room.status === 'open' ? 'text-green-600' : 'text-red-500'} font-medium">Status: ${room.status.toUpperCase()}</p>
                            ${room.unread_count > 0 && !isActive ? `<span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">${room.unread_count}</span>` : ''}
                        </div>
                    </div>
                `;
                roomListEl.innerHTML += html;
            });
        }

        // ==== ROOM SELECTION ====
        function selectRoom(roomId, sellerName, status) {
            currentRoomId = roomId;
            lastMessageCount = 0;
            chatBox.innerHTML = '';
            
            document.getElementById('header-seller-name').innerText = sellerName;
            document.getElementById('header-room-id').innerText = roomId;
            document.getElementById('header-status').innerText = 'Status: ' + status.toUpperCase();
            
            noRoomState.classList.add('hidden');
            activeRoomState.classList.remove('hidden');
            activeRoomState.classList.add('flex');

            if(window.innerWidth < 768) {
                document.getElementById('sidebar-tickets').classList.add('hidden');
                document.getElementById('main-chat').classList.remove('hidden');
                document.getElementById('main-chat').classList.add('flex');
            }

            fetchRooms(); // refresh UI sidebar
            fetchMessages();
        }

        function showTicketsList() {
            document.getElementById('sidebar-tickets').classList.remove('hidden');
            document.getElementById('main-chat').classList.add('hidden');
            document.getElementById('main-chat').classList.remove('flex');
            currentRoomId = null;
        }

        // ==== MESSAGES POLLING ====
        async function fetchMessages() {
            if (!currentRoomId) return;
            try {
                const res = await fetch(`/api/support/rooms/${currentRoomId}/messages?role=admin`);
                const data = await res.json();
                
                if (data.messages.length !== lastMessageCount) {
                    renderMessages(data.messages);
                    lastMessageCount = data.messages.length;
                    scrollToBottom();
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderMessages(messages) {
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                const isMine = msg.sender_role === 'admin';
                const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                const html = `
                    <div class="flex w-full ${isMine ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-[70%] rounded-2xl px-5 py-3 text-sm shadow-sm ${isMine ? 'bg-white border border-gray-200 text-gray-800 rounded-br-sm' : 'bg-blue-50 text-blue-900 border border-blue-100 rounded-bl-sm'}">
                            ${!isMine ? `<p class="text-xs font-bold text-blue-600 mb-1">${msg.sender.name} (Seller)</p>` : ''}
                            <p>${msg.message}</p>
                            <p class="text-[10px] mt-1 text-right ${isMine ? 'text-gray-400' : 'text-blue-400'}">${time} ${isMine ? (msg.is_read ? '✓✓' : '✓') : ''}</p>
                        </div>
                    </div>
                `;
                chatBox.innerHTML += html;
            });
        }

        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // ==== SEND MESSAGE ====
        async function sendMessage(e) {
            e.preventDefault();
            if (!currentRoomId) return;

            const text = messageInput.value.trim();
            if (!text) return;

            messageInput.value = '';
            
            try {
                await fetch(`/api/support/rooms/${currentRoomId}/messages`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        sender_id: adminId,
                        sender_role: 'admin',
                        message: text
                    })
                });
                fetchMessages();
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Gagal mengirim pesan');
            }
        }

        // Poll rooms every 5s, messages every 3s
        setInterval(fetchRooms, 5000);
        setInterval(() => {
            if (currentRoomId) fetchMessages();
        }, 3000);
        
        fetchRooms();
    </script>
</body>
</html>
