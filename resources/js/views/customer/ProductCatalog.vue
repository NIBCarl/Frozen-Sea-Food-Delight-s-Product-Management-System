<template>
  <div class="product-catalog-enhanced">
    <!-- Hero Header -->
    <div class="catalog-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="header-title">Fresh Seafood Market</h1>
          <p class="header-subtitle">Premium frozen seafood delivered fresh to your door</p>
        </div>
        <div class="header-badge">
          <v-icon size="20" color="white">mdi-fish</v-icon>
          <span>{{ filteredProducts.length }} Products</span>
        </div>
      </div>
    </div>

    <div class="catalog-container">
      <!-- Search and Filters -->
      <div class="filters-section">
        <div class="search-wrapper">
          <v-icon class="search-icon" size="20">mdi-magnify</v-icon>
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search seafood products..."
            @input="debouncedSearch"
          />
          <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''">
            <v-icon size="18">mdi-close</v-icon>
          </button>
        </div>

        <div class="filters-row">
          <select v-model="selectedCategory" class="filter-select" @change="filterProducts">
            <option :value="null">All Categories</option>
            <option v-for="cat in categoryStore.categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>

          <select v-model="sortBy" class="filter-select" @change="filterProducts">
            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
              {{ option.title }}
            </option>
          </select>

          <button class="filter-toggle-btn">
            <v-icon size="18">mdi-tune</v-icon>
            Filters
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="productStore.loading" class="loading-grid">
        <div v-for="n in 8" :key="n" class="skeleton-card">
          <div class="skeleton-image"></div>
          <div class="skeleton-content">
            <div class="skeleton-line skeleton-line-title"></div>
            <div class="skeleton-line skeleton-line-text"></div>
            <div class="skeleton-line skeleton-line-price"></div>
          </div>
        </div>
      </div>

      <!-- Products Grid -->
      <div v-else-if="filteredProducts.length > 0" class="products-grid">
        <div
          v-for="product in filteredProducts"
          :key="product.id"
          class="product-card-modern"
        >
          <!-- Product Image -->
          <div class="product-image-section">
            <img
              :src="product.primary_image?.path || '/images/placeholder-product.jpg'"
              :alt="product.name"
              class="product-image"
            />
            
            <!-- Badges -->
            <div class="product-badges">
              <span v-if="product.stock_quantity < 10" class="product-badge badge-warning">
                <v-icon size="12">mdi-alert</v-icon>
                Low Stock
              </span>
              <span v-if="product.is_expiring_soon" class="product-badge badge-error">
                <v-icon size="12">mdi-clock-alert</v-icon>
                Expiring Soon
              </span>
            </div>

            <!-- Quick View Overlay -->
            <div class="product-overlay">
              <button class="btn-quick-view" @click="viewProduct(product)">
                <v-icon size="20">mdi-eye</v-icon>
                Quick View
              </button>
            </div>
          </div>

          <!-- Product Details -->
          <div class="product-details-section">
            <div class="product-category">{{ product.category?.name }}</div>
            <h3 class="product-name">{{ product.name }}</h3>
            <p class="product-description">{{ truncate(product.description, 60) }}</p>

            <!-- Price and Weight -->
            <div class="product-pricing">
              <div class="price-main">
                <span class="currency">₱</span>
                <span class="price">{{ product.price }}</span>
              </div>
              <div class="price-unit">per {{ product.weight }}kg</div>
            </div>

            <!-- Meta Info -->
            <div class="product-meta">
              <div class="meta-item">
                <v-icon size="14">mdi-package-variant</v-icon>
                <span>{{ product.stock_quantity }} in stock</span>
              </div>
              <div class="meta-item">
                <v-icon size="14">mdi-account-circle</v-icon>
                <span>Supplier: {{ product.creator?.name }}</span>
              </div>
              <div v-if="product.expiration_date" class="meta-item">
                <v-icon size="14">mdi-calendar</v-icon>
                <span>Exp: {{ formatDate(product.expiration_date) }}</span>
              </div>
            </div>

            <!-- Add to Cart Button -->
            <button
              class="btn-add-cart"
              :disabled="product.stock_quantity === 0 || !product.is_available"
              @click="addToCart(product)"
            >
              <v-icon size="18">mdi-cart-plus</v-icon>
              <span>Add to Cart</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <div class="empty-icon">
          <v-icon size="80" color="#94a3b8">mdi-fish-off</v-icon>
        </div>
        <h3 class="empty-title">No Products Found</h3>
        <p class="empty-text">Try adjusting your search or filters to find what you're looking for</p>
        <button class="btn-reset-filters" @click="resetFilters">
          <v-icon size="18">mdi-refresh</v-icon>
          Reset Filters
        </button>
      </div>
    </div>

    <!-- Floating Cart Button -->
    <button class="cart-fab" @click="goToCart">
      <div class="cart-icon-wrapper">
        <v-icon size="24" color="white">mdi-cart</v-icon>
        <span v-if="cartStore.itemCount > 0" class="cart-badge">
          {{ cartStore.itemCount }}
        </span>
      </div>
    </button>

    <!-- Snackbar for notifications -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
      location="top"
    >
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">Close</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '@/stores/products'
import { useCategoryStore } from '@/stores/categories'
import { useCartStore } from '@/stores/cart'

const router = useRouter()
const productStore = useProductStore()
const categoryStore = useCategoryStore()
const cartStore = useCartStore()

const searchQuery = ref('')
const selectedCategory = ref(null)
const sortBy = ref('name')
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const sortOptions = [
  { title: 'Name (A-Z)', value: 'name' },
  { title: 'Price (Low to High)', value: 'price_asc' },
  { title: 'Price (High to Low)', value: 'price_desc' },
  { title: 'Newest First', value: 'newest' },
]

const filteredProducts = computed(() => {
  let products = productStore.availableProducts

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    products = products.filter(p => 
      p.name.toLowerCase().includes(query) ||
      p.description?.toLowerCase().includes(query)
    )
  }

  // Filter by category
  if (selectedCategory.value) {
    products = products.filter(p => p.category_id === selectedCategory.value)
  }

  // Sort products
  products = [...products].sort((a, b) => {
    switch (sortBy.value) {
      case 'price_asc':
        return a.price - b.price
      case 'price_desc':
        return b.price - a.price
      case 'newest':
        return new Date(b.created_at) - new Date(a.created_at)
      default:
        return a.name.localeCompare(b.name)
    }
  })

  return products
})

let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filterProducts()
  }, 300)
}

const filterProducts = async () => {
  await productStore.fetchProducts({
    search: searchQuery.value,
    category: selectedCategory.value,
  })
}

const truncate = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
}

const viewProduct = (product) => {
  router.push({ name: 'customer.product-detail', params: { id: product.id } })
}

const addToCart = async (product) => {
  try {
    await cartStore.addItem(product.id, 1)
    snackbar.value = {
      show: true,
      message: `${product.name} added to cart!`,
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: error.response?.data?.message || 'Failed to add to cart',
      color: 'error'
    }
  }
}

const goToCart = () => {
  router.push({ name: 'customer.cart' })
}

const resetFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = null;
  sortBy.value = 'name';
  filterProducts();
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

onMounted(async () => {
  await Promise.all([
    productStore.fetchProducts(),
    categoryStore.fetchCategories(),
    cartStore.fetchCart()
  ])
})
</script>

<style scoped>
.product-catalog-enhanced {
  min-height: 100vh;
  background: transparent;
  padding-bottom: 6rem;
}

/* Hero Header */
.catalog-header {
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 50%, #42a5f5 100%);
  padding: 3rem 2rem;
  position: relative;
  overflow: hidden;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.1) inset,
    0 4px 12px 0 rgba(0, 0, 0, 0.15);
}

.catalog-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 50%;
  background: linear-gradient(180deg, rgba(255,255,255,0.15) 0%, transparent 100%);
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 1;
}

.header-text {
  flex: 1;
}

.header-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: white;
  margin: 0 0 0.5rem 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.header-subtitle {
  font-size: 1.125rem;
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
}

.header-badge {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: white;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 4px 12px 0 rgba(0, 0, 0, 0.2);
}

.catalog-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

/* Filters Section */
.filters-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 
    0 -1px 3px 0 rgba(0, 0, 0, 0.02),
    0 2px 6px 0 rgba(0, 0, 0, 0.08);
}

.search-wrapper {
  position: relative;
  margin-bottom: 1rem;
}

.search-icon {
  position: absolute;
  left: 1.25rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3.5rem;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  color: #0f172a;
  transition: all 200ms;
  box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.search-input:focus {
  outline: none;
  background: white;
  border-color: #2196f3;
  box-shadow: 
    inset 0 2px 4px 0 rgba(0, 0, 0, 0.05),
    0 0 0 4px rgba(33, 150, 243, 0.1);
}

.search-input::placeholder {
  color: #94a3b8;
}

.search-clear {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: #e2e8f0;
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all 200ms;
}

.search-clear:hover {
  background: #cbd5e1;
  color: #475569;
}

.filters-row {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 1rem;
}

.filter-select {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  color: #0f172a;
  cursor: pointer;
  transition: all 200ms;
  box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.filter-select:focus {
  outline: none;
  border-color: #2196f3;
  background: white;
  box-shadow: 
    inset 0 2px 4px 0 rgba(0, 0, 0, 0.05),
    0 0 0 4px rgba(33, 150, 243, 0.1);
}

.filter-toggle-btn {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 200ms;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.filter-toggle-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.08);
}

/* Loading Skeleton */
.loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.skeleton-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.skeleton-image {
  height: 200px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-content {
  padding: 1.25rem;
}

.skeleton-line {
  height: 12px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  border-radius: 4px;
  margin-bottom: 0.75rem;
  animation: shimmer 1.5s infinite;
}

.skeleton-line-title {
  width: 70%;
  height: 16px;
}

.skeleton-line-text {
  width: 100%;
}

.skeleton-line-price {
  width: 40%;
  height: 20px;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Products Grid */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.product-card-modern {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    0 -1px 3px 0 rgba(0, 0, 0, 0.02),
    0 2px 6px 0 rgba(0, 0, 0, 0.08);
}

.product-card-modern:hover {
  transform: translateY(-8px);
  box-shadow: 
    0 -2px 8px 0 rgba(0, 0, 0, 0.03),
    0 12px 24px 0 rgba(0, 0, 0, 0.15);
}

.product-image-section {
  position: relative;
  height: 220px;
  overflow: hidden;
  background: #f8fafc;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 300ms;
}

.product-card-modern:hover .product-image {
  transform: scale(1.05);
}

.product-badges {
  position: absolute;
  top: 0.75rem;
  left: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  z-index: 2;
}

.product-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
}

.badge-warning {
  background: linear-gradient(135deg, #fbbf24, #fcd34d);
  color: #78350f;
}

.badge-error {
  background: linear-gradient(135deg, #f87171, #fca5a5);
  color: #7f1d1d;
}

.product-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.7));
  opacity: 0;
  transition: opacity 300ms;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 1rem;
}

.product-card-modern:hover .product-overlay {
  opacity: 1;
}

.btn-quick-view {
  padding: 0.625rem 1.25rem;
  background: white;
  border: none;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #0f172a;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transform: translateY(10px);
  transition: all 200ms;
  box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.3);
}

.product-card-modern:hover .btn-quick-view {
  transform: translateY(0);
}

.btn-quick-view:hover {
  background: #f8fafc;
}

.product-details-section {
  padding: 1.25rem;
}

.product-category {
  font-size: 0.75rem;
  font-weight: 600;
  color: #2196f3;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.product-name {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

.product-description {
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 1rem 0;
  min-height: 2.625rem;
}

.product-pricing {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.price-main {
  display: flex;
  align-items: flex-start;
}

.currency {
  font-size: 1rem;
  font-weight: 600;
  color: #1976d2;
  margin-top: 0.125rem;
}

.price {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1;
}

.price-unit {
  font-size: 0.8125rem;
  color: #64748b;
  padding-bottom: 0.25rem;
}

.product-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: #64748b;
}

.btn-add-cart {
  width: 100%;
  padding: 0.875rem;
  background: linear-gradient(135deg, #1976d2, #2196f3);
  border: none;
  border-radius: 10px;
  font-size: 0.9375rem;
  font-weight: 600;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 200ms;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 4px 12px 0 rgba(25, 118, 210, 0.3);
}

.btn-add-cart:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 6px 16px 0 rgba(25, 118, 210, 0.4);
}

.btn-add-cart:active:not(:disabled) {
  transform: translateY(0);
}

.btn-add-cart:disabled {
  background: linear-gradient(135deg, #94a3b8, #cbd5e1);
  cursor: not-allowed;
  opacity: 0.7;
  box-shadow: none;
}

/* Empty State */
.empty-state {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  margin-bottom: 1.5rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
}

.empty-text {
  font-size: 1rem;
  color: #64748b;
  margin: 0 0 1.5rem 0;
}

.btn-reset-filters {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 200ms;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.btn-reset-filters:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.08);
}

/* Floating Cart */
.cart-fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #1976d2, #2196f3);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: all 200ms;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 8px 20px 0 rgba(25, 118, 210, 0.4);
  z-index: 100;
}

.cart-fab:hover {
  transform: scale(1.1);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 12px 28px 0 rgba(25, 118, 210, 0.5);
}

.cart-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  background: linear-gradient(135deg, #dc2626, #f87171);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px 0 rgba(220, 38, 38, 0.4);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  }
}

@media (max-width: 768px) {
  .catalog-header {
    padding: 2rem 1rem;
  }
  
  .header-title {
    font-size: 1.75rem;
  }
  
  .header-subtitle {
    font-size: 1rem;
  }
  
  .header-badge {
    display: none;
  }
  
  .catalog-container {
    padding: 1rem;
  }
  
  .filters-row {
    grid-template-columns: 1fr;
  }
  
  .products-grid {
    grid-template-columns: 1fr;
  }
  
  .product-card-modern:hover {
    transform: translateY(-4px);
  }
  
  .cart-fab {
    width: 56px;
    height: 56px;
    bottom: 1.5rem;
    right: 1.5rem;
  }
}
</style>

