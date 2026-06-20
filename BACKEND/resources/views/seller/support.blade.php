<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Support Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-scroll::-webkit-scrollbar { width: 6px; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex flex-col font-sans">
    
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-3 sm:p-4 shadow-md flex items-center gap-2 sm:gap-3">
        <!-- Back Button -->
        <a href="/seller/dashboard" class="p-1 sm:p-2 -ml-1 text-blue-100 hover:text-white transition-colors shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        
        <!-- Profile / Title -->
        <div class="flex-1 flex items-center gap-2.5 sm:gap-3 min-w-0">
            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white text-blue-600 rounded-full flex items-center justify-center font-bold text-base sm:text-xl shrink-0">
                S
            </div>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-lg font-semibold truncate leading-tight">Bantuan Admin</h1>
                <p class="text-[10px] sm:text-xs text-blue-100 truncate">Login: {{ $seller->name }}</p>
            </div>
        </div>
        
        <!-- Status Badge -->
        <div class="shrink-0 ml-1">
            <span class="px-2.5 py-1 text-[10px] sm:text-xs font-bold rounded-full whitespace-nowrap {{ $room->status === 'open' ? 'bg-green-400 text-white' : 'bg-red-400 text-white' }}">
                {{ strtoupper($room->status) }}
            </span>
        </div>
    </nav>

    <!-- Chat Container -->
    <main class="flex-1 max-w-4xl w-full mx-auto p-4 flex flex-col h-full overflow-hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden">
            
            <!-- Messages Area -->
            <div id="chat-box" class="flex-1 p-4 overflow-y-auto chat-scroll space-y-4 bg-slate-50">
                <!-- Messages will be injected here -->
                <div class="text-center text-xs text-gray-400 my-4">Mulai percakapan dengan tim Admin StreamCart</div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white border-t border-gray-100">
                <form id="chat-form" class="flex space-x-2 relative" onsubmit="sendMessage(event)">
                    <input type="text" id="message-input" class="flex-1 border border-gray-300 rounded-full px-5 py-3 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all text-sm" placeholder="Ketik pesan Anda..." autocomplete="off" {{ $room->status === 'closed' ? 'disabled' : '' }}>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded-full px-6 py-2 transition-colors flex items-center justify-center disabled:opacity-50" {{ $room->status === 'closed' ? 'disabled' : '' }}>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        const roomId = {{ $room->id }};
        const sellerId = {{ $seller->id }};
        const chatBox = document.getElementById('chat-box');
        const messageInput = document.getElementById('message-input');

        let lastMessageCount = 0;

        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        async function fetchMessages() {
            try {
                const response = await fetch(`/api/support/rooms/${roomId}/messages?role=seller`);
                const data = await response.json();
                
                if (data.messages.length !== lastMessageCount) {
                    renderMessages(data.messages);
                    lastMessageCount = data.messages.length;
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        function renderMessages(messages) {
            chatBox.innerHTML = ''; // Clear box
            messages.forEach(msg => {
                const isMine = msg.sender_role === 'seller';
                const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                const html = `
                    <div class="flex w-full ${isMine ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-[75%] rounded-2xl px-4 py-2 text-sm shadow-sm ${isMine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-white border border-gray-100 text-gray-800 rounded-bl-sm'}">
                            ${!isMine ? '<p class="text-xs font-bold text-blue-600 mb-1">Admin</p>' : ''}
                            <p>${msg.message}</p>
                            <p class="text-[10px] mt-1 text-right ${isMine ? 'text-blue-100' : 'text-gray-400'}">${time} ${isMine ? (msg.is_read ? '✓✓' : '✓') : ''}</p>
                        </div>
                    </div>
                `;
                chatBox.innerHTML += html;
            });
        }

        async function sendMessage(e) {
            e.preventDefault();
            const text = messageInput.value.trim();
            if (!text) return;

            messageInput.value = ''; // clear immediately
            
            try {
                await fetch(`/api/support/rooms/${roomId}/messages`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        sender_id: sellerId,
                        sender_role: 'seller',
                        message: text
                    })
                });
                fetchMessages(); // refresh instantly
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Gagal mengirim pesan');
            }
        }

        // Poll every 3 seconds
        setInterval(fetchMessages, 3000);
        fetchMessages(); // initial load
    </script>
</body>
</html>
