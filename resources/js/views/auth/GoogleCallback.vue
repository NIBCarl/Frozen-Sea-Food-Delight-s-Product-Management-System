<template>
  <div class="callback-page">
    <div class="callback-container">
      <div class="callback-card">
        <div v-if="loading" class="loading-state">
          <v-progress-circular indeterminate size="64" width="4" color="primary"></v-progress-circular>
          <h2>Signing you in...</h2>
          <p>Please wait while we complete your Google sign-in.</p>
        </div>
        <div v-else-if="error" class="error-state">
          <v-icon size="64" color="error">mdi-alert-circle</v-icon>
          <h2>Authentication Failed</h2>
          <p>{{ error }}</p>
          <router-link to="/login" class="btn-retry">Back to Login</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const loading = ref(true);
const error = ref('');

onMounted(async () => {
  const token = route.query.token;
  const needsOnboarding = route.query.needs_onboarding === '1';
  const errorMsg = route.query.error;

  if (errorMsg) {
    error.value = decodeURIComponent(errorMsg);
    loading.value = false;
    return;
  }

  if (!token) {
    error.value = 'No authentication token received.';
    loading.value = false;
    return;
  }

  try {
    // Set token and fetch user
    localStorage.setItem('token', token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    await authStore.fetchUser();

    if (needsOnboarding) {
      router.push('/onboarding');
    } else {
      const roles = authStore.user?.roles || [];
      if (roles.includes('admin')) router.push('/admin/dashboard');
      else if (roles.includes('supplier')) router.push('/supplier/shipments');
      else if (roles.includes('delivery_personnel')) router.push('/delivery/today');
      else router.push('/customer/products');
    }
  } catch (err) {
    console.error('Auth error:', err);
    error.value = 'Failed to complete authentication. Please try again.';
    authStore.clearAuth();
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.callback-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0a3d62 0%, #1565c0 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}
.callback-card {
  background: white;
  border-radius: 24px;
  padding: 3rem;
  text-align: center;
  max-width: 400px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}
.loading-state h2, .error-state h2 { margin: 1.5rem 0 0.5rem; color: #111827; }
.loading-state p, .error-state p { color: #6b7280; }
.btn-retry {
  display: inline-block;
  margin-top: 1.5rem;
  padding: 0.75rem 1.5rem;
  background: #2196f3;
  color: white;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
}
</style>
