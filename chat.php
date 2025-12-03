<?php
require 'config.php';
require 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope - Chat with Blu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .chat-box {
            height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        .message {
            margin-bottom: 15px;
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
        }
        .message.user {
            background: #4DA8DA;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 2px;
        }
        .message.bot {
            background: #e9ecef;
            color: #333;
            margin-right: auto;
            border-bottom-left-radius: 2px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <span class="blu-mascot me-2" style="font-size: 1.5rem;">🐧</span>
                        <h5 class="mb-0">Chat with Blu (AI Assistant)</h5>
                    </div>
                    <div class="card-body">
                        <div id="chat-box" class="chat-box mb-3">
                            <div class="message bot">
                                Hello! I'm Blu. I have access to your product database. Ask me anything about prices or products! 🛍️
                            </div>
                        </div>
                        
                        <form id="chat-form" class="d-flex gap-2">
                            <input type="text" id="user-input" class="form-control" placeholder="Ask about prices, comparisons..." required>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const chatForm = document.getElementById('chat-form');
        const userInput = document.getElementById('user-input');

        function appendMessage(text, sender) {
            const div = document.createElement('div');
            div.className = `message ${sender}`;
            div.textContent = text;
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (!message) return;

            appendMessage(message, 'user');
            userInput.value = '';

            // Show loading
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'message bot text-muted';
            loadingDiv.textContent = 'Thinking...';
            chatBox.appendChild(loadingDiv);

            try {
                const response = await fetch('api_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });
                const data = await response.json();
                
                chatBox.removeChild(loadingDiv);
                
                if (data.reply) {
                    appendMessage(data.reply, 'bot');
                } else {
                    appendMessage("Error: " + (data.error || "Unknown error"), 'bot');
                }
            } catch (err) {
                chatBox.removeChild(loadingDiv);
                appendMessage("Network Error", 'bot');
            }
        });
    </script>
</body>
</html>
