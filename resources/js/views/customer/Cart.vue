<template>
  <div class="cart-page pa-4">
    <v-container>
      <v-row>
        <v-col cols="12">
          <v-btn
            variant="text"
            prepend-icon="mdi-arrow-left"
            @click="$router.back()"
          >
            Continue Shopping
          </v-btn>
        </v-col>
      </v-row>

      <v-row class="mt-4">
        <v-col cols="12" md="8">
          <v-card>
            <v-card-title class="text-h5">
              Shopping Cart
              <v-chip class="ml-2" size="small">{{ cartStore.itemCount }} items</v-chip>
            </v-card-title>

            <v-divider></v-divider>

            <!-- Loading -->
            <v-progress-linear
              v-if="cartStore.loading"
              indeterminate
              color="primary"
            ></v-progress-linear>

            <!-- Empty Cart -->
            <v-card-text v-if="!cartStore.loading && !cartStore.hasItems" class="text-center py-16">
              <v-icon size="64" color="grey">mdi-cart-off</v-icon>
              <p class="text-h6 mt-4">Your cart is empty</p>
              <v-btn
                color="primary"
                class="mt-4"
                to="/customer/products"
              >
                Start Shopping
              </v-btn>
            </v-card-text>

            <!-- Cart Items -->
            <v-list v-else lines="three">
              <v-list-item
                v-for="(item, index) in cartStore.items"
                :key="item.product.id"
                class="cart-item"
              >
                <template v-slot:prepend>
                  <v-avatar size="80" rounded>
                    <v-img
                      :src="item.product.primary_image?.path || '/images/placeholder-product.jpg'"
                      cover
                    ></v-img>
                  </v-avatar>
                </template>

                <v-list-item-title class="text-h6">{{ item.product.name }}</v-list-item-title>
                
                <v-list-item-subtitle>
                  <div class="d-flex align-center mt-2">
                    <span class="text-h6 font-weight-bold mr-4">₱{{ item.product.price }}</span>
                    <v-chip size="small" v-if="item.product.stock_quantity < 10" color="warning">
                      Only {{ item.product.stock_quantity }} left
                    </v-chip>
                  </div>
                </v-list-item-subtitle>

                <template v-slot:append>
                  <div class="d-flex flex-column align-end">
                    <!-- Quantity Controls -->
                    <div class="d-flex align-center mb-2">
                      <v-btn
                        icon="mdi-minus"
                        size="small"
                        variant="text"
                        :disabled="item.quantity <= 1"
                        @click="updateQuantity(item.product.id, item.quantity - 1)"
                      ></v-btn>
                      
                      <v-text-field
                        :model-value="item.quantity"
                        type="number"
                        min="1"
                        :max="item.product.stock_quantity"
                        variant="outlined"
                        density="compact"
                        hide-details
                        style="width: 60px"
                        class="mx-2"
                        @update:model-value="(val) => updateQuantity(item.product.id, parseInt(val))"
                      ></v-text-field>
                      
                      <v-btn
                        icon="mdi-plus"
                        size="small"
                        variant="text"
                        :disabled="item.quantity >= item.product.stock_quantity"
                        @click="updateQuantity(item.product.id, item.quantity + 1)"
                      ></v-btn>
                    </div>

                    <!-- Subtotal -->
                    <div class="text-h6 font-weight-bold">₱{{ item.subtotal.toFixed(2) }}</div>

                    <!-- Remove Button -->
                    <v-btn
                      size="small"
                      variant="text"
                      color="error"
                      prepend-icon="mdi-delete"
                      @click="removeItem(item.product.id)"
                    >
                      Remove
                    </v-btn>
                  </div>
                </template>

                <v-divider v-if="index < cartStore.items.length - 1" class="mt-4"></v-divider>
              </v-list-item>
            </v-list>

            <!-- Clear Cart Button -->
            <v-card-actions v-if="cartStore.hasItems">
              <v-btn
                variant="text"
                color="error"
                @click="confirmClearCart"
              >
                Clear Cart
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>

        <!-- Order Summary -->
        <v-col cols="12" md="4">
          <v-card sticky class="order-summary">
            <v-card-title class="text-h6">Order Summary</v-card-title>
            <v-divider></v-divider>

            <v-card-text>
              <div class="d-flex justify-space-between mb-2">
                <span>Subtotal</span>
                <span class="font-weight-bold">₱{{ cartStore.total.toFixed(2) }}</span>
              </div>
              
              <div class="d-flex justify-space-between mb-2">
                <span>Delivery Fee</span>
                <span class="font-weight-bold">₱0.00</span>
              </div>

              <v-divider class="my-3"></v-divider>

              <div class="d-flex justify-space-between text-h6">
                <span class="font-weight-bold">Total</span>
                <span class="font-weight-bold">₱{{ cartStore.total.toFixed(2) }}</span>
              </div>

              <v-alert
                type="info"
                variant="tonal"
                density="compact"
                class="mt-4"
              >
                <div class="text-caption">
                  Payment Method: Cash on Delivery
                </div>
              </v-alert>
            </v-card-text>

            <v-card-actions>
              <v-btn
                color="primary"
                block
                size="large"
                :disabled="!cartStore.hasItems"
                @click="proceedToCheckout"
              >
                Proceed to Checkout
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Clear Cart Confirmation Dialog -->
    <v-dialog v-model="clearCartDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6">Clear Cart?</v-card-title>
        <v-card-text>
          Are you sure you want to remove all items from your cart?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="clearCartDialog = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" @click="clearCart">Clear Cart</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
    >
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const router = useRouter()
const cartStore = useCartStore()

const clearCartDialog = ref(false)
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const updateQuantity = async (productId, newQuantity) => {
  if (newQuantity < 1) return
  
  try {
    await cartStore.updateItem(productId, newQuantity)
  } catch (error) {
    snackbar.value = {
      show: true,
      message: error.response?.data?.message || 'Failed to update quantity',
      color: 'error'
    }
  }
}

const removeItem = async (productId) => {
  try {
    await cartStore.removeItem(productId)
    snackbar.value = {
      show: true,
      message: 'Item removed from cart',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to remove item',
      color: 'error'
    }
  }
}

const confirmClearCart = () => {
  clearCartDialog.value = true
}

const clearCart = async () => {
  try {
    await cartStore.clearCart()
    clearCartDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Cart cleared',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to clear cart',
      color: 'error'
    }
  }
}

const proceedToCheckout = () => {
  router.push({ name: 'customer.checkout' })
}

const loadCart = async () => {
  try {
    console.log('Loading cart...')
    await cartStore.fetchCart()
    console.log('Cart loaded - Items:', cartStore.items.length, 'Total:', cartStore.total)
  } catch (error) {
    console.error('Failed to load cart:', error)
    snackbar.value = {
      show: true,
      message: 'Failed to load cart',
      color: 'error'
    }
  }
}

onMounted(loadCart)
</script>

<style scoped>
.cart-item {
  padding: 16px;
}

.order-summary {
  position: sticky;
  top: 80px;
}
</style>

