<template>
  <v-container fluid class="pa-4">
    <div class="d-flex align-center mb-6">
      <v-icon size="32" color="primary" class="me-3">mdi-cog</v-icon>
      <h1 class="text-h4">Account Settings</h1>
    </div>

    <v-row>
      <v-col cols="12" md="3">
        <!-- Settings Navigation -->
        <v-card class="mb-4">
          <v-list>
            <v-list-item
              v-for="tab in tabs"
              :key="tab.value"
              :class="{ 'v-list-item--active': activeTab === tab.value }"
              @click="activeTab = tab.value"
            >
              <template v-slot:prepend>
                <v-icon>{{ tab.icon }}</v-icon>
              </template>
              <v-list-item-title>{{ tab.title }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <v-col cols="12" md="9">
        <!-- Profile Tab -->
        <v-card v-if="activeTab === 'profile'" class="pa-4">
          <v-card-title class="d-flex align-center pb-4">
            <v-icon class="me-2">mdi-account</v-icon>
            Profile Information
          </v-card-title>

          <v-row>
            <!-- Avatar Section -->
            <v-col cols="12" md="4" class="text-center">
              <div class="avatar-section">
                <v-avatar size="120" class="mb-4">
                  <v-img
                    :src="avatarUrl"
                    :alt="settingsStore.profile.name"
                  />
                </v-avatar>
                
                <div class="mb-4">
                  <v-btn 
                    color="primary" 
                    variant="outlined" 
                    size="small"
                    @click="$refs.avatarInput.click()"
                    :loading="settingsStore.uploadingAvatar"
                    class="me-2"
                  >
                    <v-icon>mdi-camera</v-icon>&nbsp;Change
                  </v-btn>
                  <v-btn 
                    color="error" 
                    variant="text" 
                    size="small"
                    @click="removeAvatar"
                    :loading="settingsStore.uploadingAvatar"
                    v-if="settingsStore.profile.avatar"
                  >
                    <v-icon>mdi-delete</v-icon>&nbsp;Remove
                  </v-btn>
                </div>
                
                <input
                  ref="avatarInput"
                  type="file"
                  accept="image/*"
                  hidden
                  @change="handleAvatarChange"
                />
                
                <p class="text-caption text-medium-emphasis">
                  Supported: JPG, PNG, GIF<br>Max size: 2MB
                </p>
              </div>
            </v-col>

            <!-- Profile Form -->
            <v-col cols="12" md="8">
              <v-form ref="profileForm" @submit.prevent="updateProfile">
                <v-text-field
                  v-model="profileForm.name"
                  label="Full Name"
                  :rules="[rules.required]"
                  prepend-icon="mdi-account"
                  variant="outlined"
                  class="mb-3"
                />
                
                <v-text-field
                  v-model="profileForm.username"
                  label="Username"
                  :rules="[rules.required]"
                  prepend-icon="mdi-account-circle"
                  variant="outlined"
                  class="mb-3"
                />
                
                <v-text-field
                  v-model="profileForm.email"
                  label="Email"
                  :rules="[rules.required, rules.email]"
                  prepend-icon="mdi-email"
                  variant="outlined"
                  class="mb-4"
                />

                <div class="d-flex">
                  <v-btn
                    color="primary"
                    type="submit"
                    :loading="settingsStore.updating"
                    :disabled="!settingsStore.hasUnsavedChanges"
                  >
                    <v-icon>mdi-content-save</v-icon>&nbsp;Save Changes
                  </v-btn>
                  <v-btn
                    variant="text"
                    @click="resetProfileForm"
                    class="ml-2"
                  >
                    Reset
                  </v-btn>
                </div>
              </v-form>
            </v-col>
          </v-row>
        </v-card>

        <!-- Security Tab -->
        <v-card v-if="activeTab === 'security'" class="pa-4">
          <v-card-title class="d-flex align-center pb-4">
            <v-icon class="me-2">mdi-shield-key</v-icon>
            Security Settings
          </v-card-title>

          <v-form ref="passwordForm" @submit.prevent="changePassword">
            <v-text-field
              v-model="passwordForm.current_password"
              label="Current Password"
              type="password"
              :rules="[rules.required]"
              prepend-icon="mdi-lock"
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="passwordForm.new_password"
              label="New Password"
              type="password"
              :rules="[rules.required, rules.minLength(8)]"
              prepend-icon="mdi-lock-plus"
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="passwordForm.new_password_confirmation"
              label="Confirm New Password"
              type="password"
              :rules="[rules.required, passwordConfirmRule]"
              prepend-icon="mdi-lock-check"
              variant="outlined"
              class="mb-4"
            />

            <v-btn
              color="primary"
              type="submit"
              :loading="settingsStore.updating"
            >
              <v-icon>mdi-key-change</v-icon>&nbsp;Change Password
            </v-btn>
          </v-form>
        </v-card>

        <!-- Preferences Tab -->
        <v-card v-if="activeTab === 'preferences'" class="pa-4">
          <v-card-title class="d-flex align-center pb-4">
            <v-icon class="me-2">mdi-tune</v-icon>
            Preferences
          </v-card-title>

          <v-form @submit.prevent="updatePreferences">
            <!-- Theme Settings -->
            <h3 class="mb-3">Appearance</h3>
            <v-radio-group v-model="preferences.theme" class="mb-4">
              <v-radio
                label="Light Theme"
                value="light"
              />
              <v-radio
                label="Dark Theme"
                value="dark"
              />
            </v-radio-group>

            <v-divider class="mb-4" />

            <!-- Notification Settings -->
            <h3 class="mb-3">Notifications</h3>
            <v-switch
              v-model="preferences.notifications.email_notifications"
              label="Email Notifications"
              color="primary"
              class="mb-2"
            />
            <v-switch
              v-model="preferences.notifications.low_stock_alerts"
              label="Low Stock Alerts"
              color="primary"
              class="mb-2"
            />
            <v-switch
              v-model="preferences.notifications.system_updates"
              label="System Updates"
              color="primary"
              class="mb-4"
            />

            <v-divider class="mb-4" />

            <!-- Dashboard Settings -->
            <h3 class="mb-3">Dashboard</h3>
            <v-switch
              v-model="preferences.dashboard.show_charts"
              label="Show Dashboard Charts"
              color="primary"
              class="mb-3"
            />
            
            <v-select
              v-model="preferences.dashboard.items_per_page"
              :items="itemsPerPageOptions"
              label="Items Per Page"
              variant="outlined"
              class="mb-4"
            />

            <v-btn
              color="primary"
              type="submit"
              :loading="settingsStore.updating"
            >
              <v-icon>mdi-content-save</v-icon>&nbsp;Save Preferences
            </v-btn>
          </v-form>
        </v-card>

        <!-- System Settings Tab (Admin Only) -->
        <v-card v-if="activeTab === 'system' && authStore.hasRole('admin')" class="pa-4">
          <v-card-title class="d-flex align-center pb-4">
            <v-icon class="me-2">mdi-cog-outline</v-icon>
            System Settings
          </v-card-title>
          
          <v-alert type="info" class="mb-4">
            <v-icon>mdi-information</v-icon>
            System settings management is coming soon!
          </v-alert>
          
          <p class="text-medium-emphasis">
            Future features will include:
          </p>
          <ul class="text-medium-emphasis mb-4">
            <li>Application configuration</li>
            <li>Email settings</li>
            <li>Backup management</li>
            <li>System maintenance</li>
          </ul>
        </v-card>
      </v-col>
    </v-row>

    <!-- Success/Error Snackbars -->
    <v-snackbar
      v-model="showSuccess"
      color="success"
      timeout="3000"
    >
      <v-icon>mdi-check-circle</v-icon>&nbsp;{{ successMessage }}
    </v-snackbar>

    <v-snackbar
      v-model="showError"
      color="error"
      timeout="5000"
    >
      <v-icon>mdi-alert-circle</v-icon>&nbsp;{{ errorMessage }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useSettingsStore } from '@/stores/settings';
import { useAuthStore } from '@/stores/auth';

const settingsStore = useSettingsStore();
const authStore = useAuthStore();

const activeTab = ref('profile');
const showSuccess = ref(false);
const showError = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

// Tabs configuration
const tabs = computed(() => [
  { value: 'profile', title: 'Profile', icon: 'mdi-account' },
  { value: 'security', title: 'Security', icon: 'mdi-shield-key' },
  { value: 'preferences', title: 'Preferences', icon: 'mdi-tune' },
  ...(authStore.hasRole('admin') ? [
    { value: 'system', title: 'System', icon: 'mdi-cog-outline' }
  ] : [])
]);

// Form data
const profileForm = reactive({
  name: '',
  username: '',
  email: ''
});

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
});

const preferences = reactive({
  theme: 'light',
  notifications: {
    email_notifications: true,
    low_stock_alerts: true,
    system_updates: true,
  },
  dashboard: {
    show_charts: true,
    items_per_page: 15,
  }
});

// Computed
const avatarUrl = computed(() => {
  if (settingsStore.profile.avatar) {
    return `/storage/${settingsStore.profile.avatar}`;
  }
  return '/images/default-avatar.png';
});

const itemsPerPageOptions = [
  { title: '10 items', value: 10 },
  { title: '15 items', value: 15 },
  { title: '25 items', value: 25 },
  { title: '50 items', value: 50 }
];

// Validation rules
const rules = {
  required: (value) => !!value || 'This field is required',
  email: (value) => /.+@.+\..+/.test(value) || 'Email must be valid',
  minLength: (min) => (value) => (value && value.length >= min) || `Must be at least ${min} characters`,
};

const passwordConfirmRule = computed(() => (value) => {
  return value === passwordForm.new_password || 'Passwords do not match';
});

// Methods
const loadData = async () => {
  await settingsStore.loadProfile();
  await settingsStore.loadPreferences();
  
  // Sync form data
  resetProfileForm();
  Object.assign(preferences, settingsStore.preferences);
};

const resetProfileForm = () => {
  profileForm.name = settingsStore.profile.name;
  profileForm.username = settingsStore.profile.username;
  profileForm.email = settingsStore.profile.email;
};

const updateProfile = async () => {
  const { valid } = await $refs.profileForm.validate();
  if (!valid) return;

  const result = await settingsStore.updateProfile(profileForm);
  if (result.success) {
    showSuccessMessage(result.message);
  } else {
    showErrorMessage(result.message);
  }
};

const changePassword = async () => {
  const { valid } = await $refs.passwordForm.validate();
  if (!valid) return;

  const result = await settingsStore.changePassword(passwordForm);
  if (result.success) {
    // Clear password form
    Object.assign(passwordForm, {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    });
    showSuccessMessage(result.message);
  } else {
    showErrorMessage(result.message);
  }
};

const updatePreferences = async () => {
  const result = await settingsStore.updatePreferences(preferences);
  if (result.success) {
    showSuccessMessage(result.message);
  } else {
    showErrorMessage(result.message);
  }
};

const handleAvatarChange = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file size (2MB)
  if (file.size > 2 * 1024 * 1024) {
    showErrorMessage('Avatar file size must be less than 2MB');
    return;
  }

  const result = await settingsStore.updateAvatar(file);
  if (result.success) {
    showSuccessMessage(result.message);
  } else {
    showErrorMessage(result.message);
  }
};

const removeAvatar = async () => {
  const result = await settingsStore.removeAvatar();
  if (result.success) {
    showSuccessMessage(result.message);
  } else {
    showErrorMessage(result.message);
  }
};

const showSuccessMessage = (message) => {
  successMessage.value = message;
  showSuccess.value = true;
};

const showErrorMessage = (message) => {
  errorMessage.value = message;
  showError.value = true;
};

// Lifecycle
onMounted(() => {
  settingsStore.initializeFromAuth();
  loadData();
});

// Watch for auth changes
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    settingsStore.initializeFromAuth();
  }
});
</script>

<style scoped>
.avatar-section {
  text-align: center;
}

.v-list-item--active {
  background-color: rgba(25, 118, 210, 0.12) !important;
  color: rgb(25, 118, 210) !important;
}

.v-list-item--active .v-icon {
  color: rgb(25, 118, 210) !important;
}
</style>
