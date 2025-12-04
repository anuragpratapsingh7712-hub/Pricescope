<?php
require 'config.php';
require 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope Pro - Blu AI Chat</title>
    <style>
        /* Base Variables & Overrides for this Page */
        :root { --bg: #020617; --neon-cyan: #00f2ff; }

        /* Global & Body Styles */
        body { 
            background: var(--bg); 
            color: white; 
            font-family: 'Segoe UI', sans-serif; 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            padding-top: 20px; 
            margin: 0;
            height: 100vh;
            box-sizing: border-box;
        }
        
        /* Chat Console */
        .console {
            width: 800px; height: 80vh; background: rgba(10, 15, 30, 0.9); 
            border: 1px solid var(--neon-cyan); border-radius: 15px; 
            display: flex; flex-direction: column;
            box-shadow: 0 0 30px rgba(0, 242, 255, 0.15); overflow: hidden;
            margin-top: 20px;
        }
        
        /* Chat Header */
        .header { 
            background: rgba(0, 242, 255, 0.1); padding: 15px; 
            border-bottom: 1px solid var(--neon-cyan); font-family: monospace; 
            display: flex; justify-content: space-between; align-items: center;
        }
        .chat-area { flex: 1; padding: 20px; overflow-y: auto; }
        
        /* Message Layout */
        .msg { display: flex; margin-bottom: 20px; align-items: start; gap: 10px; }
        .msg.ai { flex-direction: row; }
        .msg.user { flex-direction: row-reverse; }
        
        /* Bubble Styles */
        .bubble { padding: 12px 18px; border-radius: 10px; font-size: 0.95em; line-height: 1.4; max-width: 70%; }
        .ai .bubble { 
            background: rgba(0, 242, 255, 0.1); border: 1px solid var(--neon-cyan); 
            color: var(--neon-cyan); box-shadow: 0 0 10px rgba(0, 242, 255, 0.1); 
        }
        .user .bubble { background: #334155; color: white; }
        
        /* Input Area */
        .input-area { padding: 20px; border-top: 1px solid #334155; display: flex; gap: 10px; background: rgba(0,0,0,0.3); }
        input { flex: 1; background: transparent; border: 1px solid #334155; padding: 12px; color: white; border-radius: 5px; outline: none; }
        input:focus { border-color: var(--neon-cyan); }
        button { background: var(--neon-cyan); border: none; padding: 0 20px; font-weight: bold; cursor: pointer; border-radius: 5px; color: #000; }
        button:hover { background: white; }
        
        /* Avatar */
        .avatar { width: 35px; height: 35px; border-radius: 50%; border: 1px solid var(--neon-cyan); object-fit: cover; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="console">
        <div class="header">
            <span>/// BLU_AI_SYSTEM_ONLINE_V2.0</span>
            <span style="color: var(--neon-cyan);">● CONNECTED</span>
        </div>
        <div class="chat-area" id="chat-box">
            <div class="msg ai">
                <img src="mascot.jpg" class="avatar">
                <div class="bubble">Systems nominal. I am scanning the marketplaces. What product shall we analyze?</div>
            </div>
        </div>
        <div class="input-area">
            <input type="text" id="user-input" placeholder="Enter command..." onkeypress="handleEnter(event)">
            <button onclick="sendMessage()">SEND</button>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');

        function handleEnter(e) {
            if (e.key === 'Enter') sendMessage();
        }

        async function sendMessage() {
            const text = userInput.value.trim();
            if (!text) return;

            // Add User Message
            addMessage(text, 'user');
            userInput.value = '';

            // Loading Indicator
            const loadingId = addMessage('Analyzing market data...', 'ai', true);

            try {
                const response = await fetch('api_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });
                
                const data = await response.json();
                
                // Remove Loading
                const loadingEl = document.getElementById(loadingId);
                if (loadingEl) loadingEl.remove();

                if (data.reply) {
                    addMessage(data.reply, 'ai');
                } else {
                    addMessage("Error: " + (data.error || "Unknown error"), 'ai');
                }
            } catch (err) {
                // Remove Loading
                const loadingEl = document.getElementById(loadingId);
                if (loadingEl) loadingEl.remove();
                
                addMessage("Connection Error. Please try again.", 'ai');
            }
        }

        function addMessage(text, sender, isLoading = false) {
            const div = document.createElement('div');
            div.className = `msg ${sender}`;
            if (isLoading) div.id = 'loading-' + Date.now();
            
            let avatar = '';
            if (sender === 'ai') {
                avatar = `<img src="mascot.jpg" class="avatar">`;
            } else {
                 avatar = `<div style="width:35px;"></div>`; // Spacer for user
            }

            // Format Markdown-like bolding
            let formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            formattedText = formattedText.replace(/\n/g, '<br>');

            div.innerHTML = `
                ${avatar}
                <div class="bubble">${formattedText}</div>
            `;
            
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
            return div.id;
        }
    </script>
</body>
</html>
