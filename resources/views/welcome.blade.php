<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ePATNER - AI-Powered Business Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --light-bg: rgb(226, 230, 241);
            --dark-bg: #1a1a1a;
            --text-dark: #1e1e1e;
            --text-light: #ffffff;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-dark);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            line-height: 1.6;
        }

        .container {
            padding-left: 24px;
            padding-right: 24px;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.25);
            color: white;
        }

        .btn-outline-custom {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 8px;
            padding: 11px 24px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .pricing-card {
            border-radius: var(--border-radius);
            padding: 30px;
            background-color: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .highlight-card {
            background-color: var(--primary-color);
            color: white;
        }

        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 60px 0 30px;
        }

        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            margin: 0 5px;
            padding: 8px 15px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .nav-link:hover {
            background-color: rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        .navbar {
            padding: 16px 0;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .hero-section {
            padding: 120px 0 80px;
        }


        /* Additional navbar styles from GIFTIFI */
        .navbar-scrolled {
            background-color: rgba(255, 255, 255, 0.95) !important;
            padding: 10px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 40px;
            margin: 10px 20px;
            width: calc(100% - 40px);
        }

        .hover-bg-light:hover {
            background-color: rgba(226, 230, 241, 0.5);
        }

        /* Mobile Responsive Adjustments for Navbar */
        @media (max-width: 992px) {
            .navbar-scrolled {
                border-radius: 30px;
                margin: 8px 16px;
                width: calc(100% - 32px);
            }

            #mobile-menu {
                top: 70px;
                z-index: 1040;
            }
        }

        @media (max-width: 768px) {
            .navbar-scrolled {
                border-radius: 25px;
                margin: 5px 10px;
                width: calc(100% - 20px);
            }
        }

        @media (max-width: 576px) {
            .navbar-scrolled {
                border-radius: 20px;
                margin: 3px 6px;
                width: calc(100% - 12px);
            }
        }


        .section-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .feature-box {
            padding: 25px;
            border-radius: var(--border-radius);
            background-color: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .testimonial-card {
            padding: 25px;
            border-radius: var(--border-radius);
            background-color: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .testimonial-card:before {
            content: '"';
            position: absolute;
            top: 10px;
            left: 15px;
            font-size: 60px;
            color: rgba(37, 99, 235, 0.2);
            font-family: Georgia, serif;
        }

        .faq-item {
            padding: 20px;
            border-radius: var(--border-radius);
            background-color: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            margin-bottom: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .faq-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .bg-light-custom {
            background-color: rgba(226, 230, 241, 0.5);
        }

        .rounded-custom {
            border-radius: var(--border-radius);
        }

        .img-shadow {
            box-shadow: var(--box-shadow);
        }

        /* Hero Section Styles */
        .hero-section {
            position: relative;
            margin-top: 0;
            padding-top: 150px;
        }

        .carousel-indicators {
            bottom: 30px;
        }

        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin: 0 6px;
            background-color: rgba(37, 99, 235, 0.5);
            border: none;
        }

        .carousel-indicators button.active {
            background-color: var(--primary-color);
            width: 12px;
            height: 12px;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            opacity: 0.7;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            opacity: 1;
        }

        .scroll-down-indicator {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            text-align: center;
            color: var(--primary-color);
        }

        .bounce {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        .features-preview-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: -70px;
            position: relative;
            z-index: 20;
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .product-preview-img {
            max-height: 200px;
            transition: transform 0.5s ease;
        }

        .product-preview-img:hover {
            transform: translateY(-10px);
        }

        .feature-preview-item {
            text-align: center;
            padding: 15px 10px;
            transition: var(--transition);
        }

        .feature-preview-item:hover {
            transform: translateY(-5px);
        }

        .feature-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(226, 230, 241, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .feature-preview-item:hover .feature-icon-wrapper {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        /* Consistent navbar styling */
        .navbar {
            border-radius: 40px;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin: 12px 20px;
            width: calc(100% - 40px);
            transition: var(--transition);
        }

        .hover-bg-light:hover {
            background-color: rgba(226, 230, 241, 0.5);
        }

        /* Mobile navbar adjustments */
        @media (max-width: 992px) {
            .navbar .container {
                justify-content: space-between;
            }

            .navbar-brand {
                margin-right: 0;
            }

            .btn-primary-custom {
                padding: 8px 16px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 768px) {
            #mobile-menu {
                top: 65px;
            }
        }

        @media (max-width: 576px) {
            .navbar {
                padding-left: 15px !important;
                padding-right: 15px !important;
            }

            .btn-primary-custom {
                padding: 7px 14px;
            }
        }

        /* Updated Hero Section Styles */
        .hero-section {
            position: relative;
            padding-top: 0;
            margin-top: 0;
            margin-bottom: 0;
            overflow: hidden;
        }

        .hero-slide {
            min-height: 80vh;
            padding-top: 80px;
            padding-bottom: 30px;
            display: flex;
            align-items: center;
        }

        .carousel-indicators {
            bottom: 10px;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 992px) {
            .hero-slide {
                min-height: 65vh;
                padding-top: 80px;
                padding-bottom: 40px;
            }

            .display-4 {
                font-size: 2.2rem;
            }

            .lead {
                font-size: 1rem;
            }

            .btn-lg {
                padding: 0.5rem 1.5rem !important;
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .hero-slide {
                min-height: 55vh;
                padding-top: 70px;
                padding-bottom: 30px;
            }

            .display-4 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 576px) {
            .hero-slide {
                min-height: 45vh;
                padding-top: 60px;
                padding-bottom: 20px;
            }

            .display-4 {
                font-size: 1.5rem;
            }

            .btn-lg {
                padding: 0.4rem 1.2rem !important;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav id="navbar" class="navbar navbar-expand-lg navbar-light bg-white shadow-md px-4 py-3 fixed-top rounded-pill mx-4 mt-3">
        <div class="container">
            <!-- Logo - left aligned on all screens -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                <img src="/images/logo.png" alt="ePATNER Logo" height="30" class="me-2">
            </a>

            <!-- Login/Dashboard button for mobile -->
            <div class="d-lg-none">
                @if (Route::has('login'))
                @auth
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary-custom">Dashboard</a>
                @else
                <a href="{{ route('login') }}" class="btn btn-primary-custom">Login</a>
                @endauth
                @endif
            </div>

            <!-- Navigation links - hidden on mobile, visible on desktop -->
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#technology">Technology</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                </ul>

                <!-- Desktop buttons -->
                <div class="d-none d-lg-flex ms-lg-4">
                    @if (Route::has('login'))
                    @auth
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary-custom">Dashboard</a>
                    @else
                    <a href="{{ route('login') }}" class="btn btn-primary-custom">Login</a>
                    @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <div class="hero-slide" style="background: url('/images/dfdsfsd.jpg') no-repeat center center; background-size: cover; background-blend-mode: overlay;">
                        <div class="container h-100">
                            <div class="row h-100 align-items-center">
                                <div class="col-lg-6 col-md-8 mx-auto mx-lg-0 text-center text-lg-start">
                                    <h1 class="display-4 fw-bold mb-3 text-dark">AI-Powered Business Automation</h1>
                                    <p class="lead mb-4 text-dark">Streamline your operations with our comprehensive ERP system designed for modern businesses.</p>
                                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                                        <a href="#" class="btn btn-primary-custom btn-lg px-4 py-2">Get Started</a>
                                        <a href="#features" class="btn btn-outline-custom btn-lg px-4 py-2">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="carousel-item">
                    <div class="hero-slide" style="background: linear-gradient(to bottom, rgba(226, 230, 241, 0.95), rgba(226, 230, 241, 0.8) 20%, rgba(226, 230, 241, 0.8) 80%, rgba(226, 230, 241, 0.95)), url('/images/accounting-module.jpg') no-repeat center center; background-size: cover; background-blend-mode: overlay;">
                        <div class="container h-100">
                            <div class="row h-100 align-items-center">
                                <div class="col-lg-6 col-md-8 mx-auto mx-lg-0 text-center text-lg-start">
                                    <h1 class="display-4 fw-bold mb-3 text-dark">Complete Financial Control</h1>
                                    <p class="lead mb-4 text-dark">Manage your finances with precision using our advanced accounting tools and real-time reporting.</p>
                                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                                        <a href="#" class="btn btn-primary-custom btn-lg px-4 py-2">Get Started</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="carousel-item">
                    <div class="hero-slide" style="background: linear-gradient(to bottom, rgba(226, 230, 241, 0.95), rgba(226, 230, 241, 0.8) 20%, rgba(226, 230, 241, 0.8) 80%, rgba(226, 230, 241, 0.95)), url('/images/inventory-management.jpg') no-repeat center center; background-size: cover; background-blend-mode: overlay;">
                        <div class="container h-100">
                            <div class="row h-100 align-items-center">
                                <div class="col-lg-6 col-md-8 mx-auto mx-lg-0 text-center text-lg-start">
                                    <h1 class="display-4 fw-bold mb-3 text-dark">Smart Inventory Solutions</h1>
                                    <p class="lead mb-4 text-dark">Track stock levels, manage suppliers, and optimize your inventory with our intelligent system.</p>
                                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                                        <a href="#" class="btn btn-primary-custom btn-lg px-4 py-2">Get Started</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carousel Controls with Custom Styling -->
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

            <!-- Carousel Indicators -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
        </div>
    </section>


    <!-- Features -->
    <section id="features" class="py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">Intelligent Business Automation</h2>
                    <p class="lead">Our AI-powered ERP system streamlines your operations and helps you make data-driven decisions.</p>
                </div>
                <div class="col-md-6 text-center">
                    <img src="/images/business-automation.jpg" class="img-fluid rounded-custom img-shadow" alt="Business Automation">
                </div>
            </div>
            <div class="row g-4">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="feature-box">
                            <div class="text-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-calculator feature-icon" viewBox="0 0 16 16">
                                    <path d="M12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h8zM4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4z" />
                                    <path d="M4 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-4z" />
                                </svg>
                            </div>
                            <h5 class="text-center mb-3">Financial Management</h5>
                            <p class="text-center">Complete accounting system with ledgers, transactions, and financial reporting.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-box">
                            <div class="text-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-box-seam feature-icon" viewBox="0 0 16 16">
                                    <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z" />
                                </svg>
                            </div>
                            <h5 class="text-center mb-3">Inventory Management</h5>
                            <p class="text-center">Track stock levels, manage suppliers, and optimize your inventory in real-time.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-box">
                            <div class="text-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-shop feature-icon" viewBox="0 0 16 16">
                                    <path d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.371 2.371 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976l2.61-3.045zm1.78 4.275a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 1 0 2.75 0V5.37a.5.5 0 0 0-.12-.325L12.27 2H3.73L1.12 5.045A.5.5 0 0 0 1 5.37v.255a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0zM1.5 8.5A.5.5 0 0 1 2 9v6h1v-5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v5h6V9a.5.5 0 0 1 1 0v6h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1V9a.5.5 0 0 1 .5-.5zM4 15h3v-5H4v5zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3zm3 0h-2v3h2v-3z" />
                                </svg>
                            </div>
                            <h5 class="text-center mb-3">POS System</h5>
                            <p class="text-center">Streamline your sales process with our integrated point of sale system.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-box">
                            <div class="text-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-graph-up-arrow feature-icon" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5Z" />
                                </svg>
                            </div>
                            <h5 class="text-center mb-3">Business Analytics</h5>
                            <p class="text-center">Gain insights with AI-powered analytics and customizable reports.</p>
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <!-- Technology Section -->
    <section id="technology" class="py-5 bg-light-custom">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Cutting-Edge Technology</h2>
                <p class="lead">Powered by advanced AI and cloud technology for maximum efficiency.</p>
            </div>
            <div class="row text-center g-4">
                <!-- Technology Section -->
                <section id="technology" class="py-5 bg-light-custom">
                    <div class="container">
                        <div class="text-center mb-5">
                            <h2 class="section-title">Cutting-Edge Technology</h2>
                            <p class="lead">Powered by advanced AI and cloud technology for maximum efficiency.</p>
                        </div>
                        <div class="row text-center g-4">
                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="text-center mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-robot feature-icon" viewBox="0 0 16 16">
                                            <path d="M6 12.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5ZM3 8.062C3 6.76 4.235 5.765 5.53 5.886a26.58 26.58 0 0 0 4.94 0C11.765 5.765 13 6.76 13 8.062v1.157a.933.933 0 0 1-.765.935c-.845.147-2.34.346-4.235.346-1.895 0-3.39-.2-4.235-.346A.933.933 0 0 1 3 9.219V8.062Zm4.542-.827a.25.25 0 0 0-.217.068l-.92.9a24.767 24.767 0 0 1-1.871-.183.25.25 0 0 0-.068.495c.55.076 1.232.149 2.02.193a.25.25 0 0 0 .189-.071l.754-.736.847 1.71a.25.25 0 0 0 .404.062l.932-.97a25.286 25.286 0 0 0 1.922-.188.25.25 0 0 0-.068-.495c-.538.074-1.207.145-1.98.189a.25.25 0 0 0-.166.076l-.754.785-.842-1.7a.25.25 0 0 0-.182-.135Z" />
                                            <path d="M8.5 1.866a1 1 0 1 0-1 0V3h-2A4.5 4.5 0 0 0 1 7.5V8a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1v1a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-1a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1v-.5A4.5 4.5 0 0 0 10.5 3h-2V1.866ZM14 7.5V13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5A3.5 3.5 0 0 1 5.5 4h5A3.5 3.5 0 0 1 14 7.5Z" />
                                        </svg>
                                    </div>
                                    <h5 class="mb-3">AI-Powered Insights</h5>
                                    <p>Advanced algorithms analyze your business data to provide actionable insights and predictions.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="text-center mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-cloud feature-icon" viewBox="0 0 16 16">
                                            <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z" />
                                        </svg>
                                    </div>
                                    <h5 class="mb-3">Cloud Integration</h5>
                                    <p>Access your business data securely from anywhere with our cloud-based platform.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="text-center mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-shield-check feature-icon" viewBox="0 0 16 16">
                                            <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z" />
                                            <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                                        </svg>
                                    </div>
                                    <h5 class="mb-3">Enterprise Security</h5>
                                    <p>Bank-grade security protocols to keep your business data safe and compliant.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Testimonials -->
                <!-- Testimonials -->
                <section id="testimonials" class="py-5">
                    <div class="container">
                        <div class="text-center mb-5">
                            <h2 class="section-title">What Our Clients Say</h2>
                            <p class="lead">Join thousands of businesses that have transformed their operations with ePATNER.</p>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-4 mb-4">
                                <div class="testimonial-card">
                                    <p class="mb-3">"ePATNER has revolutionized how we manage inventory. We've reduced stockouts by 75% and our order fulfillment is faster than ever. The ROI has been incredible!"</p>
                                    <div class="d-flex align-items-center">
                                        <img src="/images/user-1.png" alt="Sarah Johnson" class="rounded-circle me-3" width="50" height="50">
                                        <div>
                                            <h6 class="mb-0">PAIKARI BAZER</h6>
                                            <small class="text-muted">Wholesale & Supplier</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-4">
                                <div class="testimonial-card">
                                    <p class="mb-3">"The reporting features in ePATNER give us insights we never had before. Making data-driven decisions has helped us grow our business by 40% in just one year!"</p>
                                    <div class="d-flex align-items-center">
                                        <img src="/images/user-2.png" alt="Mohammed Rahman" class="rounded-circle me-3" width="50" height="50">
                                        <div>
                                            <h6 class="mb-0">aipdia</h6>
                                            <small class="text-muted">Publishing Site</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-4">
                                <div class="testimonial-card">
                                    <p class="mb-3">"Customer support at ePATNER is exceptional. Whenever we've had questions, their team responds quickly and effectively. It's like having an IT department on call!"</p>
                                    <div class="d-flex align-items-center">
                                        <img src="/images/user-3.png" alt="Priya Patel" class="rounded-circle me-3" width="50" height="50">
                                        <div>
                                            <h6 class="mb-0">GIFTIFI</h6>
                                            <small class="text-muted">Online Gift Shop</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 bg-light-custom">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Simple, Transparent Pricing</h2>
                <p class="lead">Choose the plan that fits your business needs.</p>
            </div>
            <div class="row justify-content-center g-4">
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold">Starter</h4>
                            <div class="d-inline-block position-relative my-4">
                                <span class="fs-1 fw-bold">$49</span>
                                <span class="position-absolute bottom-0 end-0 mb-1 text-muted">/mo</span>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Basic accounting
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Inventory management
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                1 user account
                            </li>
                            <li class="mb-3 d-flex align-items-center text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle-fill text-muted me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z" />
                                </svg>
                                AI-powered analytics
                            </li>
                        </ul>
                        <div class="text-center">
                            <a href="#" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-medium">Get Started</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pricing-card highlight-card">
                        <div class="text-center mb-4">
                            <span class="badge bg-white text-primary position-absolute top-0 end-0 mt-3 me-3">Popular</span>
                            <h4 class="fw-bold">Business</h4>
                            <div class="d-inline-block position-relative my-4">
                                <span class="fs-1 fw-bold">$99</span>
                                <span class="position-absolute bottom-0 end-0 mb-1 text-white opacity-75">/mo</span>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-white me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Advanced accounting
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-white me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Complete inventory system
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-white me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                5 user accounts
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-white me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Basic AI analytics
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-white me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Priority support
                            </li>
                        </ul>
                        <div class="text-center">
                            <a href="#" class="btn btn-light rounded-pill px-4 py-2 fw-medium">Start 14-Day Trial</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold">Enterprise</h4>
                            <div class="d-inline-block position-relative my-4">
                                <span class="fs-1 fw-bold">$249</span>
                                <span class="position-absolute bottom-0 end-0 mb-1 text-muted">/mo</span>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Full ERP suite
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Unlimited users
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Advanced AI analytics
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Dedicated account manager
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </svg>
                                Custom integrations
                            </li>
                        </ul>
                        <div class="text-center">
                            <a href="#" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-medium">Contact Sales</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Frequently Asked Questions</h2>
            <div class="row g-4">
                @foreach(['How does ePATNER help my business?', 'Is my data secure?', 'Do you offer implementation support?', 'Can I integrate with other systems?'] as $index => $faq)
                <div class="col-md-6">
                    <div class="faq-item">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; min-width: 30px;">
                                <span>{{ $index + 1 }}</span>
                            </div>
                            <h5 class="mb-0 fw-bold">{{ $faq }}</h5>
                        </div>
                        <p class="ms-5 mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Our comprehensive ERP solution streamlines operations, improves efficiency, and provides valuable insights to help your business grow.</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-light-custom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3">Ready to Transform Your Business?</h2>
                    <p class="lead mb-4">Join thousands of businesses that have improved their operations with ePATNER's comprehensive ERP solution.</p>
                    <a href="#" class="btn btn-primary btn-lg">Get Started Today</a>
                </div>
                <div class="col-lg-5">
                    <img src="/images/business-dashboard.jpg" alt="ePATNER Dashboard" class="img-fluid rounded-custom img-shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                        <img src="/images/logo-footer.png" alt="ePATNER Logo" height="30" class="me-2">
                    </a>
                    <p style="margin-top:20px;">Empowering businesses with intelligent ERP solutions that drive growth and efficiency.</p>
                    <div class="d-flex mt-4">
                        <a href="https://www.facebook.com/aaifico" class="me-3 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                                <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z" />
                            </svg>
                        </a>
                        <a href="#" class="me-3 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16">
                                <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z" />
                            </svg>
                        </a>
                        <a href="#" class="me-3 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
                                <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z" />
                            </svg>
                        </a>
                        <a href="#" class="text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
                                <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z" />
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5 class="fw-bold mb-3">Company</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/about" class="text-white text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="/blog" class="text-white text-decoration-none">Blog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5 class="fw-bold mb-3">Support</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/contact" class="text-white text-decoration-none">Help Center</a></li>
                        <li class="mb-2"><a href="/contact" class="text-white text-decoration-none">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5 class="fw-bold mb-3">Stay Updated</h5>
                    <p>Subscribe to our newsletter for the latest updates and business tips.</p>
                    <form class="mt-3">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Your email" aria-label="Your email">
                            <button class="btn btn-light" type="button">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">&copy; 2025 ePATNER Inc. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-white text-decoration-none">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>


    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; opacity: 0; transition: all 0.3s ease;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-up" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z" />
        </svg>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize carousel
            const heroCarousel = new bootstrap.Carousel(document.getElementById('heroCarousel'), {
                interval: 5000,
                wrap: true
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;

                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Feature hover effects
            const featureItems = document.querySelectorAll('.feature-preview-item');
            featureItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.querySelector('.feature-icon-wrapper').style.transform = 'scale(1.1)';
                });

                item.addEventListener('mouseleave', function() {
                    this.querySelector('.feature-icon-wrapper').style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>

</html>