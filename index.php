<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PriceScope Pro - Market Intelligence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="landing-wrapper">
        <!-- Transparent Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-transparent pt-4">
            <div class="container">
                <a class="navbar-brand fw-bold tracking-tight" href="#">
                    PRICESCOPE <span class="text-primary">PRO</span>
                </a>
                <div class="ms-auto">
                    <a href="login.php" class="btn btn-outline-primary btn-sm px-4 rounded-pill">Log In</a>
                    <a href="register.php" class="btn btn-primary btn-sm px-4 rounded-pill ms-2">Get Started</a>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="py-5 mt-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-5 mb-lg-0">
                        <div class="animate-up">
                            <div class="d-inline-flex align-items-center border border-dark-subtle rounded-pill px-3 py-1 mb-4 bg-dark-surface">
                                <span class="badge bg-primary rounded-pill me-2">NEW</span>
                                <span class="small text-muted">AI Analyst 2.0 is now live</span>
                            </div>
                            <h1 class="hero-title-large">
                                Stop Guessing.<br>Start Executing.
                            </h1>
                            <p class="hero-subtitle mb-5">
                                The professional-grade price tracker for modern shoppers. 
                                Real-time data, historical analysis, and AI-driven signals to ensure you never overpay again.
                            </p>
                            <div class="d-flex gap-3">
                                <a href="register.php" class="btn btn-primary btn-lg px-5 rounded-pill shadow-lg hover-scale">
                                    Start Tracking Free
                                </a>
                                <a href="#demo" class="btn btn-outline-primary btn-lg px-4 rounded-pill hover-scale">
                                    View Live Demo
                                </a>
                            </div>
                            <div class="mt-5 d-flex align-items-center gap-4 text-muted small">
                                <div><span class="text-dark fw-bold">10k+</span> Products Tracked</div>
                                <div class="vr"></div>
                                <div><span class="text-dark fw-bold">₹2.5Cr</span> Saved</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="app-preview-card p-3 animate-up delay-200">
                            <!-- Mock Browser Header -->
                            <div class="d-flex align-items-center mb-3 px-2">
                                <div class="d-flex gap-2 me-3">
                                    <div class="rounded-circle bg-danger" style="width:10px; height:10px;"></div>
                                    <div class="rounded-circle bg-warning" style="width:10px; height:10px;"></div>
                                    <div class="rounded-circle bg-success" style="width:10px; height:10px;"></div>
                                </div>
                                <div class="bg-dark-surface rounded px-3 py-1 small text-muted flex-grow-1 text-center font-monospace">
                                    pricescope.pro/dashboard
                                </div>
                            </div>
                            <!-- Mock Dashboard Content -->
                            <div class="bg-body rounded p-4" style="min-height: 300px;">
                                <div class="d-flex justify-content-between mb-4">
                                    <div>
                                        <div class="h5 mb-0">Sony WH-1000XM5</div>
                                        <div class="small text-success">● Historic Low</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="h4 mb-0">₹19,990</div>
                                        <div class="small text-danger">-15% Drop</div>
                                    </div>
                                </div>
                                <!-- Mock Chart -->
                                <div class="d-flex align-items-end gap-1" style="height: 150px;">
                                    <div class="bg-primary opacity-25 w-100 rounded-top" style="height: 40%"></div>
                                    <div class="bg-primary opacity-25 w-100 rounded-top" style="height: 60%"></div>
                                    <div class="bg-primary opacity-25 w-100 rounded-top" style="height: 50%"></div>
                                    <div class="bg-primary opacity-50 w-100 rounded-top" style="height: 30%"></div>
                                    <div class="bg-primary opacity-50 w-100 rounded-top" style="height: 45%"></div>
                                    <div class="bg-primary w-100 rounded-top" style="height: 20%"></div>
                                </div>
                                <div class="mt-3 p-3 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                                    <div class="d-flex gap-2">
                                        <span>🐧</span>
                                        <span class="small fw-bold text-success">Blu says: "Buy Now! This is the lowest price in 120 days."</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-5">
            <div class="container py-5">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="feature-box h-100">
                            <div class="feature-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>
                            <h3 class="h5 fw-bold mb-3">Price History</h3>
                            <p class="text-muted mb-0">Don't rely on current prices. We track data over time to reveal the true market value and hidden trends.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-box h-100">
                            <div class="feature-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            </div>
                            <h3 class="h5 fw-bold mb-3">Instant Alerts</h3>
                            <p class="text-muted mb-0">Set your target price. When it hits, we notify you instantly via email or push notification.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-box h-100">
                            <div class="feature-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <h3 class="h5 fw-bold mb-3">AI Analysis</h3>
                            <p class="text-muted mb-0">Our AI "Blu" analyzes market volatility to give you a simple "Buy" or "Wait" recommendation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Supported Stores -->
        <section class="py-5 border-top border-dark-subtle">
            <div class="container text-center">
                <p class="text-muted small text-uppercase fw-bold mb-4">Trusted Data Sources</p>
                <div class="d-flex justify-content-center gap-5 opacity-50 grayscale-logos">
                    <h4 class="text-white fw-bold">amazon</h4>
                    <h4 class="text-white fw-bold">flipkart</h4>
                    <h4 class="text-white fw-bold">croma</h4>
                    <h4 class="text-white fw-bold">myntra</h4>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="py-5 bg-dark-surface">
            <div class="container py-5">
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Market Sentiment</h2>
                    <p class="text-muted">What our 10,000+ users are saying.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card bg-body border-dark-subtle p-4 h-100">
                            <div class="text-warning mb-3">★★★★★</div>
                            <p class="text-muted mb-4">"The AI signals are scary accurate. I saved ₹5,000 on my MacBook just by waiting 3 days for Blu's signal."</p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">R</div>
                                <div>
                                    <div class="fw-bold text-white">Rahul S.</div>
                                    <div class="small text-muted">Tech Enthusiast</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-body border-dark-subtle p-4 h-100">
                            <div class="text-warning mb-3">★★★★★</div>
                            <p class="text-muted mb-4">"Finally, a price tracker that looks and feels like a professional tool. The dark mode is perfect."</p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">P</div>
                                <div>
                                    <div class="fw-bold text-white">Priya M.</div>
                                    <div class="small text-muted">Day Trader</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-body border-dark-subtle p-4 h-100">
                            <div class="text-warning mb-3">★★★★★</div>
                            <p class="text-muted mb-4">"I use the API integration to track my entire wishlist. The historical data charts are a game changer."</p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">A</div>
                                <div>
                                    <div class="fw-bold text-white">Arjun K.</div>
                                    <div class="small text-muted">Developer</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-5">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="text-center mb-5">
                            <h2 class="fw-bold">System FAQ</h2>
                        </div>
                        <div class="accordion accordion-flush" id="faqAccordion">
                            <div class="accordion-item bg-transparent border-dark-subtle">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        How accurate is the AI prediction?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        Our AI model analyzes 3 years of historical price data and seasonal trends. While no prediction is 100% guaranteed, our signals have an 87% success rate in identifying price bottoms.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item bg-transparent border-dark-subtle">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Is it really free?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        Yes. PriceScope Pro is currently in Open Beta and is 100% free for early adopters. We may introduce premium tiers later, but your current tracked items will remain free forever.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item bg-transparent border-dark-subtle">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Which sites do you track?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        We currently specialize in Amazon India. Support for Flipkart, Myntra, and Croma is in beta testing and will be rolled out to all users in the next update.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-5 text-center">
            <div class="container">
                <div class="p-5 rounded-5 bg-primary bg-gradient text-white">
                    <h2 class="fw-bold mb-3">Ready to Outsmart the Market?</h2>
                    <p class="lead mb-4 opacity-75">Join thousands of smart shoppers tracking prices today.</p>
                    <a href="register.php" class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-primary hover-scale">Create Free Account</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-4 border-top border-dark-subtle text-center text-muted small">
            <div class="container">
                <p class="mb-0">&copy; <?= date('Y') ?> PriceScope Pro. All rights reserved.</p>
            </div>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
