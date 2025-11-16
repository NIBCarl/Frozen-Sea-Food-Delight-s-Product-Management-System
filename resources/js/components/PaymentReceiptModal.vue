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
          Please send your payment to the GCash number below, then upload your payment receipt to complete your order.
        </div>

        <!-- GCash Payment Instructions -->
        <v-card variant="outlined" class="mb-4 gcash-info">
          <v-card-title class="text-h6 pa-3 bg-primary text-white d-flex align-center">
            <v-icon start>mdi-cellphone</v-icon>
            GCash Payment Details
          </v-card-title>
          <v-card-text class="pa-4">
            <div class="payment-details">
              <div class="detail-row mb-3">
                <div class="detail-label">GCash Number:</div>
                <div class="detail-value gcash-number">
                  <span class="text-h6 font-weight-bold text-primary">09381565021</span>
                  <v-btn
                    icon="mdi-content-copy"
                    size="small"
                    variant="text"
                    color="primary"
                    @click="copyGCashNumber"
                    class="ml-2"
                  ></v-btn>
                </div>
              </div>
              
              <div class="detail-row mb-3">
                <div class="detail-label">Account Name:</div>
                <div class="detail-value font-weight-medium">Frozen Seafood Delight</div>
              </div>
              
              <div class="detail-row mb-3">
                <div class="detail-label">Amount to Send:</div>
                <div class="detail-value">
                  <span class="text-h6 font-weight-bold text-success">₱{{ totalAmount?.toFixed(2) }}</span>
                </div>
              </div>
            </div>
            
            <v-alert type="warning" variant="tonal" density="compact" class="mt-3">
              <div class="text-caption">
                <strong>Important:</strong> Please send the exact amount and upload a clear screenshot of your payment confirmation.
              </div>
            </v-alert>
          </v-card-text>
        </v-card>

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
    
    <!-- Copy Success Snackbar -->
    <v-snackbar
      v-model="copySuccess"
      timeout="2000"
      color="success"
      location="top"
    >
      <v-icon start>mdi-check-circle</v-icon>
      GCash number copied to clipboard!
    </v-snackbar>
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
const copySuccess = ref(false)

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

const copyGCashNumber = async () => {
  try {
    await navigator.clipboard.writeText('09381565021')
    copySuccess.value = true
    setTimeout(() => {
      copySuccess.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy: ', err)
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = '09381565021'
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    copySuccess.value = true
    setTimeout(() => {
      copySuccess.value = false
    }, 2000)
  }
}

const closeModal = () => {
  selectedFile.value = null
  previewUrl.value = ''
  errorMessage.value = ''
  uploading.value = false
  copySuccess.value = false
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

.gcash-info {
  border: 2px solid #1976d2;
}

.payment-details {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #e9ecef;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #6c757d;
  min-width: 120px;
}

.detail-value {
  display: flex;
  align-items: center;
  font-weight: 600;
}

.gcash-number {
  background-color: #e3f2fd;
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid #1976d2;
}

@media (max-width: 600px) {
  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .detail-label {
    min-width: unset;
  }
}
</style>
