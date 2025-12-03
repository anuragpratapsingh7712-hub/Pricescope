<nav class="navbar navbar-expand-lg navbar-light navbar-custom mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold tracking-tight" href="dashboard.php">
            PRICESCOPE <span class="text-primary">PRO</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="search.php">🔍 Search</a></li>
                <li class="nav-item"><a class="nav-link" href="add_product.php">➕ Add Product</a></li>
                <li class="nav-item"><a class="nav-link" href="watchlist.php">❤️ Watchlist</a></li>
                <li class="nav-item"><a class="nav-link" href="chat.php">✨ AI Chat</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <?php if (!empty($_SESSION['is_admin'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">⚙️ Admin</a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                        <li><a class="dropdown-item" href="admin_import.php">📦 Amazon Import</a></li>
                        <li><a class="dropdown-item" href="admin_seed.php">🌱 Seed Data</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="btn btn-outline-primary btn-sm" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
