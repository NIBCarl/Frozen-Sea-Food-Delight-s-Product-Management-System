<template>
  <v-container fluid class="fill-height">
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="6">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>Register Account</v-toolbar-title>
          </v-toolbar>
          <v-card-text>
            <v-form @submit.prevent="handleRegister" ref="formRef">
              <v-row>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.name"
                    label="Full Name"
                    prepend-icon="mdi-account"
                    :rules="[v => !!v || 'Name is required']"
                    required
                  ></v-text-field>
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="form.username"
                    label="Username"
                    prepend-icon="mdi-account-circle"
                    :rules="[v => !!v || 'Username is required', v => v.length >= 3 || 'Username must be at least 3 characters']"
                    required
                  ></v-text-field>
                </v-col>
              </v-row>
              
              <v-text-field
                v-model="form.email"
                label="Email"
                prepend-icon="mdi-email"
                type="email"
                :rules="[v => !!v || 'Email is required', v => /.+@.+/.test(v) || 'Email must be valid']"
                required
              ></v-text-field>
              
              <v-text-field
                v-model="form.password"
                label="Password"
                prepend-icon="mdi-lock"
                :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                :type="showPassword ? 'text' : 'password'"
                @click:append="showPassword = !showPassword"
                :rules="[v => !!v || 'Password is required', v => v.length >= 8 || 'Password must be at least 8 characters']"
                required
              ></v-text-field>
              
              <v-text-field
                v-model="form.password_confirmation"
                label="Confirm Password"
                prepend-icon="mdi-lock-check"
                :type="showPassword ? 'text' : 'password'"
                :rules="[v => !!v || 'Confirmation is required', v => v === form.password || 'Passwords must match']"
                required
              ></v-text-field>
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
              color="primary"
              :loading="loading"
              :disabled="loading"
              @click="handleRegister"
            >
              Register
            </v-btn>
          </v-card-actions>
          <v-card-text class="text-center">
            <router-link to="/login" class="text-decoration-none">
              Already have an account? Login
            </router-link>
          </v-card-text>
        </v-card>
        <v-snackbar
          v-model="snackbar.show"
          :color="snackbar.color"
          :timeout="3000"
        >
          {{ snackbar.message }}
        </v-snackbar>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const formRef = ref(null);

const form = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
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

const handleRegister = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  
  loading.value = true;
  try {
    await authStore.register(form);
    showSnackbar('Registration successful! Please login.');
    router.push('/login');
  } catch (error) {
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat().join(' ');
      showSnackbar(errors, 'error');
    } else {
      showSnackbar(error.response?.data?.message || 'Registration failed', 'error');
    }
  } finally {
    loading.value = false;
  }
};
</script>
