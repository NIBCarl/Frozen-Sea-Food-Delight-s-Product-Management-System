<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Products - Frozen Seafood Delight</title>
    
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
            background: #f8fafc;
            color: #333;
        }
        
        /* Navigation - Same as welcome page */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
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
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: #0284c7;
        }
        
        .btn {
            padding: 0.65rem 1.75rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
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
        
        /* Page Header */
        .page-header {
            padding: 8rem 2rem 3rem;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.25rem;
            color: #64748b;
        }
        
        /* Filters & Search */
        .filters-section {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 1rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }
        
        /* Products Grid */
        .products-container {
            max-width: 1400px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(2, 132, 199, 0.15);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #06b6d4, #0284c7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .product-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            background: linear-gradient(135deg, #06b6d4, #0284c7);
        }
        
        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #0284c7;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-category {
            color: #0284c7;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0.5rem 0;
        }
        
        .product-description {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .product-meta {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .meta-tag {
            padding: 0.25rem 0.75rem;
            background: #f1f5f9;
            border-radius: 50px;
            font-size: 0.75rem;
            color: #475569;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0284c7;
        }
        
        .add-to-cart-btn {
            padding: 0.65rem 1.5rem;
            background: #0284c7;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .add-to-cart-btn:hover {
            background: #0369a1;
            transform: scale(1.05);
        }
        
        .add-to-cart-btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Login Required Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            max-width: 500px;
            text-align: center;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-content h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #0f172a;
        }
        
        .modal-content p {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .loading {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .no-products {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .no-products-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .no-products h3 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        
        .no-products p {
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
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
                <li><a href="/public/products" class="active">Browse Products</a></li>
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

    <!-- Page Header -->
    <div class="page-header">
        <h1>Browse Our Premium Seafood</h1>
        <p>Discover fresh frozen seafood products delivered to your door</p>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="search-bar">
            <input 
                type="text" 
                class="search-input" 
                id="searchInput"
                placeholder="Search for seafood products..."
            >
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All Products</button>
            <button class="filter-btn" data-filter="frozen">❄️ Frozen</button>
            <button class="filter-btn" data-filter="grade-a">⭐ Grade A</button>
            <button class="filter-btn" data-filter="featured">🏆 Featured</button>
        </div>
    </div>

    <!-- Products Container -->
    <div class="products-container">
        <div id="productsGrid" class="products-grid">
            <div class="loading">Loading products...</div>
        </div>
    </div>

    <!-- Login Required Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔒</div>
            <h2>Login Required</h2>
            <p>Please create an account or login to add products to your cart and place orders.</p>
            <div class="modal-actions">
                <a href="{{ url('/login') }}" class="btn btn-primary">Login</a>
                <a href="{{ url('/register') }}" class="btn btn-outline">Sign Up</a>
            </div>
        </div>
    </div>

    <script>
        let allProducts = [];
        let currentFilter = 'all';
        const isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};

        // Fetch products from API
        async function fetchProducts() {
            try {
                console.log('Fetching products from: /api/v1/public/products');
                const response = await fetch('/api/v1/public/products', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('API Response:', data);
                
                // Handle Laravel pagination response
                if (data.data && Array.isArray(data.data)) {
                    allProducts = data.data;
                } else if (Array.isArray(data)) {
                    allProducts = data;
                } else {
                    allProducts = [];
                }
                
                console.log('Products loaded:', allProducts.length);
                renderProducts(allProducts);
            } catch (error) {
                console.error('Error fetching products:', error);
                document.getElementById('productsGrid').innerHTML = `
                    <div class="no-products">
                        <div class="no-products-icon">😞</div>
                        <h3>Unable to load products</h3>
                        <p>${error.message}</p>
                        <p style="font-size: 0.9rem; margin-top: 1rem;">Check the browser console for details</p>
                    </div>
                `;
            }
        }

        // Render products
        function renderProducts(products) {
            const grid = document.getElementById('productsGrid');
            
            if (!products || products.length === 0) {
                grid.innerHTML = `
                    <div class="no-products">
                        <div class="no-products-icon">📦</div>
                        <h3>No products found</h3>
                        <p>Try adjusting your search or filters</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = products.map(product => {
                // Get product image path
                const imagePath = product.primary_image?.path || product.primaryImage?.path || '/images/placeholder-product.jpg';
                const imageUrl = imagePath.startsWith('http') ? imagePath : (imagePath.startsWith('/storage') ? imagePath : `/storage/${imagePath}`);
                
                return `
                <div class="product-card">
                    <div class="product-image">
                        ${product.is_frozen ? '<div class="product-badge">❄️ Frozen</div>' : ''}
                        ${imagePath !== '/images/placeholder-product.jpg' 
                            ? `<img src="${imageUrl}" alt="${product.name}" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'product-image-placeholder\'>🐟</div>';" />` 
                            : '<div class="product-image-placeholder">🐟</div>'}
                    </div>
                    <div class="product-info">
                        <div class="product-category">${product.category?.name || 'Seafood'}</div>
                        <h3 class="product-name">${product.name}</h3>
                        <p class="product-description">${product.description || 'Premium quality seafood product'}</p>
                        <div class="product-meta">
                            ${product.fish_type ? `<span class="meta-tag">🐠 ${product.fish_type}</span>` : ''}
                            ${product.freshness_grade ? `<span class="meta-tag">⭐ Grade ${product.freshness_grade}</span>` : ''}
                            ${product.weight_kg ? `<span class="meta-tag">⚖️ ${product.weight_kg} kg</span>` : ''}
                            ${product.origin_waters ? `<span class="meta-tag">🌊 ${product.origin_waters}</span>` : ''}
                        </div>
                        <div class="product-footer">
                            <div class="product-price">₱${parseFloat(product.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                            <button 
                                class="add-to-cart-btn" 
                                onclick="handleAddToCart(${product.id})"
                                ${product.stock_quantity <= 0 ? 'disabled' : ''}
                            >
                                ${product.stock_quantity > 0 ? '🛒 Add to Cart' : 'Out of Stock'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
        }

        // Handle add to cart
        function handleAddToCart(productId) {
            if (!isAuthenticated) {
                document.getElementById('loginModal').classList.add('active');
                return;
            }
            
            // If authenticated, redirect to customer products page
            window.location.href = '/dashboard';
        }

        // Close modal when clicking outside
        document.getElementById('loginModal').addEventListener('click', (e) => {
            if (e.target.id === 'loginModal') {
                e.target.classList.remove('active');
            }
        });

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const filter = btn.dataset.filter;
                currentFilter = filter;
                
                let filtered = allProducts;
                if (filter === 'frozen') {
                    filtered = allProducts.filter(p => p.is_frozen);
                } else if (filter === 'grade-a') {
                    filtered = allProducts.filter(p => p.freshness_grade === 'A');
                } else if (filter === 'featured') {
                    filtered = allProducts.filter(p => p.featured);
                }
                
                renderProducts(filtered);
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            let filtered = allProducts.filter(product => 
                product.name.toLowerCase().includes(searchTerm) ||
                (product.description && product.description.toLowerCase().includes(searchTerm)) ||
                (product.fish_type && product.fish_type.toLowerCase().includes(searchTerm))
            );
            renderProducts(filtered);
        });

        // Load products on page load
        fetchProducts();
    </script>
</body>
</html>
