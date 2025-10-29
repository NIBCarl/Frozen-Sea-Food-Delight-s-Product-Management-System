<template>
  <v-card>
    <v-card-title>
      <span class="text-h5">{{ formTitle }}</span>
    </v-card-title>

    <v-card-text>
      <v-container>
        <v-form ref="form">
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field v-model="formData.name" label="Product Name" :rules="schemas.product.name"></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="formData.category_id"
                :items="categoryStore.categories"
                item-title="name"
                item-value="id"
                label="Category"
                :rules="schemas.product.category_id"
              ></v-select>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="formData.description" label="Description" rows="3"></v-textarea>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <v-text-field v-model="formData.price" label="Price" type="number" prefix="$" :rules="schemas.product.price"></v-text-field>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <v-text-field v-model="formData.cost_price" label="Cost Price" type="number" prefix="$" :rules="[rules.numeric, rules.positive]"></v-text-field>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <v-text-field v-model="formData.stock_quantity" label="Stock Quantity" type="number" :rules="schemas.product.stock_quantity"></v-text-field>
            </v-col>
             <v-col cols="12" sm="6" md="4">
              <v-text-field v-model="formData.min_stock_level" label="Min Stock Level" type="number" :rules="schemas.product.min_stock_level"></v-text-field>
            </v-col>
            <v-col cols="12" sm="6" md="4">
              <v-text-field v-model="formData.sku" label="SKU" :rules="schemas.product.sku"></v-text-field>
            </v-col>
             <v-col cols="12" sm="6" md="4">
              <v-select
                v-model="formData.status"
                :items="['active', 'inactive', 'discontinued']"
                label="Status"
                :rules="schemas.product.status"
              ></v-select>
            </v-col>
            <v-col cols="12">
              <v-checkbox v-model="formData.featured" label="Featured Product"></v-checkbox>
            </v-col>
            
            <!-- Image Upload Section -->
            <v-col cols="12">
              <v-divider class="my-4"></v-divider>
              <h3 class="text-h6 mb-3">Product Images</h3>
              
              <!-- Existing Images (for edit mode) -->
              <div v-if="props.productId && productImages.length > 0" class="mb-4">
                <v-row>
                  <v-col v-for="image in productImages" :key="image.id" cols="12" sm="6" md="3">
                    <v-card class="mb-2">
                      <v-img :src="image.thumbnail_url" height="150" cover></v-img>
                      <v-card-actions class="pa-2">
                        <v-btn 
                          size="small" 
                          :color="image.is_primary ? 'primary' : 'grey'" 
                          @click="setPrimaryImage(image.id)"
                          :disabled="uploading"
                        >
                          {{ image.is_primary ? 'Primary' : 'Set Primary' }}
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-btn 
                          size="small" 
                          color="red" 
                          icon="mdi-delete"
                          @click="deleteImage(image.id)"
                          :disabled="uploading"
                        ></v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-col>
                </v-row>
              </div>
              
              <!-- Upload New Images -->
              <v-file-input
                v-model="selectedFiles"
                label="Upload Product Images"
                multiple
                accept="image/*"
                prepend-icon="mdi-camera"
                show-size
                counter
                :disabled="uploading"
                @change="previewImages"
              ></v-file-input>
              
              <!-- Image Previews -->
              <div v-if="imagePreviews.length > 0" class="mt-4">
                <h4 class="text-subtitle-1 mb-2">Preview New Images:</h4>
                <v-row>
                  <v-col v-for="(preview, index) in imagePreviews" :key="index" cols="12" sm="6" md="3">
                    <v-card>
                      <v-img :src="preview" height="150" cover></v-img>
                      <v-card-actions class="pa-2">
                        <v-btn 
                          size="small" 
                          color="red" 
                          icon="mdi-close"
                          @click="removePreview(index)"
                          :disabled="uploading"
                        ></v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-col>
                </v-row>
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
import { useProductStore } from '@/stores/products';
import { useCategoryStore } from '@/stores/categories';
import { useNotificationStore } from '@/stores/notifications';
import { rules, schemas } from '@/utils/validation';

const props = defineProps({
  productId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['close', 'save']);

const productStore = useProductStore();
const categoryStore = useCategoryStore();
const notificationStore = useNotificationStore();

const form = ref(null);
const loading = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const selectedFiles = ref([]);
const imagePreviews = ref([]);
const productImages = ref([]);

const formData = ref({
  name: '',
  description: '',
  category_id: null,
  price: 0,
  cost_price: 0,
  stock_quantity: 0,
  min_stock_level: 0,
  sku: '',
  status: 'active',
  featured: false,
});

const formTitle = computed(() => (props.productId ? 'Edit Product' : 'New Product'));

watch(() => props.productId, (newId) => {
  if (newId) {
    loadProduct(newId);
  } else {
    resetForm();
  }
}, { immediate: true });

async function loadProduct(id) {
  loading.value = true;
  try {
    const product = await productStore.fetchProduct(id);
    formData.value = { ...product };
    // Load product images
    productImages.value = product.images || [];
  } catch (error) {
    notificationStore.showApiError(error);
  } finally {
    loading.value = false;
  }
}

// Image handling functions
function previewImages() {
  imagePreviews.value = [];
  if (selectedFiles.value && selectedFiles.value.length > 0) {
    Array.from(selectedFiles.value).forEach(file => {
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          imagePreviews.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

function removePreview(index) {
  imagePreviews.value.splice(index, 1);
  // Also remove from selectedFiles
  const newFiles = Array.from(selectedFiles.value);
  newFiles.splice(index, 1);
  selectedFiles.value = newFiles;
}

async function uploadImages(productId) {
  if (!selectedFiles.value || selectedFiles.value.length === 0) return;
  
  uploading.value = true;
  uploadProgress.value = 0;
  
  try {
    const totalFiles = selectedFiles.value.length;
    let uploadedCount = 0;
    
    for (const file of selectedFiles.value) {
      await productStore.uploadProductImage(productId, file);
      uploadedCount++;
      uploadProgress.value = (uploadedCount / totalFiles) * 100;
    }
    
    // Clear selections after successful upload
    selectedFiles.value = [];
    imagePreviews.value = [];
    
    // Reload product images
    if (props.productId) {
      const product = await productStore.fetchProduct(props.productId);
      productImages.value = product.images || [];
    }
  } catch (error) {
    notificationStore.showApiError(error);
    throw error;
  } finally {
    uploading.value = false;
    uploadProgress.value = 0;
  }
}

async function setPrimaryImage(imageId) {
  if (!props.productId) return;
  
  try {
    await productStore.setPrimaryImage(props.productId, imageId);
    // Update local images
    productImages.value.forEach(img => {
      img.is_primary = img.id === imageId;
    });
  } catch (error) {
    notificationStore.showApiError(error);
  }
}

async function deleteImage(imageId) {
  if (!props.productId) return;
  
  try {
    await productStore.deleteProductImage(props.productId, imageId);
    // Remove from local images
    productImages.value = productImages.value.filter(img => img.id !== imageId);
  } catch (error) {
    notificationStore.showApiError(error);
  }
}

function resetForm() {
  formData.value = {
    name: '',
    description: '',
    category_id: null,
    price: 0,
    cost_price: 0,
    stock_quantity: 0,
    min_stock_level: 0,
    sku: '',
    status: 'active',
    featured: false,
  };
  selectedFiles.value = [];
  imagePreviews.value = [];
  productImages.value = [];
}

async function save() {
  const { valid } = await form.value.validate();
  if (!valid) return;

  loading.value = true;
  try {
    let productId;
    
    if (props.productId) {
      // Update existing product
      await productStore.updateProduct(props.productId, formData.value);
      productId = props.productId;
    } else {
      // Create new product
      const newProduct = await productStore.createProduct(formData.value);
      productId = newProduct.id;
    }
    
    // Upload images if any are selected
    if (selectedFiles.value && selectedFiles.value.length > 0) {
      await uploadImages(productId);
    }
    
    notificationStore.showSuccess(
      props.productId ? 'Product updated successfully!' : 'Product created successfully!'
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
    categoryStore.fetchCategories();
  }
});
</script>
