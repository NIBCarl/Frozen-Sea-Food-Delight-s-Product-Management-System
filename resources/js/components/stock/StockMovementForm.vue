<template>
  <v-card>
    <v-card-title>
      <span class="text-h5">New Stock Movement</span>
    </v-card-title>

    <v-card-text>
      <v-container>
        <v-form ref="form">
          <v-row>
            <v-col cols="12">
              <v-autocomplete
                v-model="formData.product_id"
                :items="productStore.products"
                item-title="name"
                item-value="id"
                label="Product"
                :rules="[rules.required]"
                @update:search="searchProducts"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="formData.type"
                :items="['in', 'out', 'adjustment']"
                label="Movement Type"
                :rules="[rules.required]"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field 
                v-model="formData.quantity" 
                label="Quantity" 
                type="number" 
                :rules="[rules.required, rules.integer, rules.positive]"
              ></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-text-field v-model="formData.reference" label="Reference (e.g., PO Number, SO Number)"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="formData.notes" label="Notes" rows="3"></v-textarea>
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
import { ref, onMounted } from 'vue';
import { useStockStore } from '@/stores/stock';
import { useProductStore } from '@/stores/products';
import { debounce } from 'lodash';

const emit = defineEmits(['close', 'save']);

const stockStore = useStockStore();
const productStore = useProductStore();

const form = ref(null);
const loading = ref(false);
const formData = ref({
  product_id: null,
  type: 'in',
  quantity: 1,
  reference: '',
  notes: '',
});

const rules = {
  required: value => !!value || 'Required.',
  integer: value => Number.isInteger(Number(value)) || 'Must be an integer.',
  positive: value => value > 0 || 'Must be positive.',
};

const searchProducts = debounce(async (query) => {
  if (query) {
    await productStore.fetchProducts({ search: query, per_page: 10 });
  }
}, 300);

async function save() {
  const { valid } = await form.value.validate();
  if (!valid) return;

  loading.value = true;
  try {
    await stockStore.createMovement(formData.value);
    emit('save');
  } catch (error) {
    console.error('Failed to save stock movement:', error);
    // Handle error
  } finally {
    loading.value = false;
  }
}

function close() {
  emit('close');
}

onMounted(() => {
  // Fetch initial list of products for the dropdown
  if (productStore.products.length === 0) {
    productStore.fetchProducts({ per_page: 10 });
  }
});
</script>
