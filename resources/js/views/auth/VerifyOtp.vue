<template>
  <div class="verify-page">
    <!-- Background with depth layers -->
    <div class="bg-pattern"></div>
    
    <v-container fluid class="fill-height">
      <v-row align="center" justify="center">
        <v-col cols="12" sm="10" md="6" lg="5" xl="4">
          <!-- Verify Card with Enhanced Depth -->
          <div class="verify-card">
            <!-- Header with Gradient -->
            <div class="verify-header">
              <div class="header-icon-wrapper">
                <v-icon size="48" color="white">mdi-cellphone-message</v-icon>
              </div>
              <h1 class="header-title">Verify Account</h1>
              <p class="header-subtitle">Enter the code sent to your phone</p>
            </div>

            <!-- Form Section -->
            <div class="verify-form-wrapper">
              <v-alert
                v-if="pageError"
                type="error"
                variant="tonal"
                closable
                class="mb-4"
                @click:close="pageError = ''"
              >
                {{ pageError }}
              </v-alert>

              <div class="info-box mb-6">
                <div class="info-header">
                  <v-icon size="16" class="mr-1">mdi-email-check-outline</v-icon>
                  Verification Required
                </div>
                <p class="info-text">
                  We've sent a 6-digit code to the number associated with <strong>{{ email }}</strong>.
                </p>
              </div>

              <v-form @submit.prevent="handleVerify" ref="formRef">
                <!-- OTP Input -->
                <div class="form-group">
                  <label class="form-label">Verification Code</label>
                  <div class="input-wrapper">
                    <v-icon class="input-icon" size="20">mdi-lock-outline</v-icon>
                    <input
                      v-model="otp"
                      type="text"
                      class="form-input otp-input"
                      placeholder="000000"
                      maxlength="6"
                      required
                      autofocus
                    />
                  </div>
                  <span v-if="error" class="error-text">{{ error }}</span>
                </div>

                <!-- Submit Button -->
                <button
                  type="submit"
                  class="btn-verify"
                  :disabled="loading || otp.length !== 6"
                >
                  <span v-if="!loading">
                    <v-icon start size="20">mdi-check-circle-outline</v-icon>
                    Verify Account
                  </span>
                  <span v-else>
                    <v-progress-circular
                      indeterminate
                      size="20"
                      width="2"
                      color="white"
                      class="mr-2"
                    ></v-progress-circular>
                    Verifying...
                  </span>
                </button>
              </v-form>

              <!-- Resend Link -->
              <div class="resend-section">
                <p class="resend-text">
                  Didn't receive the code?
                  <button 
                    @click="handleResend" 
                    class="resend-link"
                    :disabled="resendLoading || resendTimer > 0"
                  >
                    {{ resendTimer > 0 ? `Resend in ${resendTimer}s` : 'Resend Code' }}
                  </button>
                </p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="verify-footer">
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
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const otp = ref('');
const loading = ref(false);
const error = ref('');
const pageError = ref('');
const resendLoading = ref(false);
const resendTimer = ref(0);
const email = computed(() => route.query.email || 'your email');

const snackbar = ref({
  show: false,
  message: '',
  color: 'success',
});

const showSnackbar = (message, color = 'success') => {
  snackbar.value = { show: true, message, color };
};

const startResendTimer = () => {
  resendTimer.value = 60;
  const interval = setInterval(() => {
    resendTimer.value--;
    if (resendTimer.value <= 0) {
      clearInterval(interval);
    }
  }, 1000);
};

const handleVerify = async () => {
  if (otp.value.length !== 6) {
    error.value = 'Please enter a valid 6-digit code';
    return;
  }

  loading.value = true;
  error.value = '';
  pageError.value = '';

  try {
    const response = await authStore.verifyOtp({
      email: route.query.email,
      otp: otp.value
    });

    showSnackbar('Verification successful! Logging you in...', 'success');

    // Use router guards to determine appropriate redirect
    const userRoles = response.user.roles;
    
    setTimeout(() => {
      if (userRoles.includes('admin')) {
        router.push('/admin/dashboard');
      } else if (userRoles.includes('customer')) {
        router.push('/customer/products');
      } else if (userRoles.includes('supplier')) {
        router.push('/supplier/shipments');
      } else if (userRoles.includes('delivery_personnel')) {
        router.push('/delivery/today');
      } else {
        router.push('/customer/products'); // Default fallback
      }
    }, 1000);
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Verification failed. Please try again.';
    showSnackbar(pageError.value, 'error');
  } finally {
    loading.value = false;
  }
};

const handleResend = async () => {
  if (resendTimer.value > 0) return;

  resendLoading.value = true;
  pageError.value = '';
  try {
    await authStore.resendOtp(route.query.email);
    showSnackbar('New code sent successfully!', 'success');
    startResendTimer();
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Failed to resend code';
    showSnackbar(pageError.value, 'error');
  } finally {
    resendLoading.value = false;
  }
};

onMounted(() => {
  if (!route.query.email) {
    showSnackbar('Email missing from navigation. Redirecting to login.', 'warning');
    setTimeout(() => router.push('/login'), 2000);
  }
});
</script>

<style scoped>
.verify-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0a3d62 0%, #0c4c7a 50%, #1565c0 100%);
  position: relative;
  overflow: hidden;
}

.bg-pattern {
  position: absolute;
  pointer-events: none;
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

.verify-card {
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

.verify-header {
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 50%, #42a5f5 100%);
  padding: 3rem 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.verify-header::before {
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

.verify-form-wrapper {
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

.otp-input {
  letter-spacing: 0.5em;
  font-size: 1.25rem;
  text-align: center;
  padding-left: 1rem; /* Adjust for centering since icon is there */
  font-weight: 700;
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

.error-text {
  display: block;
  font-size: 0.8125rem;
  color: #dc2626;
  margin-top: 0.375rem;
  font-weight: 500;
}

.btn-verify {
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

.btn-verify::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.btn-verify:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 8px 20px 0 rgba(25, 118, 210, 0.5);
}

.btn-verify:hover:not(:disabled)::before {
  left: 100%;
}

.btn-verify:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 2px 6px 0 rgba(25, 118, 210, 0.4);
}

.btn-verify:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.resend-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
  text-align: center;
}

.resend-text {
  font-size: 0.9375rem;
  color: #6b7280;
  margin: 0;
}

.resend-link {
  background: none;
  border: none;
  color: #2196f3;
  font-weight: 600;
  cursor: pointer;
  padding: 0 0.25rem;
  transition: color 200ms;
}

.resend-link:hover:not(:disabled) {
  color: #1976d2;
  text-decoration: underline;
}

.resend-link:disabled {
  color: #9ca3af;
  cursor: not-allowed;
  text-decoration: none;
}

.info-box {
  padding: 1rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
}

.info-header {
  font-size: 0.75rem;
  font-weight: 600;
  color: #0369a1;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
}

.info-text {
  font-size: 0.8125rem;
  color: #0c4a6e;
  margin: 0;
  line-height: 1.5;
}

.verify-footer {
  text-align: center;
  padding: 1.5rem 0;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
}

.verify-footer p {
  margin: 0;
}

@media (max-width: 600px) {
  .verify-header {
    padding: 2rem 1.5rem;
  }
  
  .header-title {
    font-size: 1.5rem;
  }
  
  .header-icon-wrapper {
    width: 64px;
    height: 64px;
  }
  
  .verify-form-wrapper {
    padding: 2rem 1.5rem 1.5rem;
  }
}
</style>
