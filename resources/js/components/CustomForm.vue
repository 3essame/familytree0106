<template>
  <v-form ref="form" v-model="isFormValid" @submit.prevent="submitForm">
    <v-container>
      <v-row>
        <v-col cols="12" md="6">
          <v-autocomplete
            v-model="formData.category"
            :items="familyMembers"
            item-title="name"
            item-value="id"
            label="اختر الفرد"
            :rules="[rules.required]"
            variant="outlined"
            prepend-inner-icon="mdi-account"
            :loading="loadingMembers"
            :disabled="loadingMembers"
            return-object
            :hint="formData.category ? `الرقم التعريفي: ${formData.category.id}` : ''"
            persistent-hint
            clearable
            density="comfortable"
            :menu-props="{ maxHeight: 300 }"
            autocomplete="off"
            class="family-member-select"
            v-model:search="search"
            :filter="customFilter"
          >
            <template v-slot:selection="{ item }">
              <v-chip size="small" class="ma-1">
                {{ item.raw.name }}
              </v-chip>
            </template>
            <template v-slot:prepend>
              <v-progress-circular
                v-if="loadingMembers"
                indeterminate
                size="20"
                width="2"
                color="primary"
                class="me-2"
              ></v-progress-circular>
            </template>
            <template v-slot:no-data>
              <v-list-item>
                <v-list-item-title>
                  لا توجد نتائج
                </v-list-item-title>
              </v-list-item>
            </template>
          </v-autocomplete>
        </v-col>

        <v-col cols="12" md="6">
          <v-text-field
            label="اسم العائلة (مثال)"
            variant="outlined"
            density="comfortable"
          ></v-text-field>
        </v-col>

        <v-col cols="12" class="d-flex justify-end mt-4">
          <v-btn
            color="error"
            variant="outlined"
            class="me-4"
            @click="cancelForm"
          >
            إلغاء
          </v-btn>
          <v-btn
            color="primary"
            type="submit"
            :loading="isSubmitting"
            :disabled="!isFormValid || isSubmitting"
          >
            حفظ
          </v-btn>
        </v-col>
      </v-row>
    </v-container>
  </v-form>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

// المراجع
const form = ref(null);
const isFormValid = ref(false);
const isSubmitting = ref(false);
const loadingMembers = ref(true);
const familyMembers = ref<{id: number|string, name: string}[]>([]);
const search = ref('');

// قواعد التحقق من الصحة
const rules = {
  required: (v: any) => !!v || 'هذا الحقل مطلوب',
};

// بيانات النموذج
interface CategoryType {
  id: number|string;
  name: string;
}

const formData = reactive({
  category: null as CategoryType | null,
});

// دالة لجلب أسماء الأفراد من قاعدة البيانات
async function fetchFamilyMembers() {
  loadingMembers.value = true;
  familyMembers.value = []; // تفريغ المصفوفة قبل تحميل البيانات
  
  try {
    console.log('جاري تحميل بيانات الأفراد من قاعدة البيانات...');
    
    // محاولة الحصول على CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.log('CSRF Token:', csrfToken ? 'موجود' : 'غير موجود');
    
    // محاولة حل مشكلة المصادقة - تجربة استخدام مسارات API مختلفة
    const apiEndpoints = [
      '/api/family-tree',
      '/family-tree'
    ];
    
    let apiData: any[] = [];
    let responseReceived = false;
    
    for (const endpoint of apiEndpoints) {
      try {
        console.log(`محاولة الاتصال بـ: ${endpoint}`);
        
        const response = await axios.get(endpoint, {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken || ''
          },
          // إضافة معلمة لتجنب التخزين المؤقت
          params: {
            nocache: new Date().getTime()
          }
        });
        
        responseReceived = true;
        console.log(`استجابة من ${endpoint}:`, {
          status: response.status,
          contentType: response.headers['content-type']
        });
        
        // التحقق من استجابة JSON صالحة
        if (response.headers['content-type']?.includes('application/json')) {
          console.log('تم استلام JSON');
          
          // استخراج البيانات
          if (Array.isArray(response.data)) {
            apiData = response.data;
            console.log(`تم العثور على مصفوفة البيانات، عدد العناصر: ${apiData.length}`);
            break;
          } else if (response.data.data && Array.isArray(response.data.data)) {
            apiData = response.data.data;
            console.log(`تم العثور على البيانات في خاصية data، عدد العناصر: ${apiData.length}`);
            break;
          } else if (typeof response.data === 'object') {
            // البحث عن مصفوفة في خصائص الكائن
            for (const key in response.data) {
              const val = response.data[key];
              if (Array.isArray(val) && val.length > 0) {
                apiData = val;
                console.log(`تم العثور على البيانات في الخاصية ${key}، عدد العناصر: ${apiData.length}`);
                break;
              }
            }
            
            if (apiData.length > 0) break;
          }
        } else if (typeof response.data === 'string' && response.data.includes('<!DOCTYPE html>')) {
          console.error('تم استلام صفحة HTML بدلاً من JSON. قد تكون غير مسجل الدخول أو ليس لديك صلاحيات كافية.');
        }
      } catch (error) {
        console.error(`فشل الاتصال بـ ${endpoint}:`, error);
      }
    }
    
    if (!responseReceived) {
      throw new Error('فشل الاتصال بجميع نقاط النهاية المعروفة');
    }
    
    // معالجة البيانات المستلمة
    if (apiData.length > 0) {
      // تحويل البيانات إلى الصيغة المطلوبة للقائمة المنسدلة
      familyMembers.value = apiData
        .filter(item => item && typeof item === 'object' && (item.id || item.ID))
        .map(item => ({
          id: item.id || item.ID,
          name: item.name || item.full_name || item.title || `شخص ${item.id}`
        }))
        .sort((a, b) => a.name.localeCompare(b.name, 'ar'));
      
      console.log(`تم تحميل ${familyMembers.value.length} فرد من قاعدة البيانات`);
    } else {
      console.error('لم يتم العثور على بيانات من قاعدة البيانات');
      // إظهار رسالة للمستخدم بدلاً من استخدام بيانات افتراضية
      alert('لم يتم العثور على بيانات في قاعدة البيانات. يرجى التأكد من تسجيل الدخول وامتلاك الصلاحيات المناسبة.');
    }
    
  } catch (error) {
    console.error('خطأ في جلب بيانات الأفراد:', error);
    alert('حدث خطأ أثناء محاولة الاتصال بقاعدة البيانات. يرجى التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى.');
  } finally {
    loadingMembers.value = false;
  }
}

// دالة تصفية مخصصة لتحسين البحث
function customFilter(item: any, queryText: string, itemText: string): boolean {
  if (queryText.trim() === '') return true;
  
  const normalizedText = queryText.trim().toLowerCase();
  const normalizedItemText = itemText.toLowerCase();
  
  // البحث عن جزء من الاسم
  return normalizedItemText.includes(normalizedText);
}

// دالة تقديم النموذج
const emit = defineEmits(['submit', 'cancel']);

function submitForm() {
  isSubmitting.value = true;
  
  // محاكاة لتأخير عملية التقديم
  setTimeout(() => {
    emit('submit', { ...formData });
    isSubmitting.value = false;
  }, 500);
}

// دالة إلغاء النموذج
function cancelForm() {
  emit('cancel');
}

// جلب البيانات عند تحميل المكون
onMounted(() => {
  fetchFamilyMembers();
});
</script>

<style scoped>
.family-member-select :deep(.v-field__input) {
  padding-top: 6px;
  padding-bottom: 6px;
}

.family-member-select :deep(.v-field__field) {
  min-height: 56px;
}

.family-member-select :deep(.v-list-item) {
  min-height: 44px;
  padding: 8px 16px;
}
</style> 