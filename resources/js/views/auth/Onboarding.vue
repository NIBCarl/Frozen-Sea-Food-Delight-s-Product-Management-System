<template>
  <div class="onboarding-page">
    <!-- Background with depth layers -->
    <div class="bg-pattern"></div>
    
    <v-container fluid class="fill-height">
      <v-row align="center" justify="center">
        <v-col cols="12" sm="10" md="8" lg="6" xl="5">
          <!-- Onboarding Card -->
          <div class="onboarding-card">
            <!-- Header with Gradient -->
            <div class="onboarding-header">
              <div class="header-icon-wrapper">
                <v-icon size="48" color="white">mdi-account-check</v-icon>
              </div>
              <h1 class="header-title">Complete Your Profile</h1>
              <p class="header-subtitle">
                Welcome{{ user?.name ? ', ' + user.name.split(' ')[0] : '' }}! Just a few more details to get started.
              </p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
              <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                <div class="step-number">{{ currentStep > 1 ? '✓' : '1' }}</div>
                <span class="step-label">Profile Info</span>
              </div>
              <div class="step-line" :class="{ completed: currentStep > 1 }"></div>
              <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                <div class="step-number">{{ currentStep > 2 ? '✓' : '2' }}</div>
                <span class="step-label">Verify Phone</span>
              </div>
            </div>

            <!-- Form Section -->
            <div class="onboarding-form-wrapper">
              <v-alert
                v-if="formError"
                type="error"
                variant="flat"
                closable
                prominent
                class="mb-4 error-alert"
                @click:close="formError = ''"
              >
                <template v-slot:prepend>
                  <v-icon size="24">mdi-alert-circle</v-icon>
                </template>
                <div class="alert-content">
                  <strong>Error</strong>
                  <div>{{ formError }}</div>
                </div>
              </v-alert>

              <!-- Step 1: Profile Info -->
              <div v-if="currentStep === 1">
                <v-form @submit.prevent="handleSubmitProfile" ref="profileFormRef">
                  <!-- User Info Display -->
                  <div class="user-preview">
                    <img 
                      :src="user?.avatar_url || user?.avatar_display_url || '/images/default-avatar.png'" 
                      :alt="user?.name"
                      class="user-avatar"
                    />
                    <div class="user-info">
                      <div class="user-name">{{ user?.name }}</div>
                      <div class="user-email">{{ user?.email }}</div>
                      <div v-if="user?.is_google_user" class="google-badge">
                        <v-icon size="14" color="#4285F4">mdi-google</v-icon>
                        <span>Signed in with Google</span>
                      </div>
                    </div>
                  </div>

                  <!-- Display Name Input -->
                  <div class="form-group">
                    <label class="form-label">Display Name</label>
                    <div class="input-wrapper">
                      <v-icon class="input-icon" size="20">mdi-account-outline</v-icon>
                      <input
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :class="{ 'input-error': errors.name }"
                        placeholder="Your display name"
                        required
                      />
                    </div>
                    <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
                    <span v-else class="helper-text">This name will be shown in the app</span>
                  </div>

                  <!-- Username Input -->
                  <div class="form-group">
                    <label class="form-label">Choose a Username</label>
                    <div class="input-wrapper">
                      <v-icon class="input-icon" size="20">mdi-at</v-icon>
                      <input
                        v-model="form.username"
                        type="text"
                        class="form-input"
                        :class="{ 'input-error': errors.username }"
                        placeholder="johndoe123"
                        required
                      />
                    </div>
                    <span v-if="errors.username" class="error-text">{{ errors.username }}</span>
                    <span v-else class="helper-text">This will be your unique identifier</span>
                  </div>

                  <!-- Contact Number Input -->
                  <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <div class="input-wrapper">
                      <v-icon class="input-icon" size="20">mdi-phone-outline</v-icon>
                      <input
                        v-model="form.contact_number"
                        type="tel"
                        class="form-input"
                        :class="{ 'input-error': errors.contact_number }"
                        placeholder="+639123456789"
                        required
                        @input="handlePhoneInput"
                      />
                    </div>
                    <span v-if="errors.contact_number" class="error-text">{{ errors.contact_number }}</span>
                    <span v-else class="helper-text">For delivery updates and OTP verification</span>
                  </div>

                  <!-- Terms Checkbox -->
                  <div class="terms-wrapper">
                    <label class="checkbox-wrapper">
                      <input
                        v-model="form.terms_accepted"
                        type="checkbox"
                        class="checkbox-input"
                      />
                      <span class="checkbox-custom"></span>
                      <span class="checkbox-label">
                        I agree to the <a href="#" class="terms-link">Terms & Conditions</a>
                      </span>
                    </label>
                  </div>

                  <!-- Submit Button -->
                  <button
                    type="submit"
                    class="btn-primary"
                    :disabled="loading"
                  >
                    <span v-if="!loading">
                      Continue
                      <v-icon end size="20">mdi-arrow-right</v-icon>
                    </span>
                    <span v-else>
                      <v-progress-circular
                        indeterminate
                        size="20"
                        width="2"
                        color="white"
                        class="mr-2"
                      ></v-progress-circular>
                      Saving...
                    </span>
                  </button>
                </v-form>
              </div>

              <!-- Step 2: OTP Verification -->
              <div v-if="currentStep === 2">
                <div class="otp-section">
                  <div class="otp-icon">
                    <v-icon size="64" color="#2196f3">mdi-message-text-lock</v-icon>
                  </div>
                  <h3 class="otp-title">Verify Your Phone</h3>
                  <p class="otp-subtitle">
                    We've sent a 6-digit code to<br/>
                    <strong>{{ form.contact_number }}</strong>
                  </p>

                  <!-- OTP Input -->
                  <div class="otp-input-group">
                    <input
                      v-for="(_, index) in 6"
                      :key="index"
                      :ref="el => otpInputRefs[index] = el"
                      v-model="otpDigits[index]"
                      type="text"
                      maxlength="1"
                      class="otp-digit"
                      :class="{ 'input-error': otpError }"
                      @input="handleOtpInput(index, $event)"
                      @keydown="handleOtpKeydown(index, $event)"
                      @paste="handleOtpPaste"
                    />
                  </div>

                  <span v-if="otpError" class="error-text text-center">{{ otpError }}</span>

                  <!-- Verify Button -->
                  <button
                    type="button"
                    class="btn-primary"
                    :disabled="loading || otpDigits.join('').length !== 6"
                    @click="handleVerifyOtp"
                  >
                    <span v-if="!loading">
                      <v-icon start size="20">mdi-check-circle</v-icon>
                      Verify & Complete
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

                  <!-- Resend OTP -->
                  <div class="resend-section">
                    <p v-if="resendCountdown > 0" class="resend-timer">
                      Resend code in {{ resendCountdown }}s
                    </p>
                    <button
                      v-else
                      type="button"
                      class="btn-resend"
                      :disabled="resendLoading"
                      @click="handleResendOtp"
                    >
                      <v-icon start size="16">mdi-refresh</v-icon>
                      {{ resendLoading ? 'Sending...' : 'Resend Code' }}
                    </button>
                  </div>

                  <!-- Back Button -->
                  <button type="button" class="btn-back" @click="currentStep = 1">
                    <v-icon start size="16">mdi-arrow-left</v-icon>
                    Change Phone Number
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="onboarding-footer">
            <p>© 2025 Seafood Delight. All rights reserved.</p>
          </div>
        </v-col>
      </v-row>
    </v-container>

    <!-- Success Snackbar -->
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
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';

const authStore = useAuthStore();
const router = useRouter();

const user = computed(() => authStore.user);
const currentStep = ref(1);
const loading = ref(false);
const resendLoading = ref(false);
const resendCountdown = ref(0);
let countdownInterval = null;

const form = reactive({
  name: '',
  username: '',
  contact_number: '+63',
  terms_accepted: false,
});

const errors = reactive({
  name: '',
  username: '',
  contact_number: '',
});

const formError = ref('');
const otpDigits = ref(['', '', '', '', '', '']);
const otpInputRefs = ref([]);
const otpError = ref('');

const handlePhoneInput = (event) => {
  let value = event.target.value;
  
  // Create a regex that only allows + and numbers
  value = value.replace(/[^\d+]/g, '');
  
  // Ensure it starts with +63
  if (!value.startsWith('+63')) {
    if (value.startsWith('63')) {
      value = '+' + value;
    } else if (value.startsWith('+')) {
      // If they typed + but not 63... hard to handle all edge cases perfectly without cursor jumping
      // Simple approach: if they delete everything, put +63 back
       if (value === '+') value = '+63';
    } else {
       // if they typed a number like 9...
       value = '+63' + value.replace(/^\+/, '');
    }
  }
  
  // Limit length if needed, e.g., +63 + 10 digits = 13 chars
  if (value.length > 13) {
      value = value.slice(0, 13);
  }

  form.contact_number = value;
};

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

const validateProfileForm = () => {
  errors.name = '';
  errors.username = '';
  errors.contact_number = '';
  let isValid = true;

  if (!form.name || form.name.trim().length < 2) {
    errors.name = 'Display name must be at least 2 characters';
    isValid = false;
  }

  if (!form.username) {
    errors.username = 'Username is required';
    isValid = false;
  } else if (form.username.length < 3) {
    errors.username = 'Username must be at least 3 characters';
    isValid = false;
  } else if (!/^[a-zA-Z0-9_]+$/.test(form.username)) {
    errors.username = 'Username can only contain letters, numbers, and underscores';
    isValid = false;
  }

  if (!form.contact_number) {
    errors.contact_number = 'Phone number is required';
    isValid = false;
  } else if (!/^\+639\d{9}$/.test(form.contact_number)) {
    errors.contact_number = 'Please enter a valid PH mobile number (e.g., +639123456789)';
    isValid = false;
  }

  if (!form.terms_accepted) {
    showSnackbar('Please accept the terms and conditions', 'warning');
    isValid = false;
  }

  return isValid;
};

const handleSubmitProfile = async () => {
  if (!validateProfileForm()) return;

  loading.value = true;
  formError.value = '';

  try {
    await axios.post('/api/v1/onboarding/complete-profile', form);
    showSnackbar('Profile saved! Please verify your phone number.', 'success');
    currentStep.value = 2;
    startResendCountdown();
  } catch (error) {
    console.error('Profile update error:', error);
    if (error.response?.data?.errors) {
      const serverErrors = error.response.data.errors;
      if (serverErrors.name) errors.name = serverErrors.name[0];
      if (serverErrors.username) errors.username = serverErrors.username[0];
      if (serverErrors.contact_number) errors.contact_number = serverErrors.contact_number[0];
    }
    formError.value = error.response?.data?.message || 'Failed to update profile. Please try again.';
  } finally {
    loading.value = false;
  }
};

const handleOtpInput = (index, event) => {
  const value = event.target.value;
  
  // Only allow digits
  if (!/^\d*$/.test(value)) {
    otpDigits.value[index] = '';
    return;
  }

  // Move to next input
  if (value && index < 5) {
    otpInputRefs.value[index + 1]?.focus();
  }

  otpError.value = '';
};

const handleOtpKeydown = (index, event) => {
  // Handle backspace
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    otpInputRefs.value[index - 1]?.focus();
  }
};

const handleOtpPaste = (event) => {
  event.preventDefault();
  const pastedData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
  
  for (let i = 0; i < pastedData.length; i++) {
    otpDigits.value[i] = pastedData[i];
  }
  
  // Focus the next empty input or the last one
  const nextEmptyIndex = pastedData.length < 6 ? pastedData.length : 5;
  otpInputRefs.value[nextEmptyIndex]?.focus();
};

const handleVerifyOtp = async () => {
  const otp = otpDigits.value.join('');
  
  if (otp.length !== 6) {
    otpError.value = 'Please enter all 6 digits';
    return;
  }

  loading.value = true;
  otpError.value = '';

  try {
    const response = await axios.post('/api/v1/onboarding/verify-otp', { otp });
    
    // Update user in store
    await authStore.fetchUser();
    
    showSnackbar('Welcome to Seafood Delight! 🎉', 'success');
    
    // Redirect to customer products page
    setTimeout(() => {
      router.push('/customer/products');
    }, 1500);
  } catch (error) {
    console.error('OTP verification error:', error);
    otpError.value = error.response?.data?.message || 'Invalid OTP. Please try again.';
    
    // Clear OTP inputs on error
    otpDigits.value = ['', '', '', '', '', ''];
    otpInputRefs.value[0]?.focus();
  } finally {
    loading.value = false;
  }
};

const handleResendOtp = async () => {
  resendLoading.value = true;

  try {
    await axios.post('/api/v1/onboarding/resend-otp');
    showSnackbar('OTP sent successfully!', 'success');
    startResendCountdown();
  } catch (error) {
    showSnackbar(error.response?.data?.message || 'Failed to resend OTP', 'error');
  } finally {
    resendLoading.value = false;
  }
};

const startResendCountdown = () => {
  resendCountdown.value = 60;
  
  if (countdownInterval) clearInterval(countdownInterval);
  
  countdownInterval = setInterval(() => {
    resendCountdown.value--;
    if (resendCountdown.value <= 0) {
      clearInterval(countdownInterval);
    }
  }, 1000);
};

onMounted(async () => {
  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    router.push('/login');
    return;
  }

  // Pre-fill form with existing data
  if (user.value) {
    if (user.value.name) form.name = user.value.name;
    if (user.value.username) form.username = user.value.username;
    if (user.value.contact_number) {
      // Normalize to +63 format if it starts with 09 or just 9
      let phone = user.value.contact_number;
      if (phone.startsWith('09')) phone = '+63' + phone.substring(1);
      else if (phone.startsWith('9')) phone = '+63' + phone;
      form.contact_number = phone;
    }
  }

  // Check onboarding status
  try {
    const response = await axios.get('/api/v1/onboarding/status');
    if (!response.data.needs_onboarding) {
      // Already completed, redirect to products
      router.push('/customer/products');
    }
  } catch (error) {
    console.error('Failed to check onboarding status:', error);
  }
});

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval);
});
</script>

<style scoped>
.onboarding-page {
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

.onboarding-card {
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

.onboarding-header {
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 50%, #42a5f5 100%);
  padding: 2.5rem 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.onboarding-header::before {
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
  font-size: 1.75rem;
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

.step-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem 2rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.step-number {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #e5e7eb;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.3s;
}

.step.active .step-number {
  background: #2196f3;
  color: white;
}

.step.completed .step-number {
  background: #10b981;
  color: white;
}

.step-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.step.active .step-label {
  color: #2196f3;
}

.step.completed .step-label {
  color: #10b981;
}

.step-line {
  width: 80px;
  height: 3px;
  background: #e5e7eb;
  margin: 0 1rem;
  margin-bottom: 1.5rem;
  border-radius: 2px;
  transition: all 0.3s;
}

.step-line.completed {
  background: #10b981;
}

.onboarding-form-wrapper {
  padding: 2rem;
}

.user-preview {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f0f9ff;
  border-radius: 12px;
  margin-bottom: 1.5rem;
}

.user-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-info {
  flex: 1;
}

.user-name {
  font-weight: 600;
  color: #111827;
  font-size: 1.125rem;
}

.user-email {
  color: #6b7280;
  font-size: 0.875rem;
}

.google-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.5rem;
  padding: 0.25rem 0.5rem;
  background: white;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #6b7280;
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
  transition: all 200ms;
}

.form-input:focus {
  outline: none;
  background: white;
  border-color: #2196f3;
  box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
}

.form-input.input-error {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.error-text {
  display: block;
  font-size: 0.8125rem;
  color: #dc2626;
  margin-top: 0.375rem;
  font-weight: 500;
}

.helper-text {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.375rem;
}

.terms-wrapper {
  margin-bottom: 1.5rem;
}

.checkbox-wrapper {
  display: flex;
  align-items: flex-start;
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
  min-width: 20px;
  border: 2px solid #d1d5db;
  border-radius: 6px;
  background: #f9fafb;
  margin-right: 0.5rem;
  margin-top: 2px;
  position: relative;
  transition: all 200ms;
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
  line-height: 1.5;
}

.terms-link {
  color: #2196f3;
  text-decoration: none;
  font-weight: 600;
}

.terms-link:hover {
  text-decoration: underline;
}

.btn-primary {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 200ms;
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(25, 118, 210, 0.5);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* OTP Section Styles */
.otp-section {
  text-align: center;
}

.otp-icon {
  margin-bottom: 1rem;
}

.otp-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.5rem;
}

.otp-subtitle {
  color: #6b7280;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

.otp-input-group {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.otp-digit {
  width: 48px;
  height: 56px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 600;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: #f9fafb;
  transition: all 200ms;
}

.otp-digit:focus {
  outline: none;
  border-color: #2196f3;
  background: white;
  box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
}

.otp-digit.input-error {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.resend-section {
  margin-top: 1.5rem;
}

.resend-timer {
  color: #6b7280;
  font-size: 0.875rem;
}

.btn-resend {
  background: none;
  border: none;
  color: #2196f3;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.btn-resend:hover {
  text-decoration: underline;
}

.btn-back {
  margin-top: 1rem;
  background: none;
  border: none;
  color: #6b7280;
  font-size: 0.875rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.btn-back:hover {
  color: #374151;
}

.error-alert {
  border-radius: 12px !important;
}

.text-center {
  text-align: center;
}

.onboarding-footer {
  text-align: center;
  padding: 1.5rem 0;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
}

@media (max-width: 600px) {
  .onboarding-header {
    padding: 2rem 1.5rem;
  }

  .header-title {
    font-size: 1.5rem;
  }

  .onboarding-form-wrapper {
    padding: 1.5rem;
  }

  .user-preview {
    flex-direction: column;
    text-align: center;
  }

  .otp-digit {
    width: 40px;
    height: 48px;
    font-size: 1.25rem;
  }
}
</style>
