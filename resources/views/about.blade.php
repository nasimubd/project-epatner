<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About ePATNER - AI-Powered Business Automation</title>
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
            transition: var(--transition);
            z-index: 1050;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            background-color: transparent;
        }

        .navbar.scrolled {
            background-color: rgba(255, 255, 255, 0.95) !important;
            padding: 10px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 40px;
            margin: 10px 20px;
            width: calc(100% - 40px);
        }

        .navbar.scrolled .nav-link {
            color: var(--text-dark) !important;
        }

        .navbar.scrolled .navbar-brand {
            color: var(--primary-color) !important;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-color);
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

        .hero-section {
            padding: 120px 0 80px;
            background: linear-gradient(to bottom, rgba(226, 230, 241, 0.95), rgba(226, 230, 241, 0.8) 20%, rgba(226, 230, 241, 0.8) 80%, rgba(226, 230, 241, 0.95)), url('/images/about-hero.jpg') no-repeat center center;
            background-size: cover;
            background-blend-mode: overlay;
        }

        .team-card {
            border-radius: var(--border-radius);
            overflow: hidden;
            background-color: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .team-img-container {
            position: relative;
            overflow: hidden;
            height: 250px;
        }

        .team-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .team-card:hover .team-img {
            transform: scale(1.05);
        }

        .team-info {
            padding: 20px;
        }

        .team-social {
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            background-color: rgba(37, 99, 235, 0.9);
            padding: 10px 0;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .team-card:hover .team-social {
            bottom: 0;
        }

        .social-icon {
            color: white;
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .social-icon:hover {
            transform: translateY(-3px);
        }

        .timeline {
            position: relative;
            padding: 0;
            list-style: none;
        }

        .timeline:before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 2px;
            margin-left: -1px;
            background-color: var(--primary-color);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 50px;
        }

        .timeline-item:after,
        .timeline-item:before {
            content: " ";
            display: table;
        }

        .timeline-item:after {
            clear: both;
        }

        .timeline-panel {
            position: relative;
            float: left;
            width: 46%;
            padding: 20px;
            border-radius: var(--border-radius);
            background-color: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .timeline-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .timeline-item:nth-child(even) .timeline-panel {
            float: right;
        }

        .timeline-item:nth-child(odd) .timeline-panel:before {
            border-left-width: 0;
            border-right-width: 15px;
            left: -15px;
            right: auto;
        }

        .timeline-badge {
            position: absolute;
            top: 20px;
            left: 50%;
            width: 40px;
            height: 40px;
            margin-left: -20px;
            border-radius: 50%;
            text-align: center;
            font-size: 1.4em;
            line-height: 40px;
            background-color: var(--primary-color);
            color: white;
            z-index: 10;
        }

        .value-card {
            border-radius: var(--border-radius);
            padding: 30px;
            background-color: white;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .value-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            padding: 15px;
            border-radius: 50%;
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            transition: var(--transition);
        }

        .value-card:hover .value-icon {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .stats-section {
            background-color: var(--primary-color);
            color: white;
            padding: 80px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: block;
        }

        .stat-label {
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .mission-section {
            position: relative;
            padding: 100px 0;
            background: linear-gradient(to right, rgba(226, 230, 241, 0.9), rgba(226, 230, 241, 0.7)), url('/images/mission-bg.jpg') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
        }

        .mission-card {
            border-radius: var(--border-radius);
            padding: 40px;
            background-color: white;
            box-shadow: var(--box-shadow);
        }

        .quote-mark {
            font-size: 5rem;
            line-height: 1;
            color: var(--primary-color);
            opacity: 0.2;
            font-family: Georgia, serif;
            position: absolute;
            top: 20px;
            left: 20px;
        }

        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 60px 0 30px;
        }

        @media (max-width: 992px) {
            .timeline:before {
                left: 60px;
            }

            .timeline-badge {
                left: 60px;
            }

            .timeline-panel {
                width: calc(100% - 90px);
                float: right;
            }

            .timeline-item:nth-child(even) .timeline-panel {
                float: right;
            }

            .navbar.scrolled {
                border-radius: 30px;
                margin: 8px 16px;
                width: calc(100% - 32px);
            }
        }

        @media (max-width: 768px) {
            .navbar.scrolled {
                border-radius: 25px;
                margin: 5px 10px;
                width: calc(100% - 20px);
            }
        }

        @media (max-width: 576px) {
            .navbar.scrolled {
                border-radius: 20px;
                margin: 3px 6px;
                width: calc(100% - 12px);
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent px-4 py-3 fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                <img src="/images/logo.png" alt="ePATNER Logo" height="30" class="me-2">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Our Story</h1>
                    <p class="lead mb-4">ePATNER was founded with a vision to revolutionize how businesses operate through intelligent automation and data-driven insights.</p>
                    <p class="mb-5">We're a team of passionate technologists, business experts, and creative problem-solvers dedicated to helping businesses of all sizes streamline their operations and achieve sustainable growth.</p>
                    <a href="#our-mission" class="btn btn-primary-custom">Learn More</a>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="/images/about-team.png" alt="ePATNER Team" class="img-fluid rounded-custom shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section id="our-mission" class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-6 mx-auto text-center">
                    <h2 class="section-title text-center">Our Mission</h2>
                    <p class="lead">Empowering businesses with intelligent technology solutions that drive growth and efficiency.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mission-card position-relative">
                        <span class="quote-mark">"</span>
                        <div class="ps-4 pt-3">
                            <h3 class="mb-3">Our Vision</h3>
                            <p>To create a world where businesses of all sizes can harness the power of advanced technology to operate more efficiently, make better decisions, and achieve sustainable growth.</p>
                            <p>We envision a future where AI-powered business automation is accessible to everyone, not just large corporations with massive IT budgets.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mission-card position-relative">
                        <span class="quote-mark">"</span>
                        <div class="ps-4 pt-3">
                            <h3 class="mb-3">Our Approach</h3>
                            <p>We believe in creating technology that works for people, not the other way around. Our solutions are designed with the end-user in mind, ensuring they're intuitive, powerful, and adaptable to your unique business needs.</p>
                            <p>We combine cutting-edge AI technology with deep business expertise to deliver solutions that provide real, measurable value.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">5000+</span>
                        <span class="stat-label">Businesses Served</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Customer Satisfaction</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">35%</span>
                        <span class="stat-label">Avg. Efficiency Gain</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Customer Support</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-6 mx-auto text-center">
                    <h2 class="section-title text-center">Our Values</h2>
                    <p class="lead">The principles that guide everything we do at ePATNER.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-lightbulb" viewBox="0 0 16 16">
                                <path d="M2 6a6 6 0 1 1 10.174 4.31c-.203.196-.359.4-.453.619l-.762 1.769A.5.5 0 0 1 10.5 13a.5.5 0 0 1 0 1 .5.5 0 0 1 0 1l-.224.447a1 1 0 0 1-.894.553H6.618a1 1 0 0 1-.894-.553L5.5 15a.5.5 0 0 1 0-1 .5.5 0 0 1 0-1 .5.5 0 0 1-.46-.302l-.761-1.77a1.964 1.964 0 0 0-.453-.618A5.984 5.984 0 0 1 2 6zm6-5a5 5 0 0 0-3.479 8.592c.263.254.514.564.676.941L5.83 12h4.342l.632-1.467c.162-.377.413-.687.676-.941A5 5 0 0 0 8 1z" />
                            </svg>
                        </div>
                        <h4 class="mb-3">Innovation</h4>
                        <p>We're constantly pushing the boundaries of what's possible with business technology, exploring new ideas and approaches to solve complex problems.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                            </svg>
                        </div>
                        <h4 class="mb-3">Customer Focus</h4>
                        <p>Everything we do starts with understanding our customers' needs. We're committed to delivering solutions that create real value for your business.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-shield-check" viewBox="0 0 16 16">
                                <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM8 5.139a.97.97 0 0 0-.74.376L5.748 7.5l-1.502-.638a.97.97 0 0 0-1.276.297 1.03 1.03 0 0 0 .17 1.314l2.148 2.157a.97.97 0 0 0 1.36.026l3.612-3.57a1.03 1.03 0 0 0-.097-1.45.97.97 0 0 0-1.163-.096z" />
                            </svg>
                        </div>
                        <h4 class="mb-3">Integrity</h4>
                        <p>We believe in doing business the right way. That means being transparent, honest, and accountable in everything we do, from how we build our products to how we interact with our customers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Journey Section -->
    <section class="py-5 bg-light-custom">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-6 mx-auto text-center">
                    <h2 class="section-title text-center">Our Journey</h2>
                    <p class="lead">The key milestones that have shaped ePATNER into what it is today.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <ul class="timeline">
                        <li class="timeline-item">
                            <div class="timeline-badge">
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4 class="timeline-title">2018: The Beginning</h4>
                                </div>
                                <div class="timeline-body">
                                    <p>ePATNER was founded with a vision to make advanced business automation accessible to companies of all sizes.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-badge">
                                <i class="bi bi-code-slash"></i>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4 class="timeline-title">2019: First Product Launch</h4>
                                </div>
                                <div class="timeline-body">
                                    <p>We launched our first ERP module, focusing on inventory management and financial reporting.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-badge">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4 class="timeline-title">2020: Rapid Growth</h4>
                                </div>
                                <div class="timeline-body">
                                    <p>Our customer base grew to over 1,000 businesses as we expanded our product offerings and enhanced our AI capabilities.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-badge">
                                <i class="bi bi-award"></i>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4 class="timeline-title">2021: Industry Recognition</h4>
                                </div>
                                <div class="timeline-body">
                                    <p>ePATNER received multiple industry awards for innovation in business automation and AI technology.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-badge">
                                <i class="bi bi-globe"></i>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4 class="timeline-title">2022: Global Expansion</h4>
                                </div>
                                <div class="timeline-body">
                                    <p>We expanded our operations globally, opening offices in multiple countries and supporting businesses across diverse markets.</p>
                                </div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-badge">
                                <i class="bi bi-rocket"></i>
                            </div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h4 class="timeline-title">2023: The Future</h4>
                                </div>
                                <div class="timeline-body">
                                    <p>Today, we continue to innovate and expand our platform, helping thousands of businesses worldwide achieve their goals through intelligent automation.</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Leadership Team Section -->
    <!-- <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-6 mx-auto text-center">
                    <h2 class="section-title text-center">Our Leadership Team</h2>
                    <p class="lead">Meet the talented individuals driving ePATNER's vision and success.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <div class="team-img-container">
                            <img src="/images/team-1.jpg" alt="CEO" class="team-img">
                            <div class="team-social">
                                <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-envelope"></i></a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h5 class="mb-1">Sarah Johnson</h5>
                            <p class="text-muted mb-2">Chief Executive Officer</p>
                            <p class="small">With over 20 years of experience in technology and business leadership, Sarah drives ePATNER's strategic vision.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <div class="team-img-container">
                            <img src="/images/team-2.jpg" alt="CTO" class="team-img">
                            <div class="team-social">
                                <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-envelope"></i></a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h5 class="mb-1">Michael Chen</h5>
                            <p class="text-muted mb-2">Chief Technology Officer</p>
                            <p class="small">Michael leads our engineering team, bringing expertise in AI, machine learning, and enterprise software development.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <div class="team-img-container">
                            <img src="/images/team-3.jpg" alt="COO" class="team-img">
                            <div class="team-social">
                                <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-envelope"></i></a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h5 class="mb-1">Priya Patel</h5>
                            <p class="text-muted mb-2">Chief Operations Officer</p>
                            <p class="small">Priya ensures our operations run smoothly and efficiently, with a focus on delivering exceptional customer experiences.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <div class="team-img-container">
                            <img src="/images/team-4.jpg" alt="CPO" class="team-img">
                            <div class="team-social">
                                <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="bi bi-envelope"></i></a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h5 class="mb-1">David Rodriguez</h5>
                            <p class="text-muted mb-2">Chief Product Officer</p>
                            <p class="small">David oversees product strategy and development, ensuring our solutions meet the evolving needs of our customers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- CTA Section -->
    <section class="py-5 bg-light-custom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3">Ready to Transform Your Business?</h2>
                    <p class="lead mb-4">Join thousands of businesses that have improved their operations with ePATNER's comprehensive ERP solution.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#" class="btn btn-primary-custom">Get Started Today</a>
                        <a href="/contact" class="btn btn-outline-custom">Contact Our Team</a>
                    </div>
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
                        <a href="#" class="me-3 text-white">
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
                        <li class="mb-2"><a href="/" class="text-white text-decoration-none">Home</a></li>
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
                    <p class="mb-0">&copy; 2023 ePATNER Inc. All rights reserved.</p>
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

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar scroll effect
            const navbar = document.querySelector('.navbar');
            const backToTopButton = document.getElementById('backToTop');

            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    navbar.classList.add('scrolled');
                    backToTopButton.style.opacity = '1';
                } else {
                    navbar.classList.remove('scrolled');
                    backToTopButton.style.opacity = '0';
                }
            });

            // Back to top functionality
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
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
        });
    </script>
</body>

</html>