<?php
require 'config.php';
require 'functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// --- DATA FETCHING ---

// 1. Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ?");
$stmt->execute([$userId]);
$totalItems = $stmt->fetchColumn();

// 2. Watchlist Items (for "Trending in your Vault" and "Watchlist View")
$stmt = $pdo->prepare("
    SELECT w.*, p.name as product_name, p.image_url, p.base_price,
    (SELECT price FROM vendor_prices vp WHERE vp.product_id = p.id ORDER BY timestamp DESC LIMIT 1) as current_price
    FROM watchlist w JOIN products p ON w.product_id = p.id 
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$userId]);
$watchlistItems = $stmt->fetchAll();

// Calculate Total Savings Potential (Mock Logic for Demo)
$totalSavings = 0;
foreach ($watchlistItems as $item) {
    if ($item['target_price'] && $item['current_price'] < $item['base_price']) {
        $totalSavings += ($item['base_price'] - $item['current_price']);
    }
}
// Fallback mock if 0
if ($totalSavings == 0) $totalSavings = 12450; 

?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PriceScope</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: { deep: '#020617' }
                }
            }
        }
    </script>
    <style>
        body { 
            background-color: #020617; 
            color: white;
            /* Aurora background simulation */
            background-image: linear-gradient(130deg, #0f172a 0%, #020617 100%);
        }
        
        .aurora-blob {
            position: fixed;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.4;
            animation: pulse 8s infinite alternate;
        }

        .glass-panel {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link.active {
            background: linear-gradient(90deg, rgba(6,182,212,0.1), transparent);
            border-left: 3px solid #06b6d4;
            color: #22d3ee;
            text-shadow: 0 0 10px rgba(34,211,238,0.5);
        }

        .view-section { display: none; }
        .view-section.active { display: block; animation: fadeUp 0.5s ease; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0% { opacity: 0.3; } 100% { opacity: 0.6; } }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <!-- Background Ambience -->
    <div class="aurora-blob top-[-10%] left-[-10%] w-[500px] h-[500px] bg-cyan-600"></div>
    <div class="aurora-blob bottom-[-10%] right-[-10%] w-[600px] h-[600px] bg-purple-700"></div>

    <!-- Sidebar -->
    <aside class="w-64 glass-panel border-r-0 border-white/5 flex flex-col m-4 rounded-3xl z-20 hidden md:flex">
        <div class="p-6 flex items-center gap-3">
            <img src="mascot.jpg" class="w-10 h-10 rounded-full border-2 border-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.5)]" alt="Blu">
            <span class="font-bold text-xl tracking-tight text-white">PriceScope</span>
        </div>
        
        <nav class="flex-1 px-4 space-y-2 mt-4">
            <button onclick="switchView('dashboard')" id="nav-dashboard" class="nav-link active w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-r-lg transition-all text-sm font-medium">
                <i data-lucide="layout-grid" class="w-5 h-5"></i> Dashboard
            </button>
            <button onclick="switchView('search')" id="nav-search" class="nav-link w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-r-lg transition-all text-sm font-medium">
                <i data-lucide="scan-search" class="w-5 h-5"></i> Product Hunt
            </button>
            <button onclick="switchView('watchlist')" id="nav-watchlist" class="nav-link w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-r-lg transition-all text-sm font-medium">
                <i data-lucide="snowflake" class="w-5 h-5"></i> Ice Vault
            </button>
            <button onclick="switchView('chat')" id="nav-chat" class="nav-link w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-r-lg transition-all text-sm font-medium">
                <i data-lucide="bot" class="w-5 h-5"></i> Ask Blu
            </button>
        </nav>
        
        <div class="p-6">
            <button onclick="window.location.href='logout.php'" class="w-full py-2 rounded-xl border border-red-500/30 text-red-400 hover:bg-red-500/10 text-xs font-bold flex items-center justify-center gap-2">
                <i data-lucide="log-out" class="w-3 h-3"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Content -->
    <main class="flex-1 overflow-y-auto relative p-4 lg:p-8">
        
        <!-- DASHBOARD VIEW -->
        <section id="view-dashboard" class="view-section active space-y-8">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white">Command Center</h1>
                    <p class="text-cyan-400 text-sm">Blu is currently tracking <?= $totalItems ?> items for you.</p>
                </div>
                <div class="w-10 h-10 bg-slate-800 rounded-full flex items-center justify-center border border-white/10">
                    <i data-lucide="bell" class="w-5 h-5 text-white"></i>
                </div>
            </header>

            <!-- Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="glass-panel p-6 rounded-3xl relative overflow-hidden group">
                    <div class="absolute -right-10 -top-10 w-32 h-32 bg-cyan-500/20 rounded-full blur-2xl group-hover:bg-cyan-500/30 transition-all"></div>
                    <h3 class="text-slate-400 text-xs uppercase tracking-wider font-bold">Total Savings Potential</h3>
                    <div class="text-4xl font-bold text-white mt-2">₹<?= number_format($totalSavings) ?></div>
                    <p class="text-green-400 text-sm mt-1 flex items-center gap-1"><i data-lucide="trending-up" class="w-3 h-3"></i> +12% this week</p>
                </div>

                <!-- Card 2: Blu's Tip -->
                <div class="glass-panel p-6 rounded-3xl border-cyan-500/30 md:col-span-2 flex items-center gap-6 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-cyan-900/20 to-transparent"></div>
                    <div class="relative w-20 h-20 shrink-0">
                         <img src="mascot.jpg" class="w-full h-full rounded-full object-cover border-2 border-white/20 shadow-lg">
                         <div class="absolute -bottom-1 -right-1 bg-green-500 text-[10px] px-2 py-0.5 rounded-full text-black font-bold">ONLINE</div>
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-cyan-300 font-bold mb-1">Blu's Insight</h3>
                        <p class="text-slate-200 text-sm italic">"The Sony headphones you liked dropped by ₹2,000 at 3 AM. It's a great time to buy before the stock clears!"</p>
                    </div>
                </div>
            </div>

            <h3 class="text-xl font-bold mt-8 mb-4">Trending in your Vault</h3>
            <div class="space-y-4">
                <?php foreach (array_slice($watchlistItems, 0, 3) as $item): ?>
                <div class="glass-panel p-4 rounded-2xl flex items-center gap-4 hover:bg-white/5 transition-colors cursor-pointer" onclick="window.location.href='product.php?id=<?= $item['product_id'] ?>'">
                    <div class="w-16 h-16 bg-white rounded-xl p-2 flex items-center justify-center">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" class="w-10 max-h-full object-contain">
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold"><?= htmlspecialchars($item['product_name']) ?></h4>
                        <p class="text-xs text-slate-400">Target: ₹<?= number_format($item['target_price'] ?? 0) ?></p>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-lg">₹<?= number_format($item['current_price']) ?></div>
                        <?php if ($item['current_price'] < $item['base_price']): ?>
                            <div class="text-xs text-green-400">▼ Drop</div>
                        <?php else: ?>
                            <div class="text-xs text-slate-500">Flat</div>
                        <?php endif; ?>
                    </div>
                    <div class="w-2 h-2 rounded-full bg-yellow-500 shadow-[0_0_10px_orange]"></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($watchlistItems)): ?>
                    <p class="text-slate-500 text-sm">No items in your vault yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- SEARCH VIEW -->
        <section id="view-search" class="view-section h-full flex flex-col justify-center items-center pb-20">
            <div class="w-full max-w-3xl text-center relative">
                <!-- Glow behind input -->
                <div class="absolute inset-0 bg-cyan-500/30 blur-3xl -z-10 rounded-full opacity-50"></div>
                
                <h2 class="text-4xl font-bold mb-8">What are we hunting?</h2>
                
                <form action="add_product.php" method="POST">
                    <div class="glass-panel p-2 rounded-full flex items-center shadow-2xl border border-cyan-500/30">
                        <div class="p-3 bg-cyan-500/10 rounded-full ml-1">
                            <i data-lucide="search" class="text-cyan-400"></i>
                        </div>
                        <input type="text" name="query" placeholder="Paste Amazon Link or Product Name..." class="flex-1 bg-transparent border-none outline-none text-white px-4 py-3 text-lg placeholder-slate-500">
                        <button type="submit" name="search" class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-8 py-3 rounded-full font-bold hover:shadow-[0_0_20px_rgba(6,182,212,0.4)] transition-all">
                            Track It
                        </button>
                    </div>
                </form>
                
                <div class="flex justify-center gap-4 mt-8">
                    <button class="flex flex-col items-center gap-2 group">
                        <div class="w-16 h-16 glass-panel rounded-2xl flex items-center justify-center group-hover:border-cyan-400 transition-colors">
                            <i data-lucide="camera" class="text-slate-300 group-hover:text-cyan-400"></i>
                        </div>
                        <span class="text-xs text-slate-400">Scan Tag</span>
                    </button>
                    <button class="flex flex-col items-center gap-2 group">
                        <div class="w-16 h-16 glass-panel rounded-2xl flex items-center justify-center group-hover:border-purple-400 transition-colors">
                            <i data-lucide="upload" class="text-slate-300 group-hover:text-purple-400"></i>
                        </div>
                        <span class="text-xs text-slate-400">Upload Image</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- WATCHLIST VIEW -->
        <section id="view-watchlist" class="view-section">
            <h2 class="text-2xl font-bold mb-6">The Ice Vault</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($watchlistItems as $item): ?>
                <div class="glass-panel rounded-3xl p-6 relative group hover:-translate-y-2 transition-transform duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-white rounded-2xl">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" class="w-8 h-8 object-contain">
                        </div>
                        <?php if ($item['current_price'] < $item['base_price']): ?>
                            <span class="bg-green-500/20 text-green-400 text-xs font-bold px-2 py-1 rounded-full border border-green-500/20">LOWEST</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-bold text-lg mb-1 truncate"><?= htmlspecialchars($item['product_name']) ?></h3>
                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-2xl font-bold text-cyan-300">₹<?= number_format($item['current_price']) ?></span>
                        <span class="text-sm text-slate-500 line-through">₹<?= number_format($item['base_price']) ?></span>
                    </div>
                    <button onclick="window.location.href='product.php?id=<?= $item['product_id'] ?>'" class="w-full py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/5 font-bold text-sm transition-colors">
                        Analytics
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- CHAT VIEW -->
        <section id="view-chat" class="view-section h-[calc(100vh-100px)]">
            <div class="glass-panel rounded-3xl h-full flex flex-col overflow-hidden border border-cyan-500/20">
                <!-- Chat Header -->
                <div class="p-4 bg-slate-900/50 border-b border-white/5 flex items-center gap-3">
                    <img src="mascot.jpg" class="w-10 h-10 rounded-full border border-cyan-400">
                    <div>
                        <h3 class="font-bold text-white">Blu (AI Analyst)</h3>
                        <p class="text-xs text-green-400 flex items-center gap-1"><span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span> Online</p>
                    </div>
                </div>

                <!-- Messages -->
                <div id="chat-messages" class="flex-1 p-6 overflow-y-auto space-y-6">
                    <div class="flex gap-4">
                        <img src="mascot.jpg" class="w-8 h-8 rounded-full self-end mb-1">
                        <div class="bg-slate-800/80 p-4 rounded-2xl rounded-bl-none max-w-[80%] border border-white/5">
                            <p class="text-sm text-slate-200">Hi! I noticed you were looking at gaming consoles. The PS5 Digital Edition usually drops price on the first Monday of the month. Want me to set a reminder?</p>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="p-4 bg-slate-900/50 border-t border-white/5">
                    <div class="relative">
                        <input type="text" id="chat-input" placeholder="Message Blu..." class="w-full bg-slate-800 rounded-2xl py-3 pl-4 pr-12 text-white outline-none focus:ring-1 focus:ring-cyan-500" onkeypress="handleChatEnter(event)">
                        <button onclick="sendChatMessage()" class="absolute right-2 top-2 p-1.5 bg-cyan-500 rounded-xl text-black hover:scale-105 transition-transform">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <script>
        lucide.createIcons();
        function switchView(viewId) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.getElementById('view-' + viewId).classList.add('active');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            document.getElementById('nav-' + viewId).classList.add('active');
        }

        // Chat Logic
        async function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const text = input.value.trim();
            if (!text) return;

            // Add User Message
            const msgs = document.getElementById('chat-messages');
            msgs.innerHTML += `
                <div class="flex gap-4 flex-row-reverse">
                    <div class="w-8 h-8 bg-cyan-600 rounded-full flex items-center justify-center text-xs font-bold self-end mb-1">ME</div>
                    <div class="bg-cyan-600/20 p-4 rounded-2xl rounded-br-none max-w-[80%] border border-cyan-500/30">
                        <p class="text-sm text-cyan-100">${text}</p>
                    </div>
                </div>
            `;
            input.value = '';
            msgs.scrollTop = msgs.scrollHeight;

            // Mock AI Response (Connect to api_chat.php for real)
            try {
                const response = await fetch('api_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });
                const data = await response.json();
                
                msgs.innerHTML += `
                    <div class="flex gap-4">
                        <img src="mascot.jpg" class="w-8 h-8 rounded-full self-end mb-1">
                        <div class="bg-slate-800/80 p-4 rounded-2xl rounded-bl-none max-w-[80%] border border-white/5">
                            <p class="text-sm text-slate-200">${data.reply || "I'm having trouble connecting to the market data."}</p>
                        </div>
                    </div>
                `;
                msgs.scrollTop = msgs.scrollHeight;
            } catch (e) {
                console.error(e);
            }
        }

        function handleChatEnter(e) {
            if (e.key === 'Enter') sendChatMessage();
        }
    </script>
</body>
</html>
