<template>
  <div class="checkout-page pa-4">
    <v-container>
      <v-row>
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold mb-2">Checkout</h1>
          <v-breadcrumbs :items="breadcrumbs"></v-breadcrumbs>
        </v-col>
      </v-row>

      <v-row>
        <!-- Delivery Information Form -->
        <v-col cols="12" md="8">
          <v-card class="mb-4">
            <v-card-title>Delivery Information</v-card-title>
            <v-divider></v-divider>
            
            <v-card-text>
              <v-form ref="form" v-model="formValid">
                <v-text-field
                  v-model="orderData.contact_number"
                  label="Contact Number *"
                  prepend-inner-icon="mdi-phone"
                  variant="outlined"
                  :rules="[rules.required, rules.phone]"
                  placeholder="09XX XXX XXXX"
                ></v-text-field>

                <v-textarea
                  v-model="orderData.delivery_address"
                  label="Delivery Address *"
                  prepend-inner-icon="mdi-map-marker"
                  variant="outlined"
                  rows="3"
                  :rules="[rules.required]"
                  placeholder="Street, Barangay, City"
                ></v-textarea>

                <v-text-field
                  v-model="orderData.preferred_delivery_date"
                  label="Preferred Delivery Date"
                  prepend-inner-icon="mdi-calendar"
                  type="date"
                  variant="outlined"
                  :min="minDate"
                  hint="Deliveries are scheduled 2-3 days from order"
                ></v-text-field>

                <v-textarea
                  v-model="orderData.notes"
                  label="Order Notes (Optional)"
                  prepend-inner-icon="mdi-note-text"
                  variant="outlined"
                  rows="2"
                  placeholder="Any special instructions for delivery"
                ></v-textarea>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- Order Items Review -->
          <v-card>
            <v-card-title>Order Items ({{ cartStore.itemCount }})</v-card-title>
            <v-divider></v-divider>
            
            <v-list>
              <v-list-item
                v-for="item in cartStore.items"
                :key="item.product.id"
              >
                <template v-slot:prepend>
                  <v-avatar size="60" rounded>
                    <v-img :src="item.product.primary_image?.path || '/images/placeholder-product.jpg'"></v-img>
                  </v-avatar>
                </template>

                <v-list-item-title>{{ item.product.name }}</v-list-item-title>
                <v-list-item-subtitle>
                  Qty: {{ item.quantity }} × ₱{{ item.product.price }} = ₱{{ item.subtotal.toFixed(2) }}
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card>
        </v-col>

        <!-- Order Summary -->
        <v-col cols="12" md="4">
          <v-card sticky class="order-summary">
            <v-card-title>Order Summary</v-card-title>
            <v-divider></v-divider>

            <v-card-text>
              <div class="summary-row">
                <span>Subtotal</span>
                <span class="font-weight-bold">₱{{ cartStore.total.toFixed(2) }}</span>
              </div>
              
              <div class="summary-row">
                <span>Delivery Fee</span>
                <span class="font-weight-bold">₱0.00</span>
              </div>

              <v-divider class="my-3"></v-divider>

              <div class="summary-row text-h6">
                <span class="font-weight-bold">Total</span>
                <span class="font-weight-bold">₱{{ cartStore.total.toFixed(2) }}</span>
              </div>

              <v-alert type="info" variant="tonal" class="mt-4" density="compact">
                <div class="text-caption">
                  <strong>Payment Method:</strong> Cash on Delivery
                </div>
              </v-alert>

              <v-alert type="warning" variant="tonal" class="mt-2" density="compact">
                <div class="text-caption">
                  Please prepare exact amount upon delivery
                </div>
              </v-alert>
            </v-card-text>

            <v-card-actions class="flex-column pa-4">
              <v-btn
                color="primary"
                size="large"
                block
                :loading="loading"
                :disabled="!formValid || !cartStore.hasItems"
                @click="placeOrder"
              >
                <v-icon start>mdi-check-circle</v-icon>
                Place Order
              </v-btn>

              <v-btn
                variant="text"
                block
                class="mt-2"
                @click="$router.back()"
              >
                Back to Cart
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Success Dialog -->
    <v-dialog v-model="successDialog" max-width="500" persistent>
      <v-card>
        <v-card-text class="text-center pa-8">
          <v-icon size="64" color="success" class="mb-4">mdi-check-circle</v-icon>
          <h2 class="text-h5 mb-2">Order Placed Successfully!</h2>
          <p class="text-grey-darken-1 mb-4">Your order #{{ orderNumber }} has been received.</p>
          <p class="text-body-2">We'll process your order and notify you about delivery schedule.</p>
        </v-card-text>
        <v-card-actions class="justify-center pb-4">
          <v-btn color="primary" variant="flat" @click="goToOrders">
            View My Orders
          </v-btn>
          <v-btn variant="text" @click="continueShopping">
            Continue Shopping
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color">
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useOrderStore } from '@/stores/orders'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const cartStore = useCartStore()
const orderStore = useOrderStore()
const authStore = useAuthStore()

const form = ref(null)
const formValid = ref(false)
const loading = ref(false)
const successDialog = ref(false)
const orderNumber = ref('')

const orderData = ref({
  contact_number: authStore.user?.contact_number || '',
  delivery_address: authStore.user?.delivery_address || '',
  preferred_delivery_date: '',
  notes: ''
})

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const breadcrumbs = [
  { title: 'Products', to: '/customer/products' },
  { title: 'Cart', to: '/customer/cart' },
  { title: 'Checkout', disabled: true }
]

const rules = {
  required: v => !!v || 'This field is required',
  phone: v => /^09\d{9}$/.test(v) || 'Invalid phone number format'
}

const minDate = computed(() => {
  const date = new Date()
  date.setDate(date.getDate() + 2)
  return date.toISOString().split('T')[0]
})

const placeOrder = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  loading.value = true
  try {
    // Prepare order items
    const items = cartStore.items.map(item => ({
      product_id: item.product.id,
      quantity: item.quantity
    }))

    // Create order
    const order = await orderStore.createOrder({
      items,
      delivery_address: orderData.value.delivery_address,
      contact_number: orderData.value.contact_number,
      preferred_delivery_date: orderData.value.preferred_delivery_date || null,
      notes: orderData.value.notes || null
    })

    orderNumber.value = order.order_number
    await cartStore.clearCart()
    successDialog.value = true
  } catch (error) {
    snackbar.value = {
      show: true,
      message: error.response?.data?.message || 'Failed to place order',
      color: 'error'
    }
  } finally {
    loading.value = false
  }
}

const goToOrders = () => {
  successDialog.value = false
  router.push({ name: 'customer.orders' })
}

const continueShopping = () => {
  successDialog.value = false
  router.push({ name: 'customer.products' })
}

onMounted(async () => {
  await cartStore.fetchCart()
  
  if (!cartStore.hasItems) {
    router.push({ name: 'customer.cart' })
  }
})
</script>

<style scoped>
.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.order-summary {
  position: sticky;
  top: 80px;
}
</style>

