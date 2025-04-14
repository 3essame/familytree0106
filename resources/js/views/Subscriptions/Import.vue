<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">استيراد الاشتراكات</h1>
      </v-col>
    </v-row>

    <v-card class="mb-4">
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-file-input
              v-model="file"
              accept=".xlsx, .xls"
              label="اختر ملف Excel"
              prepend-icon="mdi-file-excel"
              :rules="fileRules"
              @change="handleFileChange"
            ></v-file-input>
          </v-col>
          <v-col cols="12" md="6" class="d-flex align-center">
            <v-btn
              color="primary"
              :loading="uploading"
              :disabled="!file"
              @click="uploadFile"
            >
              رفع الملف
            </v-btn>
          </v-col>
        </v-row>

        <!-- Template Download Section -->
        <v-row class="mt-4">
          <v-col cols="12">
            <v-alert
              type="info"
              variant="tonal"
              class="mb-4"
            >
              <div class="d-flex align-center">
                <v-icon start class="mr-2">mdi-information</v-icon>
                <div>
                  <strong>تحميل قالب Excel:</strong>
                  <p class="mb-0">قم بتحميل القالب أدناه وملء البيانات حسب النموذج المطلوب.</p>
                </div>
              </div>
            </v-alert>
            <v-btn
              color="success"
              prepend-icon="mdi-download"
              @click="downloadTemplate"
            >
              تحميل القالب
            </v-btn>
          </v-col>
        </v-row>

        <!-- Preview Section -->
        <v-row v-if="previewData.length > 0" class="mt-4">
          <v-col cols="12">
            <h3 class="text-h6 mb-2">معاينة البيانات</h3>
            <v-data-table
              :headers="headers"
              :items="previewData"
              class="elevation-1"
            ></v-data-table>
          </v-col>
        </v-row>

        <!-- Error Messages -->
        <v-row v-if="errors.length > 0" class="mt-4">
          <v-col cols="12">
            <v-alert
              type="error"
              variant="tonal"
            >
              <div v-for="(error, index) in errors" :key="index">
                {{ error }}
              </div>
            </v-alert>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import * as XLSX from 'xlsx';

const file = ref(null);
const uploading = ref(false);
const previewData = ref([]);
const errors = ref([]);

const headers = [
  { title: 'رقم العضو', key: 'member_id' },
  { title: 'حالة الاشتراك', key: 'subscription_status' },
  { title: 'تاريخ الدفع', key: 'payment_date' },
  { title: 'المبلغ', key: 'amount' },
  { title: 'طريقة الدفع', key: 'payment_method' },
  { title: 'ملاحظات', key: 'notes' }
];

const fileRules = [
  v => !v || v.size < 2000000 || 'حجم الملف يجب أن يكون أقل من 2 ميجابايت',
  v => !v || /\.(xlsx|xls)$/.test(v.name) || 'يجب أن يكون الملف بتنسيق Excel'
];

const handleFileChange = async (event) => {
  // Extract the file from the event or use the file ref directly
  const fileToProcess = event instanceof File ? event : file.value;
  
  if (!fileToProcess) {
    previewData.value = [];
    errors.value = [];
    return;
  }

  try {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const jsonData = XLSX.utils.sheet_to_json(firstSheet);
        
        if (jsonData.length === 0) {
          errors.value = ['الملف لا يحتوي على بيانات'];
          previewData.value = [];
          return;
        }

        // Validate and transform data
        const validatedData = jsonData.map((row, index) => {
          // Check required fields
          if (!row.member_id || !row.subscription_status || !row.payment_date || !row.amount) {
            errors.value.push(`سطر ${index + 2}: جميع الحقول مطلوبة ما عدا الملاحظات`);
            return null;
          }

          // Validate subscription status
          if (!['paid', 'unpaid', 'overdue'].includes(row.subscription_status)) {
            errors.value.push(`سطر ${index + 2}: حالة الاشتراك غير صحيحة`);
            return null;
          }

          // Validate date format
          if (!isValidDate(row.payment_date)) {
            errors.value.push(`سطر ${index + 2}: تنسيق التاريخ غير صحيح`);
            return null;
          }

          // Validate amount is numeric
          if (isNaN(parseFloat(row.amount))) {
            errors.value.push(`سطر ${index + 2}: المبلغ يجب أن يكون رقماً`);
            return null;
          }

          return {
            member_id: row.member_id,
            subscription_status: row.subscription_status,
            payment_date: row.payment_date,
            amount: parseFloat(row.amount),
            payment_method: row.payment_method || 'cash',
            notes: row.notes || ''
          };
        }).filter(Boolean);

        previewData.value = validatedData;
      } catch (error) {
        console.error('Error parsing file:', error);
        errors.value = ['حدث خطأ أثناء قراءة الملف'];
        previewData.value = [];
      }
    };
    reader.onerror = () => {
      errors.value = ['حدث خطأ أثناء قراءة الملف'];
      previewData.value = [];
    };
    
    // Check if file is a File object
    if (fileToProcess instanceof File) {
      reader.readAsArrayBuffer(fileToProcess);
    } else {
      console.error('Invalid file object:', fileToProcess);
      errors.value = ['نوع الملف غير صالح'];
      previewData.value = [];
    }
  } catch (error) {
    console.error('Error reading file:', error);
    errors.value = ['حدث خطأ أثناء قراءة الملف'];
    previewData.value = [];
  }
};

const isValidDate = (dateString) => {
  const date = new Date(dateString);
  return date instanceof Date && !isNaN(date);
};

const uploadFile = async () => {
  if (!file.value) return;

  if (errors.value.length > 0) {
    alert('يرجى تصحيح الأخطاء قبل رفع الملف');
    return;
  }

  uploading.value = true;
  errors.value = [];

  try {
    const formData = new FormData();
    formData.append('file', file.value);

    const response = await axios.post('/subscriptions/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.data.success) {
      alert(response.data.message || 'تم استيراد البيانات بنجاح');
      file.value = null;
      previewData.value = [];
    }
  } catch (error) {
    console.error('Upload error:', error);
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      errors.value = ['حدث خطأ أثناء رفع الملف'];
    }
  } finally {
    uploading.value = false;
  }
};

const downloadTemplate = async () => {
  try {
    const response = await axios.get('/subscriptions/template', {
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'subscriptions_template.xlsx');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Template download error:', error);
    errors.value = ['حدث خطأ أثناء تحميل القالب'];
  }
};
</script>

<style scoped>
.v-data-table {
  direction: rtl;
}
</style> 