<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope Pro - Market Intelligence</title>
    <style>
        /* Base Variables & Overrides for this Page */
        :root {
            --bg-deep: #020617;
            --neon-cyan: #00f2ff;
            --neon-pink: #ff0099;
            --neon-purple: #bc13fe;
            /* Using a dark, glossy gradient for the glass surface */
            --glass-surface: linear-gradient(145deg, rgba(20, 30, 60, 0.7), rgba(10, 15, 30, 0.8));
            --glass-border: 1px solid rgba(0, 242, 255, 0.3);
            --gloss-highlight: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        /* Global & Body Styles */
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 0;
            background-color: var(--bg-deep);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            /* Aurora Background Orbs */
            background: radial-gradient(circle at 10% 20%, rgba(188, 19, 254, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(0, 242, 255, 0.15) 0%, transparent 40%),
                        #020617;
        }
        /* Neon Grid Overlay */
        body::after {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(rgba(0, 242, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 242, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            pointer-events: none;
        }

        /* Header Styles */
        header { padding: 20px 5%; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo { font-size: 26px; font-weight: 800; letter-spacing: -1px; }
        .logo span { color: var(--neon-cyan); text-shadow: 0 0 10px rgba(0, 242, 255, 0.5); }
        
        /* Hero Section */
        .hero { text-align: center; padding: 100px 20px; position: relative; }
        .hero h1 { 
            font-size: 4.5em; line-height: 1.1; margin-bottom: 20px; font-weight: 900; 
            background: linear-gradient(to right, #fff, #a5b4fc); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        .hero h1 span { 
            background: linear-gradient(to right, var(--neon-cyan), var(--neon-purple)); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            filter: drop-shadow(0 0 5px rgba(188,19,254,0.5)); 
        }
        
        /* CTA Button */
        .cta-btn {
            padding: 16px 40px; border-radius: 30px; border: none; font-weight: 700; font-size: 1.1em; 
            cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s;
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
            color: #fff;
            box-shadow: 0 0 20px rgba(0, 242, 255, 0.4);
        }
        .btn-primary:hover { box-shadow: 0 0 40px rgba(188, 19, 254, 0.6); transform: scale(1.05); }
        
        /* Features Section & Glass Card */
        .features { display: flex; gap: 30px; justify-content: center; padding: 50px 5%; }
        .glass-card {
            background: var(--glass-surface);
            border: var(--glass-border);
            border-top: 1px solid rgba(255,255,255,0.4); /* Glossy top edge */
            backdrop-filter: blur(20px);
            padding: 40px; border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            transition: 0.3s;
            position: relative; overflow: hidden;
            flex: 1; /* Make cards expand in flex container */
            max-width: 350px;
        }
        .glass-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(0, 242, 255, 0.15); border-color: var(--neon-cyan); }
        
        /* Utility */
        .penguin-badge { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--neon-cyan); overflow: hidden; box-shadow: 0 0 15px var(--neon-cyan); }
    </style>
</head>
<body>
    <header>
        <div class="logo">PriceScope<span>PRO</span></div>
        <a href="login.php" class="cta-btn btn-primary" style="padding: 10px 25px; font-size: 0.9em;">Login Terminal</a>
    </header>

    <div class="hero">
        <h1>MARKET INTELLIGENCE <br> <span>REDEFINED</span></h1>
        <p style="font-size: 1.2em; color: #94a3b8; max-width: 600px; margin: 0 auto 40px;">Stop overpaying. Use our AI-driven signals to buy at the perfect moment.</p>
        <a href="register.php" class="cta-btn btn-primary">Start Tracking Free</a>
    </div>

    <div class="features">
        <div class="glass-card">
            <h3 style="color: var(--neon-cyan);">⚡ Real-time Data</h3>
            <p style="color: #cbd5e1;">Live price updates across all major marketplaces.</p>
        </div>
        <div class="glass-card">
            <h3 style="color: var(--neon-purple);">🧠 AI Analyst</h3>
            <p style="color: #cbd5e1;">Predictive algorithms tell you exactly when to buy.</p>
             <div style="position: absolute; bottom: 10px; right: 10px;"><img src="https://via.placeholder.com/40" class="penguin-badge"></div>
        </div>
        <div class="glass-card">
            <h3 style="color: var(--neon-pink);">🛡️ Secure Vault</h3>
            <p style="color: #cbd5e1;">Your data is encrypted and stored safely.</p>
        </div>
    </div>
</body>
</html>
