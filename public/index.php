<?php require_once 'bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Andcorp Autos - Your Comfort, Our Priority</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
    <style>
        .hero {
            background: var(--primary);
            color: white;
            padding: 100px 0;
        }
        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
        }
        .step-number {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        footer {
            background: var(--primary);
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern">
        <div class="container">
            <a class="navbar-brand" href="https://andcorpautos.com">
                <img src="<?php echo url('assets/images/logo.png'); ?>" alt="Andcorp Autos" style="height: 45px; width: auto;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('login.php'); ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-2" href="<?php echo url('register.php'); ?>">Get Started</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-4">Import Your Dream Car from the USA</h1>
            <p class="lead mb-5">We buy on-demand from Copart and IAA auctions and deliver to Ghana with full transparency</p>
            <a href="<?php echo url('register.php'); ?>" class="btn btn-light btn-lg me-3">Start Your Order</a>
            <a href="#how-it-works" class="btn btn-outline-light btn-lg">Learn More</a>
        </div>
    </div>

    <div class="container my-5" id="features">
        <div class="row text-center mb-5">
            <div class="col">
                <h2 class="display-5 fw-bold">Why Choose Andcorp Autos?</h2>
                <p class="lead text-muted">We handle everything from purchase to delivery</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <i class="bi bi-search feature-icon"></i>
                    <h3 class="mt-3">No Inventory Limits</h3>
                    <p class="text-muted">Choose from millions of vehicles on Copart and IAA. We buy exactly what you want.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <i class="bi bi-truck feature-icon"></i>
                    <h3 class="mt-3">Full Tracking</h3>
                    <p class="text-muted">Track your vehicle from auction to your doorstep with real-time updates.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <i class="bi bi-shield-check feature-icon"></i>
                    <h3 class="mt-3">Quality Inspection</h3>
                    <p class="text-muted">Thorough inspection with detailed reports before you take delivery.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <i class="bi bi-cash-stack feature-icon"></i>
                    <h3 class="mt-3">Transparent Pricing</h3>
                    <p class="text-muted">Clear breakdown of all costs including customs, duties, and clearing fees.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <i class="bi bi-tools feature-icon"></i>
                    <h3 class="mt-3">Repair Services</h3>
                    <p class="text-muted">Professional repairs and improvements with ongoing updates.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <i class="bi bi-bell feature-icon"></i>
                    <h3 class="mt-3">Constant Updates</h3>
                    <p class="text-muted">Email and SMS notifications at every stage of the process.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-light py-5" id="how-it-works">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2 class="display-5 fw-bold">How It Works</h2>
                    <p class="lead text-muted">Simple 7-step process from selection to delivery</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="step-number mx-auto">1</div>
                        <h4>Select Your Vehicle</h4>
                        <p class="text-muted">Browse Copart/IAA listings and tell us which vehicle you want</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="step-number mx-auto">2</div>
                        <h4>We Purchase</h4>
                        <p class="text-muted">We bid and buy the vehicle on your behalf from the auction</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="step-number mx-auto">3</div>
                        <h4>Shipping to Ghana</h4>
                        <p class="text-muted">Vehicle is shipped with full tracking and regular updates</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="step-number mx-auto">4</div>
                        <h4>Customs Clearance</h4>
                        <p class="text-muted">We calculate duties and handle customs clearance process</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="step-number mx-auto">5</div>
                        <h4>Inspection</h4>
                        <p class="text-muted">Detailed inspection with photos and comprehensive report</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="step-number mx-auto">6</div>
                        <h4>Repairs & Fixes</h4>
                        <p class="text-muted">Professional repairs with continuous progress updates</p>
                    </div>
                </div>
                <div class="col-md-12 col-lg-12">
                    <div class="text-center">
                        <div class="step-number mx-auto">7</div>
                        <h4>Delivery</h4>
                        <p class="text-muted">Your car is delivered to your location in Ghana</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-4">Ready to Import Your Car?</h2>
                <p class="lead mb-4">Join hundreds of satisfied customers who have imported their dream cars with Andcorp Autos</p>
                <a href="<?php echo url('register.php'); ?>" class="btn btn-primary btn-lg">Create Your Account</a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
