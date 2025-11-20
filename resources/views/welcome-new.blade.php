<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frozen Seafood Delight - Premium Quality Seafood</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled {
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.15);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0284c7;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0284c7, #06b6d4);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
            list-style: none;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 0.95rem;
        }
        
        .nav-links a:hover {
            color: #0284c7;
        }
        
        .btn {
            padding: 0.65rem 1.75rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0284c7, #06b6d4);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #0284c7;
            border: 2px solid #0284c7;
        }
        
        .btn-outline:hover {
            background: #0284c7;
            color: white;
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 6rem 2rem 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%230284c7" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat;
            background-size: cover;
            opacity: 0.5;
        }
        
        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }
        
        .hero-content h1 .highlight {
            color: #0284c7;
            position: relative;
        }
        
        .hero-content h1 .highlight::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 12px;
            background: rgba(2, 132, 199, 0.2);
            z-index: -1;
        }
        
        .hero-content p {
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.8;
        }
        
        .hero-actions {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        
        .hero-image {
            position: relative;
            height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-image-container {
            width: 100%;
            height: 100%;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 60px rgba(2, 132, 199, 0.3);
            background: linear-gradient(135deg, #06b6d4, #0284c7);
        }
        
        .hero-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(2, 132, 199, 0.1);
            color: #0284c7;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: white;
        }
        
        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 4rem;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }
        
        .section-header p {
            font-size: 1.1rem;
            color: #64748b;
        }
        
        .features-grid {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            padding: 2.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(2, 132, 199, 0.15);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0284c7, #06b6d4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: #64748b;
            line-height: 1.8;
        }
        
        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #0284c7, #06b6d4);
            color: white;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .cta p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        /* Footer */
        .footer {
            padding: 3rem 2rem;
            background: #0f172a;
            color: #94a3b8;
            text-align: center;
        }
        
        .footer p {
            margin: 0.5rem 0;
        }
        
        /* Responsive */
        @media (max-width: 968px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-actions {
                justify-content: center;
            }
            
            .hero-image {
                height: 400px;
            }
            
            .nav-links {
                display: none;
            }
        }
        
        .feature-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(6, 182, 212, 0.1);
            color: #0284c7;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="logo">
                <div class="logo-icon">🐟</div>
                <span>Seafood Delight</span>
            </a>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/public/products">Browse Products</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
            </ul>
            <div style="display: flex; gap: 1rem;">
                @guest
                    <a href="{{ url('/login') }}" class="btn btn-outline">Login</a>
                    <a href="{{ url('/register') }}" class="btn btn-primary">Sign Up</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                @endguest
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="badge">🌊 Premium Quality Guaranteed</div>
                <h1>
                    Fresh <span class="highlight">Frozen Seafood</span><br>
                    Delivered to Your Door
                </h1>
                <p>
                    Experience the finest selection of frozen seafood products. 
                    Premium quality, sustainably sourced, and delivered with care. 
                    Your trusted partner for fresh frozen delights.
                </p>
                <div class="hero-actions">
                    <a href="/public/products" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.85rem 2rem;">
                        Browse Products →
                    </a>
                    <a href="#features" class="btn btn-outline" style="font-size: 1.1rem; padding: 0.85rem 2rem;">
                        Learn More
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-image-container">
                    <img src="{{ asset('images/hero-seafood.png') }}" alt="Fresh Premium Seafood" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Why Choose Seafood Delight?</h2>
            <p>We provide the best quality frozen seafood with unmatched service and reliability</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">❄️</div>
                <h3>Premium Frozen Quality</h3>
                <p>Our advanced freezing technology preserves freshness, flavor, and nutritional value. Every product meets the highest quality standards.</p>
                <span class="feature-badge">Grade A+ Products</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌊</div>
                <h3>Sustainably Sourced</h3>
                <p>We partner with certified suppliers who practice responsible fishing. Know exactly where your seafood comes from.</p>
                <span class="feature-badge">Eco-Friendly</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3>Fast Delivery</h3>
                <p>Temperature-controlled delivery ensures your seafood arrives in perfect condition. Track your order in real-time.</p>
                <span class="feature-badge">Same Day Available</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure Ordering</h3>
                <p>Shop with confidence using our secure payment system. Multiple payment options including COD and GCash.</p>
                <span class="feature-badge">100% Secure</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📦</div>
                <h3>Wide Selection</h3>
                <p>From premium tuna to fresh shrimp, discover a vast variety of seafood products for every occasion and recipe.</p>
                <span class="feature-badge">50+ Products</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h3>Quality Guarantee</h3>
                <p>Not satisfied? We offer a hassle-free return policy. Your satisfaction is our top priority.</p>
                <span class="feature-badge">Money Back Guarantee</span>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta" id="about">
        <div style="max-width: 800px; margin: 0 auto;">
            <h2>Ready to Start Ordering?</h2>
            <p>Join thousands of satisfied customers who trust us for their seafood needs</p>
            <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
                <a href="{{ url('/register') }}" class="btn btn-primary" style="background: white; color: #0284c7;">
                    Create Account
                </a>
                <a href="/public/products" class="btn btn-outline" style="border-color: white; color: white;">
                    View Products
                </a>
            </div>
            <p style="margin-top: 2rem; font-size: 0.95rem; opacity: 0.9;">
                🔐 Login required to add items to cart and place orders
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p><strong>Frozen Seafood Delight</strong> - Premium Quality Frozen Seafood</p>
        <p>© {{ date('Y') }} Seafood Delight. All rights reserved.</p>
        <p style="margin-top: 1rem; font-size: 0.9rem;">
            Developed by Justin S. Llorado, Aaron Andrie A. Javier, Christian Aranas, Kyllie Sheena Orillo
        </p>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
