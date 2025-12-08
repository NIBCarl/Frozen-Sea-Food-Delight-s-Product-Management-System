<template>
  <div class="product-detail-page pa-4">
    <v-container>
      <!-- Breadcrumbs -->
      <v-breadcrumbs :items="breadcrumbs" class="px-0 mb-4">
        <template v-slot:divider>
          <v-icon icon="mdi-chevron-right"></v-icon>
        </template>
      </v-breadcrumbs>

      <!-- Loading State -->
      <v-row v-if="productStore.loading" justify="center" class="py-12">
        <v-col cols="12" class="text-center">
          <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
          <p class="mt-4 text-grey">Loading product details...</p>
        </v-col>
      </v-row>

      <!-- Product Content -->
      <v-row v-else-if="product">
        <!-- Left Column: Images -->
        <v-col cols="12" md="6">
          <v-card class="pa-2" elevation="0" border>
            <v-img
              :src="selectedImage || '/images/placeholder-product.jpg'"
              aspect-ratio="1"
              cover
              class="rounded-lg bg-grey-lighten-4"
              height="400"
            >
              <template v-slot:placeholder>
                <div class="d-flex align-center justify-center fill-height">
                  <v-progress-circular indeterminate color="grey-lighten-4"></v-progress-circular>
                </div>
              </template>
            </v-img>
          </v-card>

          <!-- Thumbnails -->
          <div class="d-flex mt-4 overflow-x-auto py-2 gap-2" v-if="product.images?.length > 1">
            <v-card
              v-for="image in product.images"
              :key="image.id"
              :class="['cursor-pointer mr-2', { 'ring-primary': selectedImage === image.path }]"
              @click="selectedImage = image.path"
              width="80"
              height="80"
              elevation="0"
              border
            >
              <v-img :src="image.path" aspect-ratio="1" cover class="rounded"></v-img>
            </v-card>
          </div>
        </v-col>

        <!-- Right Column: Details -->
        <v-col cols="12" md="6">
          <div>
            <!-- Category & Status -->
            <div class="d-flex align-center mb-2">
              <v-chip
                color="primary"
                size="small"
                variant="tonal"
                class="mr-2"
                v-if="product.category"
              >
                {{ product.category.name }}
              </v-chip>
              <v-chip
                :color="stockStatus.color"
                size="small"
                variant="flat"
              >
                {{ stockStatus.text }}
              </v-chip>
            </div>

            <!-- Title & Price -->
            <h1 class="text-h4 font-weight-bold mb-2">{{ product.name }}</h1>
            <div class="text-h4 text-primary font-weight-bold mb-4">
              ₱{{ product.price }}
              <span class="text-body-2 text-grey ml-2">/ {{ product.unit || 'piece' }}</span>
            </div>

            <v-divider class="mb-6"></v-divider>

            <!-- Description -->
            <div class="mb-6">
              <h3 class="text-subtitle-1 font-weight-bold mb-2">Description</h3>
              <p class="text-body-1 text-grey-darken-1">{{ product.description }}</p>
            </div>

            <!-- Seafood Attributes -->
            <v-card variant="tonal" color="info" class="mb-6" v-if="isSeafoodProduct">
              <v-card-title class="text-subtitle-2 font-weight-bold">
                <v-icon icon="mdi-fish" size="small" class="mr-2"></v-icon>
                Product Specifications
              </v-card-title>
              <v-card-text>
                <v-row dense>
                  <v-col cols="6" v-if="product.fish_type">
                    <div class="text-caption text-grey-darken-2">Fish Type</div>
                    <div class="font-weight-medium">{{ product.fish_type }}</div>
                  </v-col>
                  <v-col cols="6" v-if="product.origin_waters">
                    <div class="text-caption text-grey-darken-2">Origin</div>
                    <div class="font-weight-medium">{{ product.origin_waters }}</div>
                  </v-col>
                  <v-col cols="6" v-if="product.freshness_grade">
                    <div class="text-caption text-grey-darken-2">Freshness Grade</div>
                    <v-chip size="x-small" :color="getGradeColor(product.freshness_grade)" class="font-weight-bold">
                      Grade {{ product.freshness_grade }}
                    </v-chip>
                  </v-col>
                  <v-col cols="6">
                    <div class="text-caption text-grey-darken-2">State</div>
                    <div class="font-weight-medium">
                      <v-icon :icon="product.is_frozen ? 'mdi-snowflake' : 'mdi-water'" size="small" :color="product.is_frozen ? 'info' : 'success'"></v-icon>
                      {{ product.is_frozen ? 'Frozen' : 'Fresh' }}
                    </div>
                  </v-col>
                  <v-col cols="6" v-if="product.catch_date">
                    <div class="text-caption text-grey-darken-2">Catch Date</div>
                    <div class="font-weight-medium">{{ formatDate(product.catch_date) }}</div>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Add to Cart Section -->
            <div class="d-flex align-end gap-4 mb-6">
              <div style="width: 120px">
                <div class="text-caption mb-1">Quantity</div>
                <v-text-field
                  v-model.number="quantity"
                  type="number"
                  density="compact"
                  variant="outlined"
                  hide-details
                  min="1"
                  :max="product.stock_quantity"
                  prepend-inner-icon="mdi-minus"
                  append-inner-icon="mdi-plus"
                  @click:prepend-inner="quantity > 1 && quantity--"
                  @click:append-inner="quantity < product.stock_quantity && quantity++"
                ></v-text-field>
              </div>
              <v-btn
                color="primary"
                size="large"
                :disabled="!isAvailable"
                :loading="cartStore.loading"
                @click="addToCart"
                prepend-icon="mdi-cart"
                class="flex-grow-1"
              >
                {{ isAvailable ? 'Add to Cart' : 'Out of Stock' }}
              </v-btn>
            </div>

            <!-- Supplier Info -->
            <div class="d-flex align-center pa-4 bg-grey-lighten-4 rounded-lg">
              <v-avatar color="primary" class="mr-3">
                <span class="text-h6 text-white">{{ product.creator?.name?.charAt(0) || 'S' }}</span>
              </v-avatar>
              <div>
                <div class="text-caption text-grey-darken-1">Supplier</div>
                <div class="font-weight-bold">{{ product.creator?.name || 'Unknown Supplier' }}</div>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Error State -->
      <v-row v-else-if="error">
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="error">mdi-alert-circle</v-icon>
          <p class="text-h6 mt-4">Error Loading Product</p>
          <p class="text-grey mb-4">{{ error }}</p>
          <v-btn color="primary" @click="loadProduct">Try Again</v-btn>
        </v-col>
      </v-row>

      <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top">
        {{ snackbar.message }}
      </v-snackbar>
    </v-container>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '@/stores/products'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const router = useRouter()
const productStore = useProductStore()
const cartStore = useCartStore()

const quantity = ref(1)
const selectedImage = ref(null)
const error = ref('')
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const productId = computed(() => route.params.id)
const product = computed(() => productStore.product)

const breadcrumbs = computed(() => [
  { title: 'Browse Products', disabled: false, to: '/customer/products' },
  { title: product.value?.category?.name || 'Category', disabled: true },
  { title: product.value?.name || 'Product', disabled: true },
])

const isSeafoodProduct = computed(() => {
  if (!product.value) return false
  return product.value.fish_type || product.value.origin_waters || product.value.catch_date
})

const isAvailable = computed(() => {
  return product.value?.stock_quantity > 0 && product.value?.status === 'active'
})

const stockStatus = computed(() => {
  if (!product.value) return { text: 'Unknown', color: 'grey' }
  if (product.value.stock_quantity <= 0) return { text: 'Out of Stock', color: 'error' }
  if (product.value.stock_quantity <= product.value.min_stock_level) return { text: 'Low Stock', color: 'warning' }
  return { text: 'In Stock', color: 'success' }
})

const getGradeColor = (grade) => {
  const colors = { 'A': 'success', 'B': 'warning', 'C': 'error' }
  return colors[grade] || 'grey'
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const loadProduct = async () => {
  try {
    await productStore.fetchProduct(productId.value)
    // Set initial selected image
    if (product.value?.images?.length) {
      const primary = product.value.images.find(img => img.is_primary)
      selectedImage.value = primary ? primary.path : product.value.images[0].path
    } else {
      selectedImage.value = product.value?.primary_image?.path
    }
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load product'
  }
}

const addToCart = async () => {
  try {
    await cartStore.addItem(product.value.id, quantity.value)
    snackbar.value = {
      show: true,
      message: 'Product added to cart successfully',
      color: 'success'
    }
    // Reset quantity
    quantity.value = 1
  } catch (e) {
    snackbar.value = {
      show: true,
      message: e?.response?.data?.message || 'Failed to add to cart',
      color: 'error'
    }
  }
}

onMounted(loadProduct)

// Watch for route changes to reload product if ID changes
watch(() => route.params.id, (newId) => {
  if (newId) loadProduct()
})
</script>

<style scoped>
.ring-primary {
  border: 2px solid rgb(var(--v-theme-primary));
}
</style>
