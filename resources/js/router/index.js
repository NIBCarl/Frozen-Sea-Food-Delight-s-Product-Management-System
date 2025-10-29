import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/Home.vue'),
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/auth/Register.vue'),
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    redirect: (to) => {
      // This will be handled by the navigation guard to redirect to role-specific dashboard
      return '/admin/dashboard';
    },
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('../views/admin/Dashboard.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/products',
    name: 'Products',
    component: () => import('../views/Products.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/categories',
    name: 'Categories',
    component: () => import('../views/Categories.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../views/Users.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/stock',
    name: 'Stock',
    component: () => import('../views/Stock.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('../views/Reports.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('../views/Settings.vue'),
    meta: { requiresAuth: true },
  },
  
  // Customer Routes
  {
    path: '/customer',
    meta: { requiresAuth: true, requiresRole: 'customer' },
    children: [
      {
        path: 'products',
        name: 'customer.products',
        component: () => import('../views/customer/ProductCatalog.vue'),
      },
      {
        path: 'cart',
        name: 'customer.cart',
        component: () => import('../views/customer/Cart.vue'),
      },
      {
        path: 'checkout',
        name: 'customer.checkout',
        component: () => import('../views/customer/Checkout.vue'),
      },
      {
        path: 'orders',
        name: 'customer.orders',
        component: () => import('../views/customer/Orders.vue'),
      },
    ],
  },

  // Supplier Routes
  {
    path: '/supplier',
    meta: { requiresAuth: true, requiresRole: 'supplier' },
    children: [
      {
        path: 'shipments',
        name: 'supplier.shipments',
        component: () => import('../views/supplier/Shipments.vue'),
      },
    ],
  },

  // Delivery Personnel Routes
  {
    path: '/delivery',
    meta: { requiresAuth: true, requiresRole: 'delivery_personnel' },
    children: [
      {
        path: 'today',
        name: 'delivery.today',
        component: () => import('../views/delivery/TodayDeliveries.vue'),
      },
    ],
  },

  // Admin Routes
  {
    path: '/admin',
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: 'orders',
        name: 'admin.orders',
        component: () => import('../views/admin/Orders.vue'),
      },
      {
        path: 'deliveries',
        name: 'admin.deliveries',
        component: () => import('../views/admin/Deliveries.vue'),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  // Initialize auth if not already done
  if (!authStore.isAuthenticated && localStorage.getItem('token')) {
    await authStore.initializeAuth();
  }

  // Check authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login');
    return;
  }

  // Check admin access
  if (to.meta.requiresAdmin && !authStore.hasRole('admin')) {
    next('/dashboard');
    return;
  }

  // Check role-specific access
  if (to.meta.requiresRole) {
    const requiredRole = to.meta.requiresRole;
    if (!authStore.hasRole(requiredRole)) {
      // Redirect to appropriate dashboard based on user's role
      if (authStore.hasRole('admin')) {
        next('/admin/dashboard');
      } else if (authStore.hasRole('customer')) {
        next('/customer/products');
      } else if (authStore.hasRole('supplier')) {
        next('/supplier/shipments');
      } else if (authStore.hasRole('delivery_personnel')) {
        next('/delivery/today');
      } else {
        next('/dashboard');
      }
      return;
    }
  }

  // Redirect authenticated users away from guest pages to appropriate dashboard
  if (to.meta.guest && authStore.isAuthenticated) {
    // Redirect users to role-specific landing page
    if (authStore.hasRole('admin')) {
      next('/admin/dashboard');
    } else if (authStore.hasRole('customer')) {
      next('/customer/products');
    } else if (authStore.hasRole('supplier')) {
      next('/supplier/shipments');
    } else if (authStore.hasRole('delivery_personnel')) {
      next('/delivery/today');
    } else {
      next('/dashboard');
    }
    return;
  }

  // Special handling for root path and dashboard routing
  if ((to.path === '/' || to.path === '/dashboard') && authStore.isAuthenticated) {
    // Redirect to appropriate landing page based on role
    if (authStore.hasRole('admin')) {
      next('/admin/dashboard');
    } else if (authStore.hasRole('customer')) {
      next('/customer/products');
    } else if (authStore.hasRole('supplier')) {
      next('/supplier/shipments');
    } else if (authStore.hasRole('delivery_personnel')) {
      next('/delivery/today');
    } else {
      next('/admin/dashboard'); // Default to admin dashboard
    }
    return;
  }

  next();
});

export default router;
