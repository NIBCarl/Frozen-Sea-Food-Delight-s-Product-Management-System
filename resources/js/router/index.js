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
    path: '/verify-otp',
    name: 'VerifyOtp',
    component: () => import('../views/auth/VerifyOtp.vue'),
    meta: { guest: true },
  },
  {
    path: '/oauth/callback',
    name: 'OAuthCallback',
    component: () => import('../views/auth/GoogleCallback.vue'),
    meta: { guest: true },
  },
  {
    path: '/onboarding',
    name: 'Onboarding',
    component: () => import('../views/auth/Onboarding.vue'),
    meta: { requiresAuth: true, allowIncompleteProfile: true },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    // Do not hard-redirect here to avoid loops; guard below will route by role
    component: () => import('../views/Dashboard.vue'),
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
        path: 'products/:id',
        name: 'customer.product-detail',
        component: () => import('../views/customer/ProductDetail.vue'),
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
      {
        path: 'orders/:id',
        name: 'customer.order-detail',
        component: () => import('../views/customer/OrderDetail.vue'),
        props: true,
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
      {
        path: 'history',
        name: 'delivery.history',
        component: () => import('../views/delivery/DeliveryHistory.vue'),
      },
    ],
  },

  // Supplier Routes
  {
    path: '/supplier',
    meta: { requiresAuth: true, requiresRole: 'supplier' },
    children: [
      {
        path: 'dashboard',
        name: 'supplier.dashboard',
        component: () => import('../views/supplier/Dashboard.vue'),
      },
      {
        path: 'orders',
        name: 'supplier.orders',
        component: () => import('../views/supplier/Orders.vue'),
      },
      {
        path: 'orders/:id',
        name: 'supplier.order-detail',
        component: () => import('../views/supplier/OrderDetail.vue'),
        props: true,
      },
      {
        path: 'products',
        name: 'supplier.products',
        component: () => import('../views/supplier/Products.vue'),
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

  // Check if user needs to complete onboarding (for Google OAuth users)
  if (authStore.isAuthenticated && authStore.user && !to.meta.allowIncompleteProfile) {
    const isGoogleUser = authStore.user.is_google_user;
    const needsOnboarding = !authStore.user.profile_completed || 
                           (isGoogleUser && (!authStore.user.username || !authStore.user.contact_number));
                           
    if (needsOnboarding && to.path !== '/onboarding') {
      next('/onboarding');
      return;
    }
  }

  // Check admin access
  if (to.meta.requiresAdmin && !authStore.hasRole('admin')) {
    // Send non-admins to customer home instead of /dashboard (which may not exist)
    next('/customer/products');
    return;
  }

  // Check role-specific access
  if (to.meta.requiresRole) {
    const requiredRole = to.meta.requiresRole;
    if (!authStore.hasRole(requiredRole)) {
      // Compute a safe fallback based on actual roles
      let fallback = '/settings';
      if (authStore.hasRole('admin')) fallback = '/admin/dashboard';
      else if (authStore.hasRole('customer')) fallback = '/customer/products';
      else if (authStore.hasRole('supplier')) fallback = '/supplier/shipments';
      else if (authStore.hasRole('delivery_personnel')) fallback = '/delivery/today';

      // Avoid redirect loops: only redirect if we are not already on the fallback route
      if (to.fullPath !== fallback) {
        next(fallback);
      } else {
        // Already at fallback, allow navigation to break the loop
        next();
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
      next('/customer/products'); // Default fallback to customer products
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
      next('/customer/products'); // Default to customer products for unrecognized roles
    }
    return;
  }

  next();
});

// Recover from dynamic import chunk load failures (e.g., after deploy)
router.onError((error, to) => {
  const message = error?.message || '';
  const name = error?.name || '';
  if (/Loading chunk/i.test(message) || /ChunkLoadError/i.test(name)) {
    // Force reload to fetch fresh chunks and navigate to the intended route
    console.warn('Chunk load failed, reloading page to recover...', error);
    window.location.assign(to?.fullPath || window.location.pathname);
  }
});

export default router;
