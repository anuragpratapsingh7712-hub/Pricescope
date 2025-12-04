<style>
    /* Neon Navbar Styles */
    :root {
        --nav-bg: rgba(15, 23, 42, 0.8);
        --nav-border: rgba(255, 255, 255, 0.1);
        --nav-text: #e2e8f0;
        --nav-hover: #00f2ff;
        --nav-active: rgba(0, 242, 255, 0.1);
    }

    .neon-navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
        background: var(--nav-bg);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--nav-border);
        position: sticky;
        top: 0;
        z-index: 1000;
        font-family: 'Outfit', 'Segoe UI', sans-serif;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .neon-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        letter-spacing: -0.5px;
    }

    .neon-brand span {
        color: var(--nav-hover);
        text-shadow: 0 0 10px rgba(0, 242, 255, 0.5);
    }

    .neon-brand img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid var(--nav-hover);
    }

    .neon-nav-links {
        display: flex;
        gap: 1rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .neon-nav-item a {
        text-decoration: none;
        color: var(--nav-text);
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .neon-nav-item a:hover,
    .neon-nav-item a.active {
        color: var(--nav-hover);
        background: var(--nav-active);
        box-shadow: 0 0 15px rgba(0, 242, 255, 0.1);
        transform: translateY(-1px);
    }

    .neon-nav-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .btn-logout {
        padding: 0.5rem 1.2rem;
        border: 1px solid var(--nav-border);
        background: transparent;
        color: #94a3b8;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s;
    }

    .btn-logout:hover {
        border-color: #ef4444;
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .neon-navbar {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }
        .neon-nav-links {
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
        }
    }
</style>

<nav class="neon-navbar">
    <a href="dashboard.php" class="neon-brand">
        <img src="mascot.jpg" alt="Logo">
        PRICESCOPE <span>PRO</span>
    </a>

    <ul class="neon-nav-links">
        <li class="neon-nav-item"><a href="dashboard.php">📊 Dashboard</a></li>
        <li class="neon-nav-item"><a href="search.php">🔍 Search</a></li>
        <li class="neon-nav-item"><a href="add_product.php">➕ Add Product</a></li>
        <li class="neon-nav-item"><a href="watchlist.php">❤️ Watchlist</a></li>
        <li class="neon-nav-item"><a href="chat.php">✨ AI Chat</a></li>
    </ul>

    <div class="neon-nav-right">
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <a href="admin_seed.php" class="neon-nav-item" style="color: #f59e0b; font-size: 0.9rem; text-decoration: none;">⚙️ Admin</a>
        <?php endif; ?>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
