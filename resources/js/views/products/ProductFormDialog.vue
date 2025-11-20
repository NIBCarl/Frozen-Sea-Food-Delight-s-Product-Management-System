<template>
  <v-card>
    <v-card-title class="text-h5 pa-4">
      {{ productId ? 'Edit Product' : 'New Product' }}
    </v-card-title>

    <v-divider></v-divider>

    <v-card-text class="pa-4" style="max-height: 70vh; overflow-y: auto;">
      <v-form ref="form" v-model="valid">
        <!-- Basic Information Section -->
        <v-row>
          <v-col cols="12">
            <div class="text-h6 mb-2">Basic Information</div>
          </v-col>

          <v-col cols="12" md="8">
            <v-text-field
              v-model="formData.name"
              label="Product Name *"
              :rules="[rules.required]"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="formData.category_id"
              :items="categories"
              item-title="name"
              item-value="id"
              label="Category *"
              :rules="[rules.required]"
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="formData.description"
              label="Description"
              variant="outlined"
              rows="3"
            ></v-textarea>
          </v-col>
        </v-row>

        <!-- Seafood-Specific Information -->
        <v-row>
          <v-col cols="12">
            <div class="text-h6 mb-2 mt-2">
              <v-icon class="me-2">mdi-fish</v-icon>
              Seafood Information
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="formData.fish_type"
              :items="fishTypes"
              label="Fish Type"
              variant="outlined"
              density="comfortable"
              clearable
            ></v-select>
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="formData.freshness_grade"
              :items="['A', 'B', 'C']"
              label="Freshness Grade"
              variant="outlined"
              density="comfortable"
            >
              <template v-slot:item="{ item, props }">
                <v-list-item v-bind="props">
                  <template v-slot:prepend>
                    <v-chip :color="getGradeColor(item.value)" size="small">{{ item.value }}</v-chip>
                  </template>
                </v-list-item>
              </template>
            </v-select>
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="formData.catch_date"
              label="Catch Date"
              type="date"
              variant="outlined"
              density="comfortable"
              :max="today"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="formData.processing_date"
              label="Processing Date"
              type="date"
              variant="outlined"
              density="comfortable"
              :min="formData.catch_date"
              :max="today"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="formData.expiration_date"
              label="Expiration Date *"
              type="date"
              variant="outlined"
              density="comfortable"
              :min="formData.catch_date || today"
              :rules="[rules.required]"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="formData.origin_waters"
              label="Origin Waters"
              variant="outlined"
              density="comfortable"
              placeholder="e.g., Cebu Waters"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="formData.fishing_method"
              :items="fishingMethods"
              label="Fishing Method"
              variant="outlined"
              density="comfortable"
              clearable
            ></v-select>
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="formData.weight_kg"
              label="Weight (kg)"
              type="number"
              step="0.01"
              min="0"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="formData.storage_temperature"
              label="Storage Temperature"
              variant="outlined"
              density="comfortable"
              placeholder="e.g., -18°C"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="4">
            <v-checkbox
              v-model="formData.is_frozen"
              label="Frozen Product"
              color="primary"
              density="comfortable"
            ></v-checkbox>
          </v-col>
        </v-row>

        <!-- Pricing & Inventory -->
        <v-row>
          <v-col cols="12">
            <div class="text-h6 mb-2 mt-2">Pricing & Inventory</div>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="formData.price"
              label="Price *"
              type="number"
              step="0.01"
              min="0"
              prefix="₱"
              :rules="[rules.required, rules.minValue]"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="formData.cost_price"
              label="Cost Price"
              type="number"
              step="0.01"
              min="0"
              prefix="₱"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="formData.stock_quantity"
              label="Stock Quantity *"
              type="number"
              min="0"
              :rules="[rules.required]"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="formData.min_stock_level"
              label="Minimum Stock Level"
              type="number"
              min="0"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="formData.status"
              :items="['active', 'inactive', 'discontinued']"
              label="Status *"
              :rules="[rules.required]"
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>

          <v-col cols="12" md="4">
            <v-checkbox
              v-model="formData.featured"
              label="Featured Product"
              color="primary"
              density="comfortable"
            ></v-checkbox>
          </v-col>
        </v-row>

        <!-- Product Images Section -->
        <v-row>
          <v-col cols="12">
            <v-divider class="my-4"></v-divider>
            <div class="text-h6 mb-3">
              <v-icon class="me-2">mdi-camera</v-icon>
              Product Images
            </div>
          </v-col>

          <!-- Existing Images (for edit mode) -->
          <v-col v-if="productId && productImages.length > 0" cols="12">
            <div class="text-subtitle-2 mb-2">Current Images:</div>
            <v-row>
              <v-col v-for="image in productImages" :key="image.id" cols="6" sm="4" md="3">
                <v-card class="mb-2">
                  <v-img :src="image.thumbnail_url" height="150" cover></v-img>
                  <v-card-actions class="pa-2">
                    <v-btn 
                      size="small" 
                      :color="image.is_primary ? 'primary' : 'grey'" 
                      @click="setPrimaryImage(image.id)"
                      :disabled="uploading"
                      variant="tonal"
                    >
                      {{ image.is_primary ? 'Primary' : 'Set Primary' }}
                    </v-btn>
                    <v-spacer></v-spacer>
                    <v-btn 
                      size="small" 
                      color="red" 
                      icon="mdi-delete"
                      variant="text"
                      @click="deleteImage(image.id)"
                      :disabled="uploading"
                    ></v-btn>
                  </v-card-actions>
                </v-card>
              </v-col>
            </v-row>
          </v-col>

          <!-- Upload New Images -->
          <v-col cols="12">
            <v-file-input
              v-model="selectedFiles"
              label="Upload Product Images"
              multiple
              accept="image/*"
              prepend-icon="mdi-camera"
              variant="outlined"
              density="comfortable"
              show-size
              counter
              :disabled="uploading"
              @change="previewImages"
              hint="Upload multiple images. First image will be primary."
              persistent-hint
            ></v-file-input>
          </v-col>

          <!-- Image Previews -->
          <v-col v-if="imagePreviews.length > 0" cols="12">
            <div class="text-subtitle-2 mb-2">Preview New Images:</div>
            <v-row>
              <v-col v-for="(preview, index) in imagePreviews" :key="index" cols="6" sm="4" md="3">
                <v-card>
                  <v-img :src="preview" height="150" cover></v-img>
                  <v-card-actions class="pa-2">
                    <v-chip size="small" color="primary" v-if="index === 0">Primary</v-chip>
                    <v-spacer></v-spacer>
                    <v-btn 
                      size="small" 
                      icon="mdi-close" 
                      variant="text"
                      @click="removePreview(index)"
                    ></v-btn>
                  </v-card-actions>
                </v-card>
              </v-col>
            </v-row>
          </v-col>

          <!-- Upload Progress -->
          <v-col v-if="uploading" cols="12">
            <v-progress-linear
              :model-value="uploadProgress"
              color="primary"
              height="20"
              striped
            >
              <strong>{{ Math.ceil(uploadProgress) }}%</strong>
            </v-progress-linear>
          </v-col>
        </v-row>
      </v-form>
    </v-card-text>

    <v-divider></v-divider>

    <v-card-actions class="pa-4">
      <v-spacer></v-spacer>
      <v-btn
        variant="text"
        @click="$emit('close')"
        :disabled="loading"
      >
        Cancel
      </v-btn>
      <v-btn
        color="primary"
        variant="flat"
        @click="save"
        :loading="loading"
        :disabled="!valid"
      >
        {{ productId ? 'Update' : 'Create' }} Product
      </v-btn>
    </v-card-actions>

    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      timeout="3000"
    >
      {{ snackbarText }}
    </v-snackbar>
  </v-card>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useProductStore } from '@/stores/products'
import { useCategoryStore } from '@/stores/categories'
import axios from 'axios'

const props = defineProps({
  productId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['close', 'save'])

const productStore = useProductStore()
const categoryStore = useCategoryStore()

const form = ref(null)
const valid = ref(false)
const loading = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// Image handling refs
const selectedFiles = ref([])
const imagePreviews = ref([])
const productImages = ref([])

const today = computed(() => new Date().toISOString().split('T')[0])

const categories = ref([])

const fishTypes = [
  'Tuna',
  'Bangus (Milkfish)',
  'Tilapia',
  'Squid',
  'Octopus',
  'Shrimp',
  'Prawns',
  'Crabs',
  'Lobster',
  'Mussels',
  'Oysters',
  'Scallops',
  'Grouper',
  'Lapu-Lapu',
  'Mackerel',
  'Sardines',
  'Other'
]

const fishingMethods = [
  'Line Fishing',
  'Net Fishing',
  'Trawling',
  'Trapping',
  'Hand Gathering',
  'Aquaculture/Farmed'
]

const formData = reactive({
  name: '',
  description: '',
  category_id: null,
  price: null,
  cost_price: null,
  stock_quantity: 0,
  min_stock_level: 0,
  status: 'active',
  featured: false,
  // Seafood fields
  fish_type: null,
  catch_date: null,
  expiration_date: null,
  processing_date: null,
  origin_waters: 'Cebu Waters',
  fishing_method: null,
  weight_kg: null,
  storage_temperature: '-18°C',
  is_frozen: true,
  freshness_grade: 'A'
})

const rules = {
  required: value => !!value || 'This field is required',
  minValue: value => value >= 0 || 'Value must be positive'
}

const getGradeColor = (grade) => {
  const colors = { A: 'success', B: 'warning', C: 'error' }
  return colors[grade] || 'grey'
}

const loadCategories = async () => {
  try {
    await categoryStore.fetchCategories()
    categories.value = categoryStore.categories
  } catch (error) {
    console.error('Failed to load categories:', error)
  }
}

const loadProduct = async () => {
  if (!props.productId) return
  
  loading.value = true
  try {
    const product = await productStore.fetchProduct(props.productId)
    Object.keys(formData).forEach(key => {
      if (product[key] !== undefined) {
        formData[key] = product[key]
      }
    })
    // Load product images
    productImages.value = product.images || []
  } catch (error) {
    showSnackbar('Failed to load product', 'error')
  } finally {
    loading.value = false
  }
}

// Image handling functions
const previewImages = () => {
  imagePreviews.value = []
  if (selectedFiles.value && selectedFiles.value.length > 0) {
    Array.from(selectedFiles.value).forEach(file => {
      if (file.type.startsWith('image/')) {
        const reader = new FileReader()
        reader.onload = (e) => {
          imagePreviews.value.push(e.target.result)
        }
        reader.readAsDataURL(file)
      }
    })
  }
}

const removePreview = (index) => {
  imagePreviews.value.splice(index, 1)
  const newFiles = Array.from(selectedFiles.value)
  newFiles.splice(index, 1)
  selectedFiles.value = newFiles
}

const uploadImages = async (productId) => {
  if (!selectedFiles.value || selectedFiles.value.length === 0) return
  
  uploading.value = true
  uploadProgress.value = 0
  
  try {
    const totalFiles = selectedFiles.value.length
    let uploadedCount = 0
    
    for (const file of selectedFiles.value) {
      await productStore.uploadProductImage(productId, file)
      uploadedCount++
      uploadProgress.value = (uploadedCount / totalFiles) * 100
    }
    
    selectedFiles.value = []
    imagePreviews.value = []
    
    if (props.productId) {
      const product = await productStore.fetchProduct(props.productId)
      productImages.value = product.images || []
    }
  } catch (error) {
    console.error('Image upload error:', error)
    const errorMsg = error.response?.data?.message || error.response?.data?.errors?.image?.[0] || 'Error uploading images'
    showSnackbar(errorMsg, 'error')
    throw error
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

const setPrimaryImage = async (imageId) => {
  if (!props.productId) return
  
  try {
    await productStore.setPrimaryImage(props.productId, imageId)
    productImages.value.forEach(img => {
      img.is_primary = img.id === imageId
    })
    showSnackbar('Primary image updated', 'success')
  } catch (error) {
    console.error('Set primary image error:', error)
    showSnackbar('Error setting primary image', 'error')
  }
}

const deleteImage = async (imageId) => {
  if (!props.productId) return
  
  if (!confirm('Are you sure you want to delete this image?')) return
  
  try {
    await productStore.deleteProductImage(props.productId, imageId)
    productImages.value = productImages.value.filter(img => img.id !== imageId)
    showSnackbar('Image deleted successfully', 'success')
  } catch (error) {
    console.error('Delete image error:', error)
    showSnackbar('Error deleting image', 'error')
  }
}

const save = async () => {
  if (!form.value.validate()) return

  loading.value = true
  try {
    let productId = props.productId
    
    if (props.productId) {
      await productStore.updateProduct(props.productId, formData)
      showSnackbar('Product updated successfully!', 'success')
    } else {
      const newProduct = await productStore.createProduct(formData)
      productId = newProduct.id
      showSnackbar('Product created successfully!', 'success')
    }
    
    // Upload images if any are selected
    if (selectedFiles.value && selectedFiles.value.length > 0) {
      try {
        await uploadImages(productId)
        // Refetch the product to get updated images in the store
        await productStore.fetchProduct(productId)
      } catch (imageError) {
        console.error('Image upload failed, but product was saved:', imageError)
        // Don't close dialog - let user retry image upload
        loading.value = false
        return
      }
    }
    
    emit('save')
    emit('close')
  } catch (error) {
    console.error('Product save error:', error)
    showSnackbar(error.response?.data?.message || 'Failed to save product', 'error')
  } finally {
    loading.value = false
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => {
  loadCategories()
  if (props.productId) {
    loadProduct()
  }
})
</script>
