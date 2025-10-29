<template>
  <div class="login-page">
    <!-- Background with depth layers -->
    <div class="bg-pattern"></div>
    
    <v-container fluid class="fill-height">
      <v-row align="center" justify="center">
        <v-col cols="12" sm="10" md="6" lg="5" xl="4">
          <!-- Login Card with Enhanced Depth -->
          <div class="login-card">
            <!-- Header with Gradient -->
            <div class="login-header">
              <div class="header-icon-wrapper">
                <v-icon size="48" color="white">mdi-fish</v-icon>
              </div>
              <h1 class="header-title">Seafood Inventory</h1>
              <p class="header-subtitle">Fresh Management System</p>
            </div>

            <!-- Form Section -->
            <div class="login-form-wrapper">
              <v-form @submit.prevent="handleLogin" ref="formRef">
                <!-- Email Input -->
                <div class="form-group">
                  <label class="form-label">Email Address</label>
                  <div class="input-wrapper">
                    <v-icon class="input-icon" size="20">mdi-email-outline</v-icon>
                    <input
                      v-model="form.email"
                      type="email"
                      class="form-input"
                      placeholder="admin@seafood.com"
                      required
                    />
                  </div>
                  <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
                </div>

                <!-- Password Input -->
                <div class="form-group">
                  <label class="form-label">Password</label>
                  <div class="input-wrapper">
                    <v-icon class="input-icon" size="20">mdi-lock-outline</v-icon>
                    <input
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-input"
                      placeholder="Enter your password"
                      required
                    />
                    <button
                      type="button"
                      class="toggle-password"
                      @click="showPassword = !showPassword"
                      tabindex="-1"
                    >
                      <v-icon size="20">
                        {{ showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline' }}
                      </v-icon>
                    </button>
                  </div>
                  <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
                </div>

                <!-- Remember Me -->
                <div class="form-options">
                  <label class="checkbox-wrapper">
                    <input
                      v-model="form.remember"
                      type="checkbox"
                      class="checkbox-input"
                    />
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-label">Remember me</span>
                  </label>
                  <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <!-- Submit Button -->
                <button
                  type="submit"
                  class="btn-login"
                  :disabled="loading"
                >
                  <span v-if="!loading">
                    <v-icon start size="20">mdi-login</v-icon>
                    Sign In
                  </span>
                  <span v-else>
                    <v-progress-circular
                      indeterminate
                      size="20"
                      width="2"
                      color="white"
                      class="mr-2"
                    ></v-progress-circular>
                    Signing in...
                  </span>
                </button>
              </v-form>

              <!-- Register Link -->
              <div class="register-section">
                <p class="register-text">
                  Don't have an account?
                  <router-link to="/register" class="register-link">
                    Create Account
                  </router-link>
                </p>
              </div>

              <!-- Demo Credentials -->
              <div class="demo-credentials">
                <div class="demo-header">
                  <v-icon size="16" class="mr-1">mdi-information-outline</v-icon>
                  Demo Credentials
                </div>
                <div class="demo-accounts">
                  <div class="demo-account" @click="fillCredentials('admin@seafood.com', 'password123')">
                    <v-icon size="16" color="primary">mdi-shield-account</v-icon>
                    <span>Admin</span>
                  </div>
                  <div class="demo-account" @click="fillCredentials('customer@seafood.com', 'password123')">
                    <v-icon size="16" color="success">mdi-account</v-icon>
                    <span>Customer</span>
                  </div>
                  <div class="demo-account" @click="fillCredentials('supplier@seafood.com', 'password123')">
                    <v-icon size="16" color="warning">mdi-truck</v-icon>
                    <span>Supplier</span>
                  </div>
                  <div class="demo-account" @click="fillCredentials('delivery@seafood.com', 'password123')">
                    <v-icon size="16" color="info">mdi-moped</v-icon>
                    <span>Delivery</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="login-footer">
            <p>© 2025 Seafood Inventory Management. All rights reserved.</p>
          </div>
        </v-col>
      </v-row>
    </v-container>

    <!-- Snackbar -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="4000"
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
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const formRef = ref(null);

const form = reactive({
  email: '',
  password: '',
  remember: false,
});

const errors = reactive({
  email: '',
  password: '',
});

const showPassword = ref(false);
const loading = ref(false);
const snackbar = reactive({
  show: false,
  message: '',
  color: 'success',
});

const showSnackbar = (message, color = 'success') => {
  snackbar.message = message;
  snackbar.color = color;
  snackbar.show = true;
};

const fillCredentials = (email, password) => {
  form.email = email;
  form.password = password;
  showSnackbar('Demo credentials filled! Click Sign In.', 'info');
};

const validateForm = () => {
  errors.email = '';
  errors.password = '';
  
  if (!form.email) {
    errors.email = 'Email is required';
    return false;
  }
  
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email';
    return false;
  }
  
  if (!form.password) {
    errors.password = 'Password is required';
    return false;
  }
  
  if (form.password.length < 6) {
    errors.password = 'Password must be at least 6 characters';
    return false;
  }
  
  return true;
};

const handleLogin = async () => {
  if (!validateForm()) return;
  
  loading.value = true;
  try {
    await authStore.login(form);
    showSnackbar('Welcome back! Redirecting...', 'success');
    
    setTimeout(() => {
      router.push('/dashboard');
    }, 1000);
  } catch (error) {
    showSnackbar(error.response?.data?.message || 'Invalid credentials', 'error');
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0a3d62 0%, #0c4c7a 50%, #1565c0 100%);
  position: relative;
  overflow: hidden;
}

.bg-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    radial-gradient(circle at 20% 50%, rgba(33, 150, 243, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
  animation: float 20s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

.login-card {
  background: rgba(255, 255, 255, 0.98);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 
    0 -4px 12px 0 rgba(255, 255, 255, 0.1),
    0 20px 40px 0 rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(255, 255, 255, 0.1);
  animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-header {
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 50%, #42a5f5 100%);
  padding: 3rem 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.login-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 50%;
  background: linear-gradient(180deg, rgba(255,255,255,0.2) 0%, transparent 100%);
}

.header-icon-wrapper {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.3),
    0 8px 16px 0 rgba(0, 0, 0, 0.2);
  position: relative;
  z-index: 1;
}

.header-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin: 0 0 0.5rem 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  position: relative;
  z-index: 1;
}

.header-subtitle {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
  font-weight: 500;
  position: relative;
  z-index: 1;
}

.login-form-wrapper {
  padding: 2.5rem 2rem 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  color: #6b7280;
  pointer-events: none;
  z-index: 1;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 0.9375rem;
  color: #111827;
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    inset 0 2px 4px 0 rgba(0, 0, 0, 0.05),
    0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.form-input:focus {
  outline: none;
  background: white;
  border-color: #2196f3;
  box-shadow: 
    inset 0 2px 4px 0 rgba(0, 0, 0, 0.05),
    0 0 0 4px rgba(33, 150, 243, 0.1),
    0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.form-input::placeholder {
  color: #9ca3af;
}

.toggle-password {
  position: absolute;
  right: 1rem;
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 200ms;
}

.toggle-password:hover {
  background: rgba(0, 0, 0, 0.05);
  color: #374151;
}

.error-text {
  display: block;
  font-size: 0.8125rem;
  color: #dc2626;
  margin-top: 0.375rem;
  font-weight: 500;
}

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  cursor: pointer;
  user-select: none;
}

.checkbox-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.checkbox-custom {
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 6px;
  background: #f9fafb;
  margin-right: 0.5rem;
  position: relative;
  transition: all 200ms;
  box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.checkbox-input:checked + .checkbox-custom {
  background: linear-gradient(135deg, #2196f3, #42a5f5);
  border-color: #2196f3;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '';
  position: absolute;
  left: 6px;
  top: 2px;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-label {
  font-size: 0.875rem;
  color: #374151;
  font-weight: 500;
}

.forgot-link {
  font-size: 0.875rem;
  color: #2196f3;
  text-decoration: none;
  font-weight: 600;
  transition: color 200ms;
}

.forgot-link:hover {
  color: #1976d2;
}

.btn-login {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 4px 12px 0 rgba(25, 118, 210, 0.4);
  position: relative;
  overflow: hidden;
}

.btn-login::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 8px 20px 0 rgba(25, 118, 210, 0.5);
}

.btn-login:hover:not(:disabled)::before {
  left: 100%;
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 2px 6px 0 rgba(25, 118, 210, 0.4);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.register-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
  text-align: center;
}

.register-text {
  font-size: 0.9375rem;
  color: #6b7280;
  margin: 0;
}

.register-link {
  color: #2196f3;
  text-decoration: none;
  font-weight: 600;
  transition: color 200ms;
}

.register-link:hover {
  color: #1976d2;
  text-decoration: underline;
}

.demo-credentials {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
}

.demo-header {
  font-size: 0.75rem;
  font-weight: 600;
  color: #0369a1;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
}

.demo-accounts {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.demo-account {
  padding: 0.5rem 0.75rem;
  background: white;
  border: 1px solid #dbeafe;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 200ms;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.demo-account:hover {
  background: #eff6ff;
  border-color: #93c5fd;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
}

.demo-account:active {
  transform: translateY(0);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.login-footer {
  text-align: center;
  padding: 1.5rem 0;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
}

.login-footer p {
  margin: 0;
}

@media (max-width: 600px) {
  .login-header {
    padding: 2rem 1.5rem;
  }
  
  .header-title {
    font-size: 1.5rem;
  }
  
  .header-icon-wrapper {
    width: 64px;
    height: 64px;
  }
  
  .login-form-wrapper {
    padding: 2rem 1.5rem 1.5rem;
  }
  
  .demo-accounts {
    grid-template-columns: 1fr;
  }
}
</style>

