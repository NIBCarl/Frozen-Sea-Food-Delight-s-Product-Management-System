<template>
  <div class="pa-4">
    <v-container>
      <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="$router.back()">Back</v-btn>

      <v-row class="mt-2">
        <v-col cols="12">
          <h1 class="text-h5 font-weight-bold">Product Details</h1>
          <p class="text-subtitle-2 text-grey-darken-1">ID: {{ productId }}</p>
        </v-col>
      </v-row>

      <v-progress-linear v-if="productStore.loading" indeterminate color="primary" />

      <v-row v-else>
        <v-col cols="12" md="6">
          <v-img :src="product?.primary_image?.path || '/images/placeholder-product.jpg'" aspect-ratio="1.5" cover />
        </v-col>
        <v-col cols="12" md="6">
          <h2 class="text-h6">{{ product?.name || 'Product' }}</h2>
          <p class="text-body-2">{{ product?.description || 'No description available.' }}</p>
          <div class="text-h6">₱{{ product?.price }}</div>
          <p class="text-subtitle-2 mt-2">Supplier: {{ product?.creator?.name }}</p>
        </v-col>
      </v-row>

      <v-alert v-if="error" type="error" variant="tonal" class="mt-4">
        {{ error }}
      </v-alert>
    </v-container>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useProductStore } from '@/stores/products'

const route = useRoute()
const productId = computed(() => route.params.id)
const productStore = useProductStore()
const error = ref('')

const product = computed(() => productStore.product)

onMounted(async () => {
  try {
    await productStore.fetchProduct(productId.value)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load product'
  }
})
</script>

<style scoped></style>
