<template>
  <div class="admin-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Admin Dashboard</h1>
        <p class="dashboard-subtitle">Welcome back, {{ authStore.user?.name }}</p>
      </div>
      <div class="header-actions">
        <button class="btn-secondary-outline">
          <v-icon size="18">mdi-refresh</v-icon>
          Refresh
        </button>
        <button class="btn-primary-elevated">
          <v-icon size="18">mdi-download</v-icon>
          Export Report
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <!-- Total Orders Stat -->
      <div class="stat-card stat-card-primary">
        <div class="stat-icon-wrapper stat-icon-primary">
          <v-icon size="28" color="white">mdi-cart</v-icon>
        </div>
        <div class="stat-content">
          <p class="stat-label">Total Orders</p>
          <h2 class="stat-value">{{ stats.totalOrders }}</h2>
          <div class="stat-change stat-change-positive">
            <v-icon size="14">mdi-trending-up</v-icon>
            <span>+12.5% from last month</span>
          </div>
        </div>
      </div>

      <!-- Revenue Stat -->
      <div class="stat-card stat-card-success">
        <div class="stat-icon-wrapper stat-icon-success">
          <v-icon size="28" color="white">mdi-cash-multiple</v-icon>
        </div>
        <div class="stat-content">
          <p class="stat-label">Total Revenue</p>
          <h2 class="stat-value">₱{{ formatNumber(stats.totalRevenue) }}</h2>
          <div class="stat-change stat-change-positive">
            <v-icon size="14">mdi-trending-up</v-icon>
            <span>+8.2% from last month</span>
          </div>
        </div>
      </div>

      <!-- Products Stat -->
      <div class="stat-card stat-card-info">
        <div class="stat-icon-wrapper stat-icon-info">
          <v-icon size="28" color="white">mdi-package-variant</v-icon>
        </div>
        <div class="stat-content">
          <p class="stat-label">Products</p>
          <h2 class="stat-value">{{ stats.totalProducts }}</h2>
          <div class="stat-change stat-change-neutral">
            <v-icon size="14">mdi-minus</v-icon>
            <span>No change</span>
          </div>
        </div>
      </div>

      <!-- Low Stock Alert -->
      <div class="stat-card stat-card-warning">
        <div class="stat-icon-wrapper stat-icon-warning">
          <v-icon size="28" color="white">mdi-alert</v-icon>
        </div>
        <div class="stat-content">
          <p class="stat-label">Low Stock Items</p>
          <h2 class="stat-value">{{ stats.lowStockItems }}</h2>
          <div class="stat-change stat-change-negative">
            <v-icon size="14">mdi-alert-circle</v-icon>
            <span>Needs attention</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
      <!-- Recent Orders Section -->
      <div class="content-card content-card-wide">
        <div class="card-header">
          <div>
            <h3 class="card-title">Recent Orders</h3>
            <p class="card-subtitle">Latest customer orders</p>
          </div>
          <button class="btn-ghost">
            <span>View All</span>
            <v-icon size="16">mdi-arrow-right</v-icon>
          </button>
        </div>
        
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in recentOrders" :key="order.id" class="table-row-hover">
                <td>
                  <span class="order-id">#{{ order.order_number }}</span>
                </td>
                <td>
                  <div class="customer-info">
                    <div class="customer-avatar">
                      {{ order.customer_initial }}
                    </div>
                    <span>{{ order.customer_name }}</span>
                  </div>
                </td>
                <td class="text-muted">{{ formatDate(order.order_date) }}</td>
                <td class="text-semibold">₱{{ formatNumber(order.total_amount) }}</td>
                <td>
                  <span :class="['badge', `badge-${getStatusColor(order.status)}`]">
                    {{ order.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          
          <!-- Empty State -->
          <div v-if="recentOrders.length === 0" class="empty-state">
            <v-icon size="48" color="grey">mdi-cart-off</v-icon>
            <p>No recent orders</p>
          </div>
        </div>
      </div>

      <!-- Today's Deliveries -->
      <div class="content-card">
        <div class="card-header">
          <div>
            <h3 class="card-title">Today's Deliveries</h3>
            <p class="card-subtitle">{{ todayDeliveries.length }} scheduled</p>
          </div>
        </div>
        
        <div class="delivery-list">
          <div v-for="delivery in todayDeliveries" :key="delivery.id" class="delivery-item">
            <div class="delivery-icon">
              <v-icon size="20" color="primary">mdi-truck-fast</v-icon>
            </div>
            <div class="delivery-info">
              <p class="delivery-order">#{{ delivery.order_number }}</p>
              <p class="delivery-time">{{ delivery.scheduled_time }}</p>
            </div>
            <div :class="['delivery-status', `status-${delivery.status}`]">
              {{ delivery.status }}
            </div>
          </div>
          
          <!-- Empty State -->
          <div v-if="todayDeliveries.length === 0" class="empty-state-small">
            <v-icon size="32" color="grey">mdi-truck-off</v-icon>
            <p>No deliveries scheduled</p>
          </div>
        </div>
      </div>

      <!-- Low Stock Alerts -->
      <div class="content-card">
        <div class="card-header">
          <div>
            <h3 class="card-title">Stock Alerts</h3>
            <p class="card-subtitle">Products running low</p>
          </div>
        </div>
        
        <div class="stock-alerts">
          <div v-for="product in lowStockProducts" :key="product.id" class="stock-alert-item">
            <div class="product-thumb">
              <img :src="product.image || '/images/placeholder-product.jpg'" alt="Product">
            </div>
            <div class="product-details">
              <p class="product-name">{{ product.name }}</p>
              <div class="stock-progress">
                <div class="progress-bar-container">
                  <div 
                    class="progress-bar"
                    :style="{ width: `${(product.stock_quantity / product.min_stock_level) * 100}%` }"
                    :class="getStockClass(product.stock_quantity, product.min_stock_level)"
                  ></div>
                </div>
                <span class="stock-text">{{ product.stock_quantity }} units left</span>
              </div>
            </div>
            <button class="btn-restock" @click="navigateToProduct(product.id)" title="Restock product">
              <v-icon size="16">mdi-plus</v-icon>
            </button>
          </div>
          
          <!-- Empty State -->
          <div v-if="lowStockProducts.length === 0" class="empty-state-small">
            <v-icon size="32" color="success">mdi-check-circle</v-icon>
            <p>All products well stocked</p>
          </div>
        </div>
      </div>

      <!-- Expiring Products Widget -->
      <div class="widget-container widget-half">
        <ExpiringProductsWidget />
      </div>

      <!-- Regional Shipping Widget -->
      <div class="widget-container widget-wide">
        <RegionalShippingWidget />
      </div>

      <!-- Quick Actions -->
      <div class="content-card">
        <div class="card-header">
          <h3 class="card-title">Quick Actions</h3>
        </div>
        
        <div class="quick-actions">
          <router-link to="/products" class="action-btn">
            <div class="action-icon action-icon-primary">
              <v-icon size="24">mdi-package-variant-plus</v-icon>
            </div>
            <div class="action-label">
              <span>Add Product</span>
              <small>Create new product</small>
            </div>
          </router-link>
          
          <router-link to="/admin/orders" class="action-btn">
            <div class="action-icon action-icon-success">
              <v-icon size="24">mdi-clipboard-text</v-icon>
            </div>
            <div class="action-label">
              <span>View Orders</span>
              <small>Manage all orders</small>
            </div>
          </router-link>
          
          <router-link to="/users" class="action-btn">
            <div class="action-icon action-icon-info">
              <v-icon size="24">mdi-account-plus</v-icon>
            </div>
            <div class="action-label">
              <span>Add User</span>
              <small>Create new user</small>
            </div>
          </router-link>
          
          <router-link to="/reports" class="action-btn">
            <div class="action-icon action-icon-warning">
              <v-icon size="24">mdi-chart-bar</v-icon>
            </div>
            <div class="action-label">
              <span>Reports</span>
              <small>View analytics</small>
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useOrderStore } from '@/stores/orders';
import ExpiringProductsWidget from '@/components/admin/ExpiringProductsWidget.vue';
import RegionalShippingWidget from '@/components/admin/RegionalShippingWidget.vue';
import { useProductStore } from '@/stores/products';
import { useDeliveryStore } from '@/stores/deliveries';

const router = useRouter();

const authStore = useAuthStore();
const orderStore = useOrderStore();
const productStore = useProductStore();
const deliveryStore = useDeliveryStore();

// Low stock products from database (must be declared before stats)
const lowStockProducts = computed(() => {
  // Filter products with stock <= 15 (low stock threshold)
  const LOW_STOCK_THRESHOLD = 15;
  
  return (productStore.products || [])
    .filter(product => {
      const stock = parseInt(product.stock_quantity) || 0;
      const minLevel = parseInt(product.min_stock_level) || LOW_STOCK_THRESHOLD;
      return stock > 0 && stock <= minLevel;
    })
    .slice(0, 5)
    .map(product => ({
      id: product.id,
      name: product.name,
      stock_quantity: product.stock_quantity || 0,
      min_stock_level: product.min_stock_level || LOW_STOCK_THRESHOLD,
      image: product.primary_image?.url || product.image_url || null
    }));
});

// Real stats computed from database
const stats = computed(() => ({
  totalOrders: orderStore.orders?.length || 0,
  totalRevenue: orderStore.orders?.reduce((sum, order) => sum + parseFloat(order.total_amount || 0), 0) || 0,
  totalProducts: productStore.products?.length || 0,
  lowStockItems: lowStockProducts.value?.length || 0,
}));

// Recent orders from database (last 5)
const recentOrders = computed(() => {
  return (orderStore.orders || [])
    .slice(0, 5)
    .map(order => ({
      id: order.id,
      order_number: order.order_number,
      customer_name: order.user?.name || order.customer?.name || 'N/A',
      customer_initial: (order.user?.name || order.customer?.name || 'N')[0].toUpperCase(),
      order_date: order.created_at || order.order_date,
      total_amount: parseFloat(order.total_amount || 0),
      status: order.status
    }));
});

// Today's deliveries from database
const todayDeliveries = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return (deliveryStore.deliveries || [])
    .filter(delivery => {
      const scheduledDate = delivery.scheduled_date?.split(' ')[0] || delivery.scheduled_date?.split('T')[0];
      return scheduledDate === today;
    })
    .slice(0, 5)
    .map(delivery => ({
      id: delivery.id,
      order_number: delivery.order?.order_number || `ORD-${delivery.order_id}`,
      scheduled_time: delivery.scheduled_date ? new Date(delivery.scheduled_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : 'TBD',
      status: delivery.status
    }));
});

const formatNumber = (num) => {
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    processing: 'info',
    completed: 'success',
    delivered: 'success',
    cancelled: 'error',
    out_for_delivery: 'primary',
    in_transit: 'primary',
  };
  return colors[status] || 'neutral';
};

const getStockClass = (current, min) => {
  const percentage = (current / min) * 100;
  if (percentage <= 25) return 'progress-critical';
  if (percentage <= 50) return 'progress-warning';
  return 'progress-good';
};

// Navigate to product edit page
const navigateToProduct = (productId) => {
  router.push({ name: 'Products' });
};

// Load all dashboard data from database
onMounted(async () => {
  try {
    await Promise.all([
      orderStore.fetchOrders(),
      productStore.fetchProducts(),
      deliveryStore.fetchDeliveries(),
    ]);
  } catch (error) {
    console.error('Error loading dashboard data:', error);
  }
});
</script>

<style scoped>
.admin-dashboard {
  padding: 1.5rem;
  background: transparent;
  min-height: 100vh;
}

@media (max-width: 768px) {
  .admin-dashboard {
    padding: 1rem;
  }
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
}

.dashboard-subtitle {
  font-size: 1rem;
  color: #64748b;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.btn-secondary-outline {
  padding: 0.625rem 1.25rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 200ms;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.btn-secondary-outline:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
}

.btn-primary-elevated {
  padding: 0.625rem 1.25rem;
  background: linear-gradient(135deg, #1976d2, #2196f3);
  border: none;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 600;
  color: white;
  cursor: pointer;
  transition: all 200ms;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 4px 12px 0 rgba(25, 118, 210, 0.3);
}

.btn-primary-elevated:hover {
  transform: translateY(-2px);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 8px 16px 0 rgba(25, 118, 210, 0.4);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  position: relative;
  overflow: hidden;
  transition: all 200ms;
  box-shadow: 
    0 -1px 3px 0 rgba(0, 0, 0, 0.02),
    0 2px 6px 0 rgba(0, 0, 0, 0.08);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
}

.stat-card-primary::before {
  background: linear-gradient(90deg, #1976d2, #2196f3);
}

.stat-card-success::before {
  background: linear-gradient(90deg, #059669, #34d399);
}

.stat-card-info::before {
  background: linear-gradient(90deg, #0891b2, #22d3ee);
}

.stat-card-warning::before {
  background: linear-gradient(90deg, #d97706, #fbbf24);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 
    0 -2px 6px 0 rgba(0, 0, 0, 0.03),
    0 8px 20px 0 rgba(0, 0, 0, 0.12);
}

.stat-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 4px 8px 0 rgba(0, 0, 0, 0.15);
}

.stat-icon-primary {
  background: linear-gradient(135deg, #1976d2, #2196f3);
}

.stat-icon-success {
  background: linear-gradient(135deg, #059669, #34d399);
}

.stat-icon-info {
  background: linear-gradient(135deg, #0891b2, #22d3ee);
}

.stat-icon-warning {
  background: linear-gradient(135deg, #d97706, #fbbf24);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  margin: 0 0 0.5rem 0;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
}

.stat-change {
  font-size: 0.8125rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-change-positive {
  color: #059669;
}

.stat-change-negative {
  color: #dc2626;
}

.stat-change-neutral {
  color: #64748b;
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 1.5rem;
}

.content-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  grid-column: span 4;
  box-shadow: 
    0 -1px 3px 0 rgba(0, 0, 0, 0.02),
    0 2px 6px 0 rgba(0, 0, 0, 0.08);
}

.content-card-wide {
  grid-column: span 8;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
}

.card-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.btn-ghost {
  background: none;
  border: none;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #2196f3;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  border-radius: 8px;
  transition: all 200ms;
}

.btn-ghost:hover {
  background: #eff6ff;
}

/* Table Styles */
.table-wrapper {
  background: #f8fafc;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(180deg, #f1f5f9, #e2e8f0);
}

.data-table th {
  padding: 0.875rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.data-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: #0f172a;
  border-top: 1px solid #e2e8f0;
}

.table-row-hover {
  transition: background 150ms;
}

.table-row-hover:hover {
  background: white;
}

.order-id {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #2196f3;
}

.customer-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.customer-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2196f3, #42a5f5);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
}

.text-muted {
  color: #64748b;
}

.text-semibold {
  font-weight: 600;
}

.badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.badge-pending {
  background: linear-gradient(135deg, #fbbf24, #fcd34d);
  color: #78350f;
}

.badge-processing {
  background: linear-gradient(135deg, #22d3ee, #67e8f9);
  color: #164e63;
}

.badge-completed {
  background: linear-gradient(135deg, #34d399, #6ee7b7);
  color: #064e3b;
}

.badge-warning {
  background: linear-gradient(135deg, #fbbf24, #fcd34d);
  color: #78350f;
}

.badge-info {
  background: linear-gradient(135deg, #60a5fa, #93c5fd);
  color: #1e3a8a;
}

.badge-success {
  background: linear-gradient(135deg, #34d399, #6ee7b7);
  color: #064e3b;
}

.badge-error {
  background: linear-gradient(135deg, #f87171, #fca5a5);
  color: #7f1d1d;
}

/* Delivery List */
.delivery-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.delivery-item {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 200ms;
}

.delivery-item:hover {
  background: white;
  border-color: #cbd5e1;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.delivery-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #eff6ff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.delivery-info {
  flex: 1;
}

.delivery-order {
  font-weight: 600;
  font-size: 0.875rem;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
}

.delivery-time {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}

.delivery-status {
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-out_for_delivery {
  background: #cffafe;
  color: #155e75;
}

/* Stock Alerts */
.stock-alerts {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stock-alert-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.product-thumb {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  overflow: hidden;
  background: #f1f5f9;
  flex-shrink: 0;
  box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.product-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-details {
  flex: 1;
}

.product-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
}

.stock-progress {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.progress-bar-container {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
  box-shadow: inset 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.progress-bar {
  height: 100%;
  border-radius: 3px;
  transition: width 300ms;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
}

.progress-good {
  background: linear-gradient(90deg, #059669, #34d399);
}

.progress-warning {
  background: linear-gradient(90deg, #d97706, #fbbf24);
}

.progress-critical {
  background: linear-gradient(90deg, #dc2626, #f87171);
}

.stock-text {
  font-size: 0.75rem;
  color: #64748b;
}

.btn-restock {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #eff6ff;
  border: none;
  color: #2196f3;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 200ms;
}

.btn-restock:hover {
  background: #dbeafe;
  transform: scale(1.1);
}

/* Quick Actions */
.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  text-decoration: none;
  transition: all 200ms;
}

.action-btn:hover {
  background: white;
  border-color: #cbd5e1;
  transform: translateX(4px);
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.action-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
}

.action-icon-primary {
  background: linear-gradient(135deg, #1976d2, #2196f3);
  color: white;
}

.action-icon-success {
  background: linear-gradient(135deg, #059669, #34d399);
  color: white;
}

.action-icon-info {
  background: linear-gradient(135deg, #0891b2, #22d3ee);
  color: white;
}

.action-icon-warning {
  background: linear-gradient(135deg, #d97706, #fbbf24);
  color: white;
}

.action-label {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.action-label span {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #0f172a;
}

.action-label small {
  font-size: 0.8125rem;
  color: #64748b;
}

/* Empty States */
.empty-state {
  padding: 3rem 1rem;
  text-align: center;
  color: #94a3b8;
}

.empty-state p {
  margin: 0.75rem 0 0 0;
  font-size: 0.9375rem;
}

.empty-state-small {
  padding: 2rem 1rem;
  text-align: center;
  color: #94a3b8;
}

.empty-state-small p {
  margin: 0.5rem 0 0 0;
  font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .content-card,
  .content-card-wide {
    grid-column: span 12;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .admin-dashboard {
    padding: 1rem;
  }
  
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-actions {
    width: 100%;
  }
  
  .btn-secondary-outline,
  .btn-primary-elevated {
    flex: 1;
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .stat-card:hover {
    transform: translateY(-2px);
  }
  
  .table-wrapper {
    overflow-x: auto;
  }
  
  .widget-container {
    grid-column: span 12;
  }
  
  .widget-half {
    grid-column: span 6;
  }
  
  .widget-wide {
    grid-column: span 6;
  }
}

/* Widget Container Styles */
.widget-container {
  grid-column: span 4;
}

.widget-half {
  grid-column: span 4;
}

.widget-wide {
  grid-column: span 8;
}

/* Ensure widgets fill their containers */
.widget-container > * {
  height: 100%;
}
</style>

