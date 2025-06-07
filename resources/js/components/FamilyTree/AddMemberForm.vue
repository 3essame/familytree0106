<template>
  <v-dialog
    v-model="dialog"
    max-width="800px"
    persistent
  >
    <v-card>
      <v-card-title class="text-h5 bg-primary text-white">
        <v-icon class="me-2">mdi-account-plus</v-icon>
        إضافة فرد جديد إلى شجرة العائلة
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" color="white" @click="cancelForm"></v-btn>
      </v-card-title>
      
      <v-card-text class="pa-4">
        <v-form ref="form" v-model="isFormValid" @submit.prevent="submitForm">
          <v-container>
            <v-row>
              <!-- Father Selection -->
              <v-col cols="12">
                <v-autocomplete
                  v-model="formData.father_id"
                  :items="familyMembers"
                  item-title="name"
                  item-value="id"
                  label="اختر الأب"
                  variant="outlined"
                  prepend-inner-icon="mdi-account-multiple"
                  :loading="loadingMembers"
                  :disabled="loadingMembers"
                  clearable
                  density="comfortable"
                  :menu-props="{ maxHeight: 300 }"
                  autocomplete="off"
                  v-model:search="fatherSearch"
                  :filter="customFilter"
                  class="member-select"
                  :hint="getFatherHint"
                  persistent-hint
                >
                  <template v-slot:no-data>
                    <v-list-item>
                      <v-list-item-title>
                        لا توجد نتائج
                      </v-list-item-title>
                    </v-list-item>
                  </template>
                </v-autocomplete>
              </v-col>
              
              <!-- Basic Information -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="الاسم"
                  :rules="[rules.required]"
                  variant="outlined"
                  prepend-inner-icon="mdi-account"
                  clearable
                ></v-text-field>
              </v-col>


              <v-col cols="12" md="6">
                          <v-text-field
                            v-model="formData.birth_date"
                            label="تاريخ الميلاد (اختياري)"
                            variant="outlined"
                            prepend-inner-icon="mdi-calendar"
                            clearable
                            type="date"
                          ></v-text-field>
                        </v-col>



            
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.gender"
                  :items="genderOptions"
                  item-title="text"
                  item-value="value"
                  label="الجنس"
                  :rules="[rules.required]"
                  variant="outlined"
                  prepend-inner-icon="mdi-gender-male-female"
                ></v-select>
              </v-col>
              
              <!-- Optional Information -->
              <v-col cols="12">
                <v-expansion-panels variant="accordion">
                  <v-expansion-panel title="معلومات إضافية">
                    <v-expansion-panel-text>
                      <v-row>
                        <v-col cols="12" md="6">
                          <v-autocomplete
                            v-model="formData.mother_id"
                            :items="potentialMothers"
                            item-title="name"
                            item-value="id"
                            label="اختر الأم (اختياري)"
                            variant="outlined"
                            prepend-inner-icon="mdi-human-female"
                            clearable
                            :menu-props="{ maxHeight: 300 }"
                            autocomplete="off"
                            :filter="customFilter"
                            class="member-select"
                          ></v-autocomplete>
                        </v-col>
                        
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="formData.relation"
                            label="صلة القرابة (اختياري)"
                            variant="outlined"
                            prepend-inner-icon="mdi-account-group"
                            clearable
                          ></v-text-field>
                        </v-col>
                        
                       
                        
                        <v-col cols="12">
                          <v-textarea
                            v-model="formData.notes"
                            label="ملاحظات (اختياري)"
                            variant="outlined"
                            prepend-inner-icon="mdi-note-text"
                            auto-grow
                            rows="3"
                            clearable
                          ></v-textarea>
                        </v-col>
                      </v-row>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-col>
            </v-row>
          </v-container>
        </v-form>
      </v-card-text>
      
      <v-card-actions class="pa-4 pt-0">
        <v-spacer></v-spacer>
        <v-btn
          color="error"
          variant="outlined"
          class="me-4"
          @click="cancelForm"
          :disabled="isSubmitting"
        >
          إلغاء
        </v-btn>
        <v-btn
          color="primary"
          @click="submitForm"
          :loading="isSubmitting"
          :disabled="!isFormValid || isSubmitting"
        >
          إضافة الفرد
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, defineProps, defineEmits, watch } from 'vue';
import axios from 'axios';
import type { ComponentPublicInstance } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  preSelectedFatherId: {
    type: [Number, String, null],
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'submit', 'cancel']);

// Dialog visibility computed property
const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Update form ref with proper type
interface FormRef extends ComponentPublicInstance {
  resetValidation: () => void;
}

// Update refs with proper types
const form = ref<FormRef | null>(null);
const isFormValid = ref(false);
const isSubmitting = ref(false);
const loadingMembers = ref(true);
const familyMembers = ref<{id: number|string, name: string}[]>([]);
const fatherSearch = ref('');

// خيارات الجنس
const genderOptions = [
  { text: 'ذكر', value: 'male' },
  { text: 'أنثى', value: 'female' }
];

// قواعد التحقق من الصحة
const rules = {
  required: (v: any) => !!v || 'هذا الحقل مطلوب',
};

// نموذج البيانات
interface MemberData {
  name: string;
  gender: 'male' | 'female' | '';
  father_id: number | string | null;
  mother_id: number | string | null;
  relation: string;
  notes: string;
  birth_date: string;
}

const formData = reactive<MemberData>({
  name: '',
  gender: '',
  father_id: null,
  mother_id: null,
  relation: '',
  notes: '',
  birth_date: ''
});

// Watch for changes in preSelectedFatherId prop
watch(() => props.preSelectedFatherId, (newValue) => {
  if (newValue) {
    formData.father_id = newValue;
  }
}, { immediate: true });

// الإشارات المحسوبة
const potentialMothers = computed(() => {
  return familyMembers.value.filter(member => {
    // الفلترة للحصول على الإناث فقط
    const memberData = familyMembers.value.find(m => m.id === member.id);
    if (!memberData) return false;
    
    // تحقق مما إذا كانت البيانات تحتوي على معلومات الجنس
    // قد تحتاج إلى تعديل هذا بناءً على هيكل البيانات الفعلي
    return (memberData as any).gender === 'female';
  });
});

// الحصول على معلومات تلميحية عن الأب
const getFatherHint = computed(() => {
  if (!formData.father_id) return '';
  
  const selectedFather = familyMembers.value.find(m => m.id === formData.father_id);
  if (!selectedFather) return '';
  
  return `الأب المختار: ${selectedFather.name}`;
});

// وظائف
// دالة لجلب أسماء أفراد العائلة
async function fetchFamilyMembers() {
  loadingMembers.value = true;
  try {
    console.log('جاري تحميل بيانات أفراد العائلة...');
    
    // محاولة الحصول على CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // محاولة الاتصال بواجهة API
    const endpoints = [
      '/api/family-tree',
      '/family-tree'
    ];
    
    let data = null;
    
    for (const endpoint of endpoints) {
      try {
        const response = await axios.get(endpoint, {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken || ''
          }
        });
        
        // التحقق من صحة الاستجابة
        if (response.headers['content-type']?.includes('application/json')) {
          data = response.data;
          console.log(`تم العثور على بيانات في ${endpoint}`);
          break;
        }
      } catch (err) {
        console.error(`فشل الاتصال بـ ${endpoint}:`, err);
      }
    }
    
    // معالجة البيانات المستلمة
    let apiData: any[] = [];
    
    if (data) {
      if (Array.isArray(data)) {
        apiData = data;
      } else if (data.data && Array.isArray(data.data)) {
        apiData = data.data;
      } else if (typeof data === 'object') {
        for (const key in data) {
          if (Array.isArray(data[key]) && data[key].length > 0) {
            apiData = data[key];
            break;
          }
        }
      }
    }
    
    // تحويل البيانات إلى التنسيق المطلوب
    familyMembers.value = apiData
      .filter(item => item && typeof item === 'object' && (item.id || item.ID))
      .map(item => ({
        id: item.id || item.ID,
        name: item.name || item.full_name || item.title || `فرد ${item.id}`,
        gender: item.gender || 'male' // افتراضيًا ذكر إذا لم يتم تحديد الجنس
      }))
      .sort((a, b) => a.name.localeCompare(b.name, 'ar'));
    
    console.log(`تم تحميل ${familyMembers.value.length} فرد من قاعدة البيانات`);
    
  } catch (error) {
    console.error('خطأ في جلب بيانات أفراد العائلة:', error);
  } finally {
    loadingMembers.value = false;
  }
}

// دالة تصفية مخصصة لتحسين البحث
function customFilter(item: any, queryText: string, itemText: string): boolean {
  if (queryText.trim() === '') return true;
  
  const normalizedText = queryText.trim().toLowerCase();
  const normalizedItemText = itemText.toLowerCase();
  
  return normalizedItemText.includes(normalizedText);
}

// دالة تقديم النموذج
async function submitForm() {
  isSubmitting.value = true;
  
  try {
    // الحصول على CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // تحضير البيانات للإرسال
    const memberData = { ...formData };
    
    // طباعة البيانات التي سيتم إرسالها للتشخيص
    console.log('البيانات التي سيتم إرسالها:', memberData);
    
    // المسار الصحيح للـ API (تأكد من عدم تكرار /api في المسار)
    // استخدم المسار المطلق بدلاً من المسار النسبي لتجنب أي مشاكل
    let apiUrl = window.location.origin + '/api/family-tree';
    
    // إضافة رؤوس مخصصة للطلب
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken || ''
    };
    
    console.log('محاولة إرسال البيانات إلى', apiUrl);
    console.log('الرؤوس:', headers);
    
    // إرسال الطلب مع رؤوس محسّنة
    const response = await axios({
      method: 'post',
      url: apiUrl,
      data: memberData,
      headers: headers,
      withCredentials: true  // للسماح بإرسال ملفات تعريف الارتباط في الطلب
    });
    
    console.log('تمت إضافة الفرد بنجاح:', response.data);
    emit('submit', response.data);
    
    // إعادة تعيين النموذج
    resetForm();
    
    // إغلاق النافذة المنبثقة
    dialog.value = false;
  } catch (error: any) {
    console.error('خطأ في إضافة الفرد:', error);
    
    // عرض تفاصيل أكثر عن الخطأ
    if (error.response) {
      // الاستجابة موجودة، لكن مع رمز حالة ليس في نطاق 2xx
      console.error('رمز الحالة:', error.response.status);
      console.error('البيانات:', error.response.data);
      console.error('الرؤوس:', error.response.headers);
      
      let errorMessage = 'حدث خطأ أثناء محاولة إضافة الفرد: ';
      if (error.response.data && error.response.data.error) {
        errorMessage += error.response.data.error;
      } else {
        errorMessage += `رمز الخطأ: ${error.response.status}`;
      }
      
      alert(errorMessage);
    } else if (error.request) {
      // تم إرسال الطلب لكن لم يتم استلام أي استجابة
      console.error('لم يتم استلام رد من الخادم');
      alert('لم يتم استلام رد من الخادم. تحقق من اتصالك بالإنترنت أو حاول مرة أخرى لاحقًا.');
    } else {
      // حدث خطأ أثناء إعداد الطلب
      console.error('خطأ في إعداد الطلب:', error.message);
      alert('حدث خطأ أثناء إعداد الطلب: ' + error.message);
    }
  } finally {
    isSubmitting.value = false;
  }
}

// إعادة تعيين النموذج
function resetForm() {
  Object.assign(formData, {
    name: '',
    gender: '',
    father_id: props.preSelectedFatherId,
    mother_id: null,
    relation: '',
    notes: '',
    birth_date: ''
  });
  
  // Skip the validation reset as it's causing issues
  // Use a timeout to allow Vue to update the form model
  setTimeout(() => {
    isFormValid.value = false;
  }, 0);
}

// دالة إلغاء النموذج
function cancelForm() {
  resetForm();
  emit('cancel');
  dialog.value = false;
}

// تحميل البيانات عند تهيئة المكون
onMounted(() => {
  fetchFamilyMembers();
});

// Watch for dialog opening to refresh data
watch(() => dialog.value, (newValue) => {
  if (newValue) {
    // Refresh family members data when dialog opens
    fetchFamilyMembers();
    // Set the preselected father ID if provided
    if (props.preSelectedFatherId) {
      formData.father_id = props.preSelectedFatherId;
    }
  }
});
</script>

<style scoped>
.member-select :deep(.v-field__input) {
  padding-top: 6px;
  padding-bottom: 6px;
}

.member-select :deep(.v-field__field) {
  min-height: 56px;
}

.member-select :deep(.v-list-item) {
  min-height: 44px;
  padding: 8px 16px;
}

.v-card-title {
  border-radius: 4px 4px 0 0;
}
</style> 