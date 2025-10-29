<template>
  <v-card>
    <v-card-title>
      <span class="text-h5">{{ formTitle }}</span>
    </v-card-title>

    <v-card-text>
      <v-container>
        <v-form ref="form">
          <v-row>
            <v-col cols="12">
              <v-text-field v-model="formData.name" label="Category Name" :rules="[rules.required]"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="formData.description" label="Description" rows="3"></v-textarea>
            </v-col>
            <v-col cols="12">
              <v-select
                v-model="formData.parent_id"
                :items="availableParentCategories"
                item-title="name"
                item-value="id"
                label="Parent Category (optional)"
                clearable
              ></v-select>
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="formData.status"
                :items="['active', 'inactive']"
                label="Status"
                :rules="[rules.required]"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="formData.sort_order" label="Sort Order" type="number"></v-text-field>
            </v-col>
            
            <!-- Category Image Upload -->
            <v-col cols="12">
              <v-divider class="my-4"></v-divider>
              <h4 class="text-subtitle-1 mb-3">Category Image</h4>
              
              <!-- Current Image (for edit mode) -->
              <div v-if="props.categoryId && currentImageUrl" class="mb-4">
                <v-img :src="currentImageUrl" height="200" width="200" contain class="mb-2"></v-img>
                <v-btn size="small" color="red" @click="deleteCurrentImage" :disabled="uploading">
                  Remove Current Image
                </v-btn>
              </div>
              
              <!-- Upload New Image -->
              <v-file-input
                v-model="selectedFile"
                label="Upload Category Image"
                accept="image/*"
                prepend-icon="mdi-image"
                show-size
                :disabled="uploading"
                @change="previewImage"
              ></v-file-input>
              
              <!-- Image Preview -->
              <div v-if="imagePreview" class="mt-4">
                <h4 class="text-subtitle-2 mb-2">Preview:</h4>
                <v-img :src="imagePreview" height="200" width="200" contain></v-img>
              </div>
              
              <!-- Upload Progress -->
              <v-progress-linear 
                v-if="uploading" 
                :model-value="uploadProgress" 
                color="primary" 
                class="mt-2"
              ></v-progress-linear>
            </v-col>
          </v-row>
        </v-form>
      </v-container>
    </v-card-text>

    <v-card-actions>
      <v-spacer></v-spacer>
      <v-btn color="blue-darken-1" variant="text" @click="close">Cancel</v-btn>
      <v-btn color="blue-darken-1" variant="text" @click="save" :loading="loading">Save</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useCategoryStore } from '@/stores/categories';
import { useNotificationStore } from '@/stores/notifications';

const props = defineProps({
  categoryId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['close', 'save']);

const categoryStore = useCategoryStore();
const notificationStore = useNotificationStore();

const form = ref(null);
const loading = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const selectedFile = ref(null);
const imagePreview = ref('');
const currentImageUrl = ref('');

const formData = ref({
  name: '',
  description: '',
  parent_id: null,
  status: 'active',
  sort_order: 0,
});

const rules = {
  required: value => !!value || 'Required.',
};

const formTitle = computed(() => (props.categoryId ? 'Edit Category' : 'New Category'));

const availableParentCategories = computed(() => {
  // Exclude the current category from the list of potential parents to avoid circular references
  return categoryStore.categories.filter(cat => cat.id !== props.categoryId);
});

watch(() => props.categoryId, (newId) => {
  if (newId) {
    loadCategory(newId);
  } else {
    resetForm();
  }
}, { immediate: true });

function loadCategory(id) {
  const category = categoryStore.categories.find(c => c.id === id);
  if (category) {
    formData.value = { ...category };
    currentImageUrl.value = category.image_url || '';
  }
}

// Image handling functions
function previewImage() {
  imagePreview.value = '';
  if (selectedFile.value && selectedFile.value.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(selectedFile.value);
  }
}

async function uploadImage(categoryId) {
  if (!selectedFile.value) return;
  
  uploading.value = true;
  uploadProgress.value = 0;
  
  try {
    // Simulate upload progress
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += 10;
      }
    }, 100);
    
    await categoryStore.uploadCategoryImage(categoryId, selectedFile.value);
    
    clearInterval(progressInterval);
    uploadProgress.value = 100;
    
    // Clear selections after successful upload
    selectedFile.value = null;
    imagePreview.value = '';
    
    // Update current image URL (assuming the response contains the new image URL)
    const updatedCategory = await categoryStore.fetchCategory(categoryId);
    currentImageUrl.value = updatedCategory.image_url || '';
  } catch (error) {
    notificationStore.showApiError(error);
    throw error;
  } finally {
    uploading.value = false;
    uploadProgress.value = 0;
  }
}

function deleteCurrentImage() {
  currentImageUrl.value = '';
  // In a real app, you'd call an API to delete the image
  // For now, we'll just clear the display
}

function resetForm() {
  formData.value = {
    name: '',
    description: '',
    parent_id: null,
    status: 'active',
    sort_order: 0,
  };
  selectedFile.value = null;
  imagePreview.value = '';
  currentImageUrl.value = '';
}

async function save() {
  const { valid } = await form.value.validate();
  if (!valid) return;

  loading.value = true;
  try {
    let categoryId;
    
    if (props.categoryId) {
      // Update existing category
      await categoryStore.updateCategory(props.categoryId, formData.value);
      categoryId = props.categoryId;
    } else {
      // Create new category
      const newCategory = await categoryStore.createCategory(formData.value);
      categoryId = newCategory.id;
    }
    
    // Upload image if one is selected
    if (selectedFile.value) {
      await uploadImage(categoryId);
    }
    
    notificationStore.showSuccess(
      props.categoryId ? 'Category updated successfully!' : 'Category created successfully!'
    );
    
    emit('save');
  } catch (error) {
    notificationStore.showApiError(error);
  } finally {
    loading.value = false;
  }
}

function close() {
  emit('close');
}

onMounted(() => {
  if (categoryStore.categories.length === 0) {
    categoryStore.fetchCategories({ all: true });
  }
});
</script>
