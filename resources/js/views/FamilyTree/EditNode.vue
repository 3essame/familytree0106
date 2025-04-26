<template>
  <v-dialog v-model="localShow" max-width="600" persistent>
    <v-card rounded="lg" elevation="5">
      <v-toolbar :color="isEdit ? 'primary' : 'success'" dark flat>
        <v-icon class="me-2">{{ isEdit ? 'mdi-account-edit' : 'mdi-account-plus' }}</v-icon>
        <v-toolbar-title>{{ isEdit ? 'تعديل فرد' : 'إضافة فرد جديد' }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>

      <v-card-text class="pt-4">
        <v-form ref="formRef" @submit.prevent="saveNode">
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="form.name"
                label="الاسم الكامل"
                :rules="[v => !!v || 'الاسم مطلوب']"
                prepend-icon="mdi-account"
                variant="outlined"
                required
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.gender"
                :items="genderOptions"
                item-title="text"
                item-value="value"
                label="الجنس"
                prepend-icon="mdi-gender-male-female"
                variant="outlined"
                :rules="[v => !!v || 'الجنس مطلوب']"
                required
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.relation"
                label="صلة القرابة"
                hint="مثال: أب، أم، ابن..."
                prepend-icon="mdi-account-group"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.birth_date"
                label="تاريخ الميلاد"
                type="date"
                prepend-icon="mdi-calendar"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.death_date"
                label="تاريخ الوفاة"
                type="date"
                prepend-icon="mdi-calendar-remove"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.father_id"
                :items="familyMembers"
                item-title="name"
                item-value="id"
                label="الأب"
                prepend-icon="mdi-account-child"
                variant="outlined"
                :hint="props.parentId ? 'سيتم إضافة هذا الفرد كابن للأب المحدد' : 'اختر الأب أو اتركه فارغاً ليكون في الجذر'"
                persistent-hint
                clearable
                :disabled="!!props.parentId"
                :no-data-text="'لا يوجد آباء متاحين. أضف أفرادًا ذكورًا أولاً.'"
                return-object
              >
                <template v-slot:selection="{ item }">
                  {{ item.raw.name }}
                </template>
                <template v-slot:item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps" :title="item.raw.name" :subtitle="item.raw.birth_date ? 'مواليد: ' + formatDate(item.raw.birth_date) : ''"></v-list-item>
                </template>
              </v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.spouse_id"
                label="معرّف الزوج/الزوجة (اختياري)"
                type="number"
                prepend-icon="mdi-account-heart"
                variant="outlined"
                hint="أدخل رقم معرف الزوج أو الزوجة"
                persistent-hint
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.notes"
                label="ملاحظات إضافية"
                rows="2"
                prepend-icon="mdi-note-text"
                variant="outlined"
                auto-grow
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.info"
                label="معلومات إضافية (JSON)"
                rows="2"
                prepend-icon="mdi-code-json"
                variant="outlined"
                hint="أدخل بيانات بصيغة JSON"
                persistent-hint
                auto-grow
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn color="grey" variant="outlined" prepend-icon="mdi-close" @click="close" class="me-2">إلغاء</v-btn>
        <v-btn color="primary" variant="elevated" prepend-icon="mdi-content-save" :loading="loading" :disabled="loading" @click="saveNode">حفظ</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: Boolean,
  node: Object,
  parentId: [Number, null],
});
const emit = defineEmits(['update:show', 'saved']);

const localShow = ref(props.show);
const isEdit = ref(!!props.node && !!props.node.id);

console.log('EditNode initialized with:', {
  show: props.show,
  node: props.node,
  parentId: props.parentId,
  isEdit: isEdit.value
});

const form = ref({
  name: props.node?.name || '',
  relation: props.node?.relation || '',
  birth_date: props.node?.birth_date || '',
  death_date: props.node?.death_date || '',
  gender: props.node?.gender || '',
  father_id: props.parentId || props.node?.father_id || '',
  spouse_id: props.node?.spouse_id || '',
  notes: props.node?.notes || '',
  info: props.node?.info ? JSON.stringify(props.node.info) : '',
});

// قائمة أفراد العائلة لاختيار الأب
const familyMembers = ref([]);
const genderOptions = [
  { text: 'ذكر', value: 'male' },
  { text: 'أنثى', value: 'female' }
];
const loading = ref(false);
const formRef = ref(null);

// دالة لتنسيق التاريخ
function formatDate(dateString) {
  if (!dateString) return '';

  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // إذا كان التاريخ غير صالح

    // تنسيق التاريخ بالعربية (ميلادي)
    const options = { year: 'numeric', month: 'long', day: 'numeric', calendar: 'gregory' };
    return new Intl.DateTimeFormat('ar', options).format(date);
  } catch (error) {
    console.error('خطأ في تنسيق التاريخ:', error);
    return dateString;
  }
};

// Watch for changes in the show prop and update localShow
watch(() => props.show, (val) => {
  localShow.value = val;
});

// Watch for changes in localShow and emit update event
watch(localShow, (val) => {
  emit('update:show', val);
});

// دالة لجلب أفراد العائلة
async function loadFamilyMembers() {
  try {
    // إضافة رسالة توضيحية للتشخيص
    console.log('Loading family members...');

    const response = await axios.get('/family-tree');
    console.log('Family members API response:', response);
    console.log('Family members data:', response.data);

    // التأكد من أن البيانات هي مصفوفة
    let members = [];
    if (response.data && typeof response.data === 'object') {
      if (Array.isArray(response.data)) {
        members = response.data;
      } else if (response.data.data && Array.isArray(response.data.data)) {
        members = response.data.data;
      } else {
        members = Object.values(response.data).filter(item => typeof item === 'object');
      }
    }

    console.log('Extracted members array:', members);

    // تصفية القائمة للذكور فقط لاختيار الأب
    // والتأكد من أن الأب لديه تاريخ ميلاد
    const maleMembers = members.filter(member => {
      // التأكد من أن العضو ذكر
      return member && member.gender === 'male';
    });

    console.log('Filtered male members:', maleMembers);

    // إذا كان هناك تحرير لفرد، استبعد الفرد نفسه من قائمة الآباء
    if (isEdit.value && props.node?.id) {
      familyMembers.value = maleMembers.filter(member => member.id !== props.node.id);
    } else {
      familyMembers.value = maleMembers;
    }

    console.log('Final family members list for dropdown:', familyMembers.value);

    // إذا لم تكن هناك بيانات، أضف رسالة توضيحية
    if (familyMembers.value.length === 0) {
      console.warn('No male family members found for father selection');
    }
  } catch (error) {
    console.error('Error loading family members:', error);
  }
}

// Watch for dialog opening and update form data
watch(() => props.show, async (val) => {
  if (val) {
    // جلب أفراد العائلة عند فتح النموذج
    await loadFamilyMembers();

    if (props.node) {
      form.value = {
        name: props.node.name || '',
        relation: props.node.relation || '',
        birth_date: props.node.birth_date || '',
        death_date: props.node.death_date || '',
        gender: props.node.gender || '',
        father_id: props.node.father_id || '',
        spouse_id: props.node.spouse_id || '',
        notes: props.node.notes || '',
        info: props.node.info ? JSON.stringify(props.node.info) : '',
      };
    } else {
      // إذا كان هناك parentId محدد، استخدمه كأب
      if (props.parentId) {
        form.value.father_id = props.parentId;
      }
    }
  }
});

function close() {
  localShow.value = false;
}

// دالة للتحقق من أن الأب أكبر من الابن بعشر سنوات على الأقل
function validateFatherAge(fatherId, childBirthDate) {
  // إذا لم يكن هناك أب أو تاريخ ميلاد للابن، فلا حاجة للتحقق
  if (!fatherId || !childBirthDate) return true;

  // التعامل مع fatherId سواء كان كائنًا أو رقمًا
  let father;
  if (typeof fatherId === 'object' && fatherId !== null) {
    // إذا كان fatherId هو الكائن نفسه
    father = fatherId;
  } else {
    // البحث عن الأب في قائمة أفراد العائلة
    father = familyMembers.value.find(member => member.id == fatherId);
  }

  // إذا لم يتم العثور على الأب أو لم يكن لديه تاريخ ميلاد
  if (!father || !father.birth_date) return true;

  // حساب الفرق بين تاريخ ميلاد الأب والابن بالسنوات
  const fatherBirthDate = new Date(father.birth_date);
  const childBirthDateObj = new Date(childBirthDate);

  // التحقق من صحة التواريخ
  if (isNaN(fatherBirthDate.getTime()) || isNaN(childBirthDateObj.getTime())) return true;

  // حساب الفرق بالسنوات
  const yearDiff = childBirthDateObj.getFullYear() - fatherBirthDate.getFullYear();

  // التحقق من أن الأب أكبر من الابن بعشر سنوات على الأقل
  return yearDiff >= 10;
}

async function saveNode() {
  loading.value = true;
  try {
    // التحقق من عمر الأب
    // التعامل مع father_id سواء كان كائنًا أو رقمًا
    let fatherId = props.parentId || null;
    if (!fatherId && form.value.father_id) {
      // إذا كان father_id كائنًا (من return-object)
      if (typeof form.value.father_id === 'object' && form.value.father_id !== null) {
        fatherId = form.value.father_id.id;
      } else {
        // إذا كان رقمًا أو نصًا
        fatherId = form.value.father_id;
      }
    }

    // التحقق من أن الأب ليس هو نفس الابن (لمنع الحلقات المغلقة)
    if (isEdit.value && props.node?.id && fatherId == props.node.id) {
      alert('لا يمكن أن يكون الشخص أباً لنفسه');
      loading.value = false;
      return;
    }

    // التحقق من عمر الأب مقارنة بالابن
    if (fatherId && form.value.birth_date) {
      const isValidAge = validateFatherAge(fatherId, form.value.birth_date);
      if (!isValidAge) {
        alert('يجب أن يكون الأب أكبر من الابن بعشر سنوات على الأقل');
        loading.value = false;
        return;
      }
    }

    const payload = {
      name: form.value.name,
      relation: form.value.relation,
      birth_date: form.value.birth_date || null,
      death_date: form.value.death_date || null,
      gender: form.value.gender || null,
      spouse_id: form.value.spouse_id || null,
      notes: form.value.notes || '',
      info: form.value.info ? JSON.parse(form.value.info) : {},
      father_id: fatherId,
    };

    console.log('Final payload with father_id:', payload);

    console.log('Using father_id:', payload.father_id, 'from props.parentId:', props.parentId, 'or form.father_id:', form.value.father_id);

    console.log('Saving node with payload:', payload);

    let response;
    if (isEdit.value && props.node?.id) {
      console.log('Updating existing node with ID:', props.node.id);
      response = await axios.put(`/family-tree/${props.node.id}`, payload);
    } else {
      console.log('Creating new node');
      response = await axios.post('/family-tree', payload);
    }

    console.log('API response:', response.data);
    emit('saved', response.data);
    close();
  } catch (e) {
    console.error('Error saving node:', e);
    // يمكن إضافة معالجة الأخطاء هنا
  } finally {
    loading.value = false;
  }
}
</script>