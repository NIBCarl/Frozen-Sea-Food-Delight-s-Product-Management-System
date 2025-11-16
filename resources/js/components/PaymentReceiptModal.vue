<template>
  <v-dialog v-model="dialog" max-width="500" persistent>
    <v-card>
      <v-card-title class="text-h5 pa-4">
        <v-icon start>mdi-receipt</v-icon>
        Upload Payment Receipt
      </v-card-title>
      
      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <div class="text-body-2 mb-4">
          Please upload your GCash payment receipt to complete your order.
        </div>

        <!-- GCash Info -->
        <v-alert type="info" variant="tonal" class="mb-4">
          <div class="text-body-2">
            <strong>GCash Number:</strong> 09XX XXX XXXX<br>
            <strong>Account Name:</strong> Frozen Seafood Store<br>
            <strong>Amount:</strong> ₱{{ totalAmount?.toFixed(2) }}
          </div>
        </v-alert>

        <!-- File Upload Area -->
        <div class="upload-area mb-4">
          <v-file-input
            v-model="selectedFile"
            label="Select Receipt Image"
            prepend-icon="mdi-camera"
            accept="image/*"
            :rules="fileRules"
            variant="outlined"
            @change="onFileSelect"
          ></v-file-input>

          <!-- Preview -->
          <div v-if="previewUrl" class="mt-3">
            <div class="text-caption mb-2">Preview:</div>
            <v-img
              :src="previewUrl"
              max-height="200"
              contain
              class="border rounded"
            ></v-img>
          </div>
        </div>

        <!-- Upload Progress -->
        <v-progress-linear
          v-if="uploading"
          indeterminate
          color="primary"
          class="mb-3"
        ></v-progress-linear>

        <!-- Error Message -->
        <v-alert
          v-if="errorMessage"
          type="error"
          variant="tonal"
          class="mb-3"
          density="compact"
        >
          {{ errorMessage }}
        </v-alert>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn
          variant="text"
          @click="closeModal"
          :disabled="uploading"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :loading="uploading"
          :disabled="!selectedFile || uploading"
          @click="uploadReceipt"
        >
          Upload & Continue
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  totalAmount: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'receipt-uploaded', 'cancel'])

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const selectedFile = ref(null)
const previewUrl = ref('')
const uploading = ref(false)
const errorMessage = ref('')

const fileRules = [
  v => !!v || 'Receipt image is required',
  v => !v || v.size < 5000000 || 'Image size should be less than 5MB',
  v => !v || ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'].includes(v.type) || 'Only image files are allowed'
]

const onFileSelect = (files) => {
  const file = Array.isArray(files) ? files[0] : files
  if (file) {
    // Create preview URL
    previewUrl.value = URL.createObjectURL(file)
    errorMessage.value = ''
  } else {
    previewUrl.value = ''
  }
}

const uploadReceipt = async () => {
  const file = Array.isArray(selectedFile.value) ? selectedFile.value[0] : selectedFile.value
  console.log('Selected file:', file)
  if (!file) {
    console.log('No file selected')
    return
  }

  uploading.value = true
  errorMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('receipt', file)
    console.log('Uploading file:', file.name, 'Size:', file.size)

    const response = await axios.post('/api/v1/payment-receipts/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    console.log('Upload response:', response.data)

    if (response.data.success) {
      emit('receipt-uploaded', {
        path: response.data.data.path,
        url: response.data.data.url
      })
      closeModal()
    } else {
      errorMessage.value = response.data.message || 'Upload failed'
    }
  } catch (error) {
    console.error('Upload error:', error)
    console.error('Error response:', error.response?.data)
    errorMessage.value = error.response?.data?.message || 'Failed to upload receipt'
  } finally {
    uploading.value = false
  }
}

const closeModal = () => {
  selectedFile.value = null
  previewUrl.value = ''
  errorMessage.value = ''
  uploading.value = false
  emit('cancel')
  dialog.value = false
}

// Clean up preview URL when component unmounts
watch(dialog, (newVal) => {
  if (!newVal && previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = ''
  }
})
</script>

<style scoped>
.upload-area {
  border: 2px dashed #e0e0e0;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  transition: border-color 0.3s;
}

.upload-area:hover {
  border-color: #1976d2;
}

.border {
  border: 1px solid #e0e0e0;
}
</style>
