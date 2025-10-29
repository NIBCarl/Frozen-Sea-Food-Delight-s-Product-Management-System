<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Categories</h1>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            Category Management
            <v-spacer></v-spacer>
            <v-btn color="primary" to="/categories/create">
              <v-icon left>mdi-plus</v-icon>
              Add Category
            </v-btn>
          </v-card-title>
          <v-card-text>
            <p>Category list will be implemented here</p>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
//
</script>
