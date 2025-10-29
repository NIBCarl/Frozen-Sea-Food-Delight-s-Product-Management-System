<template>
  <div class="app-layout">
    <!-- Mobile Header with Burger Menu -->
    <header class="mobile-header" :class="{ 'header-elevated': scrolled }">
      <button class="burger-btn" @click="toggleSidebar" aria-label="Toggle menu">
        <span class="burger-line" :class="{ 'burger-open': sidebarOpen }"></span>
        <span class="burger-line" :class="{ 'burger-open': sidebarOpen }"></span>
        <span class="burger-line" :class="{ 'burger-open': sidebarOpen }"></span>
      </button>
      
      <div class="mobile-logo">
        <v-icon size="24" color="primary">mdi-fish</v-icon>
        <span>Seafood</span>
      </div>
      
      <div class="mobile-actions">
        <button v-if="isCustomer" class="mobile-cart-btn" @click="goToCart">
          <v-icon size="20">mdi-cart</v-icon>
          <span v-if="cartStore.itemCount > 0" class="cart-count">{{ cartStore.itemCount }}</span>
        </button>
        <button class="mobile-profile-btn" @click="toggleProfileMenu">
          <v-icon size="20">mdi-account-circle</v-icon>
        </button>
      </div>
    </header>

    <!-- Overlay for Mobile Sidebar -->
    <div 
      v-if="sidebarOpen" 
      class="sidebar-overlay"
      @click="closeSidebar"
    ></div>

    <!-- Responsive Sidebar -->
    <aside class="app-sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <!-- Sidebar Header -->
      <div class="sidebar-header">
        <div class="sidebar-logo">
          <div class="logo-icon">
            <v-icon size="32" color="white">mdi-fish</v-icon>
          </div>
          <div class="logo-text">
            <h2>Seafood</h2>
            <p>Inventory System</p>
          </div>
        </div>
        <button class="sidebar-close-btn" @click="closeSidebar">
          <v-icon size="24">mdi-close</v-icon>
        </button>
      </div>

      <!-- User Profile Section -->
      <div class="sidebar-profile">
        <div class="profile-avatar">
          {{ userInitial }}
        </div>
        <div class="profile-info">
          <p class="profile-name">{{ authStore.user?.name }}</p>
          <p class="profile-role">{{ userRole }}</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <!-- Admin Navigation -->
        <template v-if="isAdmin">
          <router-link to="/admin/dashboard" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-view-dashboard</v-icon>
            <span>Dashboard</span>
          </router-link>
          <router-link to="/products" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-package-variant</v-icon>
            <span>Products</span>
          </router-link>
          <router-link to="/categories" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-tag-multiple</v-icon>
            <span>Categories</span>
          </router-link>
          <router-link to="/admin/orders" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-cart</v-icon>
            <span>Orders</span>
          </router-link>
          <router-link to="/admin/deliveries" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-truck-delivery</v-icon>
            <span>Deliveries</span>
          </router-link>
          <router-link to="/users" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-account-group</v-icon>
            <span>Users</span>
          </router-link>
          <router-link to="/stock" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-package-down</v-icon>
            <span>Stock</span>
          </router-link>
          <router-link to="/reports" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-chart-bar</v-icon>
            <span>Reports</span>
          </router-link>
        </template>

        <!-- Customer Navigation -->
        <template v-if="isCustomer">
          <router-link to="/customer/products" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-fish</v-icon>
            <span>Browse Products</span>
          </router-link>
          <router-link to="/customer/cart" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-cart</v-icon>
            <span>Shopping Cart</span>
            <span v-if="cartStore.itemCount > 0" class="nav-badge">{{ cartStore.itemCount }}</span>
          </router-link>
          <router-link to="/customer/orders" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-clipboard-text</v-icon>
            <span>My Orders</span>
          </router-link>
        </template>

        <!-- Supplier Navigation -->
        <template v-if="isSupplier">
          <router-link to="/supplier/shipments" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-truck</v-icon>
            <span>Shipments</span>
          </router-link>
          <router-link to="/products" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-package-variant</v-icon>
            <span>Products</span>
          </router-link>
        </template>

        <!-- Delivery Personnel Navigation -->
        <template v-if="isDelivery">
          <router-link to="/delivery/today" class="nav-item" @click="handleNavClick">
            <v-icon class="nav-icon" size="20">mdi-truck-fast</v-icon>
            <span>Today's Deliveries</span>
          </router-link>
        </template>

        <!-- Common Navigation -->
        <div class="nav-divider"></div>
        <router-link to="/settings" class="nav-item" @click="handleNavClick">
          <v-icon class="nav-icon" size="20">mdi-cog</v-icon>
          <span>Settings</span>
        </router-link>
      </nav>

      <!-- Sidebar Footer -->
      <div class="sidebar-footer">
        <button class="logout-btn" @click="handleLogout">
          <v-icon size="20">mdi-logout</v-icon>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="app-main" :class="{ 'main-shifted': sidebarOpen && !isMobile }">
      <div class="main-content">
        <slot></slot>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();

const sidebarOpen = ref(false);
const profileMenuOpen = ref(false);
const scrolled = ref(false);
const windowWidth = ref(window.innerWidth);

const isMobile = computed(() => windowWidth.value < 1024);

const isAdmin = computed(() => authStore.hasRole('admin'));
const isCustomer = computed(() => authStore.hasRole('customer'));
const isSupplier = computed(() => authStore.hasRole('supplier'));
const isDelivery = computed(() => authStore.hasRole('delivery_personnel'));

const userInitial = computed(() => {
  return authStore.user?.name?.charAt(0).toUpperCase() || 'U';
});

const userRole = computed(() => {
  if (isAdmin.value) return 'Administrator';
  if (isCustomer.value) return 'Customer';
  if (isSupplier.value) return 'Supplier';
  if (isDelivery.value) return 'Delivery';
  return 'User';
});

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
  if (sidebarOpen.value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
};

const closeSidebar = () => {
  sidebarOpen.value = false;
  document.body.style.overflow = '';
};

const handleNavClick = () => {
  if (isMobile.value) {
    closeSidebar();
  }
};

const toggleProfileMenu = () => {
  profileMenuOpen.value = !profileMenuOpen.value;
};

const goToCart = () => {
  router.push('/customer/cart');
};

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const handleScroll = () => {
  scrolled.value = window.scrollY > 10;
};

const handleResize = () => {
  windowWidth.value = window.innerWidth;
  if (!isMobile.value) {
    closeSidebar();
  }
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  window.addEventListener('resize', handleResize);
  
  // Load cart for customers
  if (isCustomer.value) {
    cartStore.fetchCart();
  }
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', handleResize);
  document.body.style.overflow = '';
});
</script>

<style scoped>
/* Layout Container */
.app-layout {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
}

/* Mobile Header */
.mobile-header {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 64px;
  background: white;
  z-index: 1000;
  padding: 0 1rem;
  align-items: center;
  justify-content: space-between;
  transition: all 200ms;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
}

.mobile-header.header-elevated {
  box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.1);
}

/* Burger Button */
.burger-btn {
  width: 40px;
  height: 40px;
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 0;
  border-radius: 8px;
  transition: background 200ms;
}

.burger-btn:hover {
  background: #f8fafc;
}

.burger-line {
  width: 24px;
  height: 2px;
  background: #0f172a;
  border-radius: 2px;
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

.burger-line:nth-child(1).burger-open {
  transform: translateY(8px) rotate(45deg);
}

.burger-line:nth-child(2).burger-open {
  opacity: 0;
  transform: translateX(-10px);
}

.burger-line:nth-child(3).burger-open {
  transform: translateY(-8px) rotate(-45deg);
}

.mobile-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
}

.mobile-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.mobile-cart-btn,
.mobile-profile-btn {
  width: 40px;
  height: 40px;
  background: #f8fafc;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 200ms;
}

.mobile-cart-btn:hover,
.mobile-profile-btn:hover {
  background: #e2e8f0;
}

.cart-count {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 18px;
  height: 18px;
  background: linear-gradient(135deg, #dc2626, #f87171);
  color: white;
  font-size: 0.625rem;
  font-weight: 700;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px 0 rgba(220, 38, 38, 0.4);
}

/* Sidebar Overlay */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1050;
  animation: fadeIn 200ms;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Sidebar */
.app-sidebar {
  width: 280px;
  background: white;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 1100;
  transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    0 -2px 8px 0 rgba(0, 0, 0, 0.02),
    0 4px 12px 0 rgba(0, 0, 0, 0.08);
}

/* Sidebar Header */
.sidebar-header {
  padding: 1.5rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #1976d2, #2196f3);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 4px 8px 0 rgba(0, 0, 0, 0.15);
}

.logo-text h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.2;
}

.logo-text p {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0;
}

.sidebar-close-btn {
  display: none;
  width: 36px;
  height: 36px;
  background: #f8fafc;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all 200ms;
}

.sidebar-close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

/* Profile Section */
.sidebar-profile {
  padding: 1.5rem 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-bottom: 1px solid #e2e8f0;
}

.profile-avatar {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #1976d2, #2196f3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 8px 0 rgba(25, 118, 210, 0.3);
}

.profile-info {
  flex: 1;
  min-width: 0;
}

.profile-name {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-role {
  font-size: 0.8125rem;
  color: #1976d2;
  margin: 0;
  font-weight: 500;
}

/* Navigation */
.sidebar-nav {
  flex: 1;
  padding: 1rem 0.5rem;
  overflow-y: auto;
  overflow-x: hidden;
}

.sidebar-nav::-webkit-scrollbar {
  width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  margin-bottom: 0.25rem;
  border-radius: 10px;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #475569;
  text-decoration: none;
  transition: all 200ms;
  position: relative;
}

.nav-item:hover {
  background: #f8fafc;
  color: #0f172a;
}

.nav-item.router-link-active {
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  color: #1976d2;
  font-weight: 600;
}

.nav-item.router-link-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: linear-gradient(180deg, #1976d2, #2196f3);
  border-radius: 0 2px 2px 0;
}

.nav-icon {
  flex-shrink: 0;
}

.nav-badge {
  margin-left: auto;
  padding: 0.25rem 0.5rem;
  background: linear-gradient(135deg, #dc2626, #f87171);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 10px;
  min-width: 24px;
  text-align: center;
  box-shadow: 0 2px 4px 0 rgba(220, 38, 38, 0.3);
}

.nav-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 1rem 0.5rem;
}

/* Sidebar Footer */
.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
}

.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #dc2626;
  cursor: pointer;
  transition: all 200ms;
}

.logout-btn:hover {
  background: #fef2f2;
  border-color: #fecaca;
}

/* Main Content */
.app-main {
  flex: 1;
  margin-left: 280px;
  min-height: 100vh;
  transition: margin-left 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

.main-content {
  width: 100%;
  min-height: 100vh;
}

/* Mobile Responsive */
@media (max-width: 1023px) {
  .mobile-header {
    display: flex;
  }

  .sidebar-overlay {
    display: block;
    opacity: 0;
    pointer-events: none;
    transition: opacity 200ms;
  }

  .sidebar-overlay:is(.sidebar-open ~ *) {
    opacity: 1;
    pointer-events: all;
  }

  .app-sidebar {
    transform: translateX(-100%);
  }

  .app-sidebar.sidebar-open {
    transform: translateX(0);
  }

  .sidebar-close-btn {
    display: flex;
  }

  .app-main {
    margin-left: 0;
    padding-top: 64px;
  }

  .main-content {
    min-height: calc(100vh - 64px);
  }
}

/* Tablet Responsive */
@media (max-width: 768px) {
  .mobile-logo span {
    display: none;
  }

  .sidebar-profile {
    padding: 1rem;
  }

  .profile-avatar {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }

  .profile-name {
    font-size: 0.875rem;
  }

  .profile-role {
    font-size: 0.75rem;
  }

  .nav-item {
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
  }
}

/* Desktop Hover Enhancements */
@media (min-width: 1024px) {
  .app-sidebar {
    transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1), box-shadow 200ms;
  }

  .app-sidebar:hover {
    box-shadow: 
      0 -2px 8px 0 rgba(0, 0, 0, 0.03),
      0 8px 20px 0 rgba(0, 0, 0, 0.12);
  }

  .nav-item {
    position: relative;
    overflow: hidden;
  }

  .nav-item::after {
    content: '';
    position: absolute;
    left: 0;
    right: 100%;
    bottom: 0;
    height: 2px;
    background: linear-gradient(90deg, #1976d2, #2196f3);
    transition: right 200ms;
  }

  .nav-item:hover::after {
    right: 0;
  }
}

/* Print Styles */
@media print {
  .mobile-header,
  .app-sidebar {
    display: none !important;
  }

  .app-main {
    margin-left: 0 !important;
    padding-top: 0 !important;
  }
}
</style>

