<template>
  <div class="supplier-products">
    <v-container fluid>
      <!-- Header -->
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold">My Products</h1>
            <p class="text-subtitle-1 text-grey">Manage your seafood inventory</p>
          </div>
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openCreateDialog"
          >
            Add Product
          </v-btn>
        </v-col>
      </v-row>

      <!-- Stats Cards -->
      <v-row class="mb-4">
        <v-col cols="12" sm="6" md="3">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar color="primary" size="48" class="me-3">
                  <v-icon color="white">mdi-package-variant</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-grey">Total Products</div>
                  <div class="text-h5 font-weight-bold">{{ totalProducts }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar color="success" size="48" class="me-3">
                  <v-icon color="white">mdi-check-circle</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-grey">Active</div>
                  <div class="text-h5 font-weight-bold">{{ activeProducts }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar color="warning" size="48" class="me-3">
                  <v-icon color="white">mdi-alert</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-grey">Low Stock</div>
                  <div class="text-h5 font-weight-bold">{{ lowStockProducts }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar color="error" size="48" class="me-3">
                  <v-icon color="white">mdi-clock-alert</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-grey">Expiring Soon</div>
                  <div class="text-h5 font-weight-bold">{{ expiringProducts }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Search and Filters -->
      <v-row class="mb-4">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="search"
            prepend-inner-icon="mdi-magnify"
            label="Search products..."
            variant="outlined"
            density="compact"
            hide-details
            clearable
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="statusFilter"
            :items="statusOptions"
            label="Status"
            variant="outlined"
            density="compact"
            hide-details
          ></v-select>
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="stockFilter"
            :items="stockOptions"
            label="Stock Level"
            variant="outlined"
            density="compact"
            hide-details
          ></v-select>
        </v-col>
      </v-row>

      <!-- Products Data Table -->
      <v-card>
        <v-data-table
          :headers="headers"
          :items="filteredProducts"
          :loading="loading"
          :search="search"
          item-value="id"
        >
          <!-- Image Column -->
          <template v-slot:item.image="{ item }">
            <v-avatar size="50" class="my-2">
              <v-img
                :src="item.primaryImage?.thumbnail_url || item.primary_image?.thumbnail_url || '/images/placeholder-product.jpg'"
                alt="Product"
              ></v-img>
            </v-avatar>
          </template>

          <!-- Product Info -->
          <template v-slot:item.name="{ item }">
            <div>
              <div class="font-weight-bold">{{ item.name }}</div>
              <div class="text-caption text-grey">{{ item.product_id }}</div>
            </div>
          </template>

          <!-- Fish Type -->
          <template v-slot:item.fish_type="{ item }">
            <v-chip v-if="item.fish_type" size="small" color="info">
              {{ item.fish_type }}
            </v-chip>
            <span v-else class="text-grey">-</span>
          </template>

          <!-- Stock -->
          <template v-slot:item.stock_quantity="{ item }">
            <v-chip
              :color="getStockColor(item.stock_quantity, item.min_stock_level)"
              size="small"
            >
              {{ item.stock_quantity }} units
            </v-chip>
          </template>

          <!-- Freshness -->
          <template v-slot:item.freshness_grade="{ item }">
            <v-chip
              v-if="item.freshness_grade"
              :color="getGradeColor(item.freshness_grade)"
              size="small"
            >
              Grade {{ item.freshness_grade }}
            </v-chip>
            <span v-else class="text-grey">-</span>
          </template>

          <!-- Expiration -->
          <template v-slot:item.expiration_date="{ item }">
            <div v-if="item.expiration_date">
              <div>{{ formatDate(item.expiration_date) }}</div>
              <v-chip
                :color="getExpirationColor(item.expiration_date)"
                size="x-small"
                class="mt-1"
              >
                {{ getDaysUntilExpiry(item.expiration_date) }} days
              </v-chip>
            </div>
            <span v-else class="text-grey">-</span>
          </template>

          <!-- Status -->
          <template v-slot:item.status="{ item }">
            <v-chip
              :color="item.status === 'active' ? 'success' : 'grey'"
              size="small"
            >
              {{ item.status }}
            </v-chip>
          </template>

          <!-- Actions -->
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-dots-vertical"
                  size="small"
                  variant="text"
                  v-bind="props"
                ></v-btn>
              </template>
              <v-list density="compact">
                <v-list-item prepend-icon="mdi-pencil" @click="editProduct(item)">
                  Edit
                </v-list-item>
                <v-list-item prepend-icon="mdi-eye" @click="viewProduct(item)">
                  View Details
                </v-list-item>
                <v-list-item
                  prepend-icon="mdi-delete"
                  @click="deleteProduct(item)"
                  base-color="error"
                >
                  Delete
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Product Form Dialog -->
    <v-dialog v-model="dialog" max-width="1000px" persistent>
      <ProductFormDialog
        :product-id="editingProductId"
        @close="closeDialog"
        @save="handleSave"
      />
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      timeout="3000"
    >
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useProductStore } from '@/stores/products'
import ProductFormDialog from '../products/ProductFormDialog.vue'

const productStore = useProductStore()

const search = ref('')
const statusFilter = ref('all')
const stockFilter = ref('all')
const loading = ref(false)
const dialog = ref(false)
const editingProductId = ref(null)

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const headers = [
  { title: 'Image', key: 'image', sortable: false },
  { title: 'Product', key: 'name' },
  { title: 'Fish Type', key: 'fish_type' },
  { title: 'Stock', key: 'stock_quantity' },
  { title: 'Price', key: 'price' },
  { title: 'Freshness', key: 'freshness_grade' },
  { title: 'Expiration', key: 'expiration_date' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const statusOptions = [
  { title: 'All Status', value: 'all' },
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' },
  { title: 'Discontinued', value: 'discontinued' }
]

const stockOptions = [
  { title: 'All Stock Levels', value: 'all' },
  { title: 'In Stock', value: 'in_stock' },
  { title: 'Low Stock', value: 'low_stock' },
  { title: 'Out of Stock', value: 'out_of_stock' }
]

// Computed stats
const totalProducts = computed(() => productStore.products.length)

const activeProducts = computed(() => 
  productStore.products.filter(p => p.status === 'active').length
)

const lowStockProducts = computed(() => 
  productStore.products.filter(p => {
    const minLevel = p.min_stock_level || 10
    return p.stock_quantity <= minLevel && p.stock_quantity > 0
  }).length
)

const expiringProducts = computed(() => 
  productStore.products.filter(p => {
    if (!p.expiration_date) return false
    const days = getDaysUntilExpiry(p.expiration_date)
    return days <= 7 && days >= 0
  }).length
)

// Filtered products
const filteredProducts = computed(() => {
  let products = productStore.products

  // Status filter
  if (statusFilter.value !== 'all') {
    products = products.filter(p => p.status === statusFilter.value)
  }

  // Stock filter
  if (stockFilter.value !== 'all') {
    if (stockFilter.value === 'in_stock') {
      products = products.filter(p => p.stock_quantity > (p.min_stock_level || 10))
    } else if (stockFilter.value === 'low_stock') {
      products = products.filter(p => {
        const minLevel = p.min_stock_level || 10
        return p.stock_quantity <= minLevel && p.stock_quantity > 0
      })
    } else if (stockFilter.value === 'out_of_stock') {
      products = products.filter(p => p.stock_quantity === 0)
    }
  }

  return products
})

// Helper functions
const getStockColor = (stock, minLevel = 10) => {
  if (stock === 0) return 'error'
  if (stock <= minLevel) return 'warning'
  return 'success'
}

const getGradeColor = (grade) => {
  const colors = { A: 'success', B: 'warning', C: 'error' }
  return colors[grade] || 'grey'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  })
}

const getDaysUntilExpiry = (expirationDate) => {
  const today = new Date()
  const expiry = new Date(expirationDate)
  const diff = expiry - today
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

const getExpirationColor = (expirationDate) => {
  const days = getDaysUntilExpiry(expirationDate)
  if (days <= 0) return 'error'
  if (days <= 3) return 'error'
  if (days <= 7) return 'warning'
  return 'success'
}

// Actions
const openCreateDialog = () => {
  editingProductId.value = null
  dialog.value = true
}

const editProduct = (product) => {
  editingProductId.value = product.id
  dialog.value = true
}

const viewProduct = (product) => {
  // Navigate to product detail or open detail dialog
  console.log('View product:', product)
}

const deleteProduct = async (product) => {
  if (!confirm(`Are you sure you want to delete "${product.name}"?`)) return

  try {
    await productStore.deleteProduct(product.id)
    snackbar.value = {
      show: true,
      message: 'Product deleted successfully',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to delete product',
      color: 'error'
    }
  }
}

const closeDialog = () => {
  dialog.value = false
  editingProductId.value = null
}

const handleSave = async () => {
  await loadProducts()
  snackbar.value = {
    show: true,
    message: editingProductId.value ? 'Product updated!' : 'Product created!',
    color: 'success'
  }
}

const loadProducts = async () => {
  loading.value = true
  try {
    // Fetch supplier's products only
    await productStore.fetchProducts()
  } catch (error) {
    console.error('Failed to load products:', error)
    snackbar.value = {
      show: true,
      message: 'Failed to load products',
      color: 'error'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
.supplier-products {
  padding: 20px;
}
</style>
