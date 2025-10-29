<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">{{ isEdit ? 'Edit Category' : 'Add Category' }}</h1>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-text>
            <p>Category form will be implemented here</p>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const isEdit = computed(() => !!route.params.id);
</script>
