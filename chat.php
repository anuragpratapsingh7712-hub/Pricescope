<?php
require 'config.php';
require 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Analyst - PriceScope Pro</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="chat-console">
        <div style="background: rgba(0, 242, 255, 0.1); padding: 15px; border-bottom: 1px solid var(--neon-cyan); font-family: monospace; display: flex; justify-content: space-between; align-items: center;">
            <span>/// BLU_AI_SYSTEM_ONLINE_V2.0</span>
            <span style="font-size: 20px;">🐧</span>
        </div>
        
        <div class="chat-history" id="chat-box">
            <div class="msg ai">
                <div class="penguin-circle" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 20px; background: rgba(0,0,0,0.5);">🐧</div>
                <div class="chat-bubble">Systems nominal. I am scanning the marketplaces. What product shall we analyze?</div>
            </div>
        </div>
        
        <div style="padding: 20px; border-top: 1px solid #334155; display: flex; gap: 10px; background: rgba(0,0,0,0.2);">
            <input type="text" id="user-input" placeholder="Enter command..." onkeypress="handleEnter(event)">
            <button onclick="sendMessage()" class="btn btn-primary" style="padding: 0 25px;">SEND</button>
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
                addMessage("Connection Error. Please try again.", 'ai');
            }
        }

        function addMessage(text, sender, isLoading = false) {
            const div = document.createElement('div');
            div.className = `msg ${sender}`;
            if (isLoading) div.id = 'loading-' + Date.now();
            
            let avatar = '';
            if (sender === 'ai') {
                avatar = `<div class="penguin-circle" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 20px; background: rgba(0,0,0,0.5);">🐧</div>`;
            }

            // Format Markdown-like bolding
            let formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            formattedText = formattedText.replace(/\n/g, '<br>');

            div.innerHTML = `
                ${avatar}
                <div class="chat-bubble">${formattedText}</div>
            `;
            
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
            return div.id;
        }
    </script>
</body>
</html>
