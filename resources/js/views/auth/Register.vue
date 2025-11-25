<template>
  <div class="register-page">
    <!-- Background with depth layers -->
    <div class="bg-pattern"></div>
    
    <v-container fluid class="fill-height">
      <v-row align="center" justify="center">
        <v-col cols="12" sm="10" md="8" lg="6" xl="5">
          <!-- Register Card with Enhanced Depth -->
          <div class="register-card">
            <!-- Header with Gradient -->
            <div class="register-header">
              <div class="header-icon-wrapper">
                <v-icon size="48" color="white">mdi-fish</v-icon>
              </div>
              <h1 class="header-title">Create Account</h1>
              <p class="header-subtitle">Join Seafood Delight Family</p>
            </div>

            <!-- Form Section -->
            <div class="register-form-wrapper">
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
                  <strong>Registration Error</strong>
                  <div>{{ formError }}</div>
                </div>
              </v-alert>

              <v-form @submit.prevent="handleRegister" ref="formRef">
                <!-- Name and Username Row -->
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <div class="input-wrapper">
                      <v-icon class="input-icon" size="20">mdi-account-outline</v-icon>
                      <input
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :class="{ 'input-error': errors.name }"
                        placeholder="John Doe"
                        required
                      />
                    </div>
                    <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-wrapper">
                      <v-icon class="input-icon" size="20">mdi-account-circle-outline</v-icon>
                      <input
                        v-model="form.username"
                        type="text"
                        class="form-input"
                        :class="{ 'input-error': errors.username }"
                        placeholder="johndoe"
                        required
                      />
                    </div>
                    <span v-if="errors.username" class="error-text">{{ errors.username }}</span>
                  </div>
                </div>

                <!-- Contact Number -->
                <div class="form-group">
                  <label class="form-label">Phone Number</label>
                  <div class="input-wrapper">
                    <v-icon class="input-icon" size="20">mdi-phone-outline</v-icon>
                    <input
                      v-model="form.contact_number"
                      type="tel"
                      class="form-input"
                      :class="{ 'input-error': errors.contact_number }"
                      placeholder="09123456789"
                      required
                    />
                  </div>
                  <span v-if="errors.contact_number" class="error-text">{{ errors.contact_number }}</span>
                </div>

                <!-- Email Input -->
                <div class="form-group">
                  <label class="form-label">Email Address</label>
                  <div class="input-wrapper">
                    <v-icon class="input-icon" size="20">mdi-email-outline</v-icon>
                    <input
                      v-model="form.email"
                      type="email"
                      class="form-input"
                      :class="{ 'input-error': errors.email }"
                      placeholder="john@example.com"
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
                      :class="{ 'input-error': errors.password }"
                      placeholder="At least 8 characters"
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

                <!-- Confirm Password Input -->
                <div class="form-group">
                  <label class="form-label">Confirm Password</label>
                  <div class="input-wrapper">
                    <v-icon class="input-icon" size="20">mdi-lock-check-outline</v-icon>
                    <input
                      v-model="form.password_confirmation"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-input"
                      :class="{ 'input-error': errors.password_confirmation }"
                      placeholder="Re-enter your password"
                      required
                    />
                  </div>
                  <span v-if="errors.password_confirmation" class="error-text">{{ errors.password_confirmation }}</span>
                </div>

                <!-- Terms Checkbox -->
                <div class="terms-wrapper">
                  <label class="checkbox-wrapper">
                    <input
                      v-model="form.terms_accepted"
                      type="checkbox"
                      class="checkbox-input"
                      required
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
                  class="btn-register"
                  :disabled="loading"
                >
                  <span v-if="!loading">
                    <v-icon start size="20">mdi-account-plus</v-icon>
                    Create Account
                  </span>
                  <span v-else>
                    <v-progress-circular
                      indeterminate
                      size="20"
                      width="2"
                      color="white"
                      class="mr-2"
                    ></v-progress-circular>
                    Creating Account...
                  </span>
                </button>
              </v-form>

              <!-- Login Link -->
              <div class="login-section">
                <p class="login-text">
                  Already have an account?
                  <router-link 
                    to="/login" 
                    class="login-link"
                  >
                    Sign In
                  </router-link>
                </p>
              </div>

              <!-- Info Box -->
              <div class="info-box">
                <div class="info-header">
                  <v-icon size="16" class="mr-1">mdi-shield-check-outline</v-icon>
                  Secure Registration
                </div>
                <p class="info-text">Your information is encrypted and secure. We'll never share your details.</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="register-footer">
            <p>© 2025 Seafood Delight. All rights reserved.</p>
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
import { ref, reactive, nextTick } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const formRef = ref(null);

const form = reactive({
  name: '',
  username: '',
  contact_number: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms_accepted: false,
});

const errors = reactive({
  name: '',
  username: '',
  contact_number: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const showPassword = ref(false);
const loading = ref(false);
const formError = ref('');
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

const validateForm = () => {
  // Reset errors
  errors.name = '';
  errors.username = '';
  errors.contact_number = '';
  errors.email = '';
  errors.password = '';
  errors.password_confirmation = '';
  
  let isValid = true;
  
  if (!form.name) {
    errors.name = 'Full name is required';
    isValid = false;
  }
  
  if (!form.username) {
    errors.username = 'Username is required';
    isValid = false;
  } else if (form.username.length < 3) {
    errors.username = 'Username must be at least 3 characters';
    isValid = false;
  }

  if (!form.contact_number) {
    errors.contact_number = 'Phone number is required';
    isValid = false;
  } else if (!/^(09|\+639)\d{9}$/.test(form.contact_number)) {
    errors.contact_number = 'Please enter a valid PH mobile number';
    isValid = false;
  }
  
  if (!form.email) {
    errors.email = 'Email is required';
    isValid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email';
    isValid = false;
  }
  
  if (!form.password) {
    errors.password = 'Password is required';
    isValid = false;
  } else if (form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters';
    isValid = false;
  }
  
  if (!form.password_confirmation) {
    errors.password_confirmation = 'Please confirm your password';
    isValid = false;
  } else if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match';
    isValid = false;
  }
  
  if (!form.terms_accepted) {
    showSnackbar('Please accept the terms and conditions', 'warning');
    isValid = false;
  }
  
  return isValid;
};

const handleRegister = async () => {
  if (!validateForm()) return;
  
  loading.value = true;
  formError.value = '';
  try {
    const response = await authStore.register(form);
    if (response.requires_otp) {
      showSnackbar('Registration successful! Please verify your phone number.', 'success');
      setTimeout(() => {
        router.push({ 
          path: '/verify-otp', 
          query: { email: form.email } 
        });
      }, 1500);
    } else {
      showSnackbar('Registration successful! Redirecting to login...', 'success');
      setTimeout(() => {
        router.push('/login');
      }, 1500);
    }
  } catch (error) {
    console.error('Registration error:', error);
    
    if (error.response?.data?.errors) {
      const serverErrors = error.response.data.errors;
      const errorMessages = [];
      
      // Set field-specific errors
      Object.keys(serverErrors).forEach(key => {
        const errorMsg = serverErrors[key][0];
        if (key === 'name') errors.name = errorMsg;
        if (key === 'username') errors.username = errorMsg;
        if (key === 'contact_number') errors.contact_number = errorMsg;
        if (key === 'email') errors.email = errorMsg;
        if (key === 'password') errors.password = errorMsg;
        if (key === 'password_confirmation') errors.password_confirmation = errorMsg;
        errorMessages.push(errorMsg);
      });
      
      // Show combined error message in the form alert
      formError.value = errorMessages.join(' | ');
    } else {
      formError.value = error.response?.data?.message || 'Registration failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.register-page {
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

.register-card {
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

.register-header {
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 50%, #42a5f5 100%);
  padding: 2.5rem 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.register-header::before {
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

.register-form-wrapper {
  padding: 2.5rem 2rem 2rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 0.5rem;
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

.form-input.input-error {
  border-color: #dc2626;
  background-color: #fef2f2;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.form-input.input-error:focus {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
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
  font-size: 0.875rem;
  color: #dc2626;
  margin-top: 0.5rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  background-color: #fef2f2;
  border-left: 3px solid #dc2626;
  border-radius: 4px;
}

.error-alert {
  border-radius: 12px !important;
  animation: shake 0.5s ease-in-out;
}

.error-alert .alert-content {
  margin-left: 0.5rem;
}

.error-alert .alert-content strong {
  display: block;
  margin-bottom: 0.25rem;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
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

.btn-register {
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

.btn-register::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.btn-register:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 8px 20px 0 rgba(25, 118, 210, 0.5);
}

.btn-register:hover:not(:disabled)::before {
  left: 100%;
}

.btn-register:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 2px 6px 0 rgba(25, 118, 210, 0.4);
}

.btn-register:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.login-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
  text-align: center;
}

.login-text {
  font-size: 0.9375rem;
  color: #6b7280;
  margin: 0;
}

.login-link {
  color: #2196f3;
  text-decoration: none;
  font-weight: 600;
  transition: color 200ms;
}

.login-link:hover {
  color: #1976d2;
  text-decoration: underline;
}

.info-box {
  margin-top: 1.5rem;
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

.register-footer {
  text-align: center;
  padding: 1.5rem 0;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
}

.register-footer p {
  margin: 0;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 0;
  }
  
  .register-header {
    padding: 2rem 1.5rem;
  }
  
  .header-title {
    font-size: 1.5rem;
  }
  
  .header-icon-wrapper {
    width: 64px;
    height: 64px;
  }
  
  .register-form-wrapper {
    padding: 2rem 1.5rem 1.5rem;
  }
}
</style>
