<template>
  <div class="tree-root-manager">
    <v-card elevation="2" class="mb-4">
      <v-card-title class="text-h6 bg-primary text-white">
        <v-icon class="me-2">mdi-family-tree</v-icon>
        إدارة جذر الشجرة
      </v-card-title>
      
      <v-card-text class="pa-4">
        <v-alert
          v-if="rootNodes.length > 1"
          type="warning"
          variant="tonal"
          icon="mdi-alert"
          class="mb-4"
        >
          يوجد {{ rootNodes.length }} جذور في الشجرة. يجب تعيين جذر رئيسي واحد.
        </v-alert>
        
        <v-alert
          v-if="rootNodes.length === 0"
          type="info"
          variant="tonal"
          icon="mdi-information"
          class="mb-4"
        >
          لا يوجد جذور في الشجرة حالياً.
        </v-alert>
        
        <v-alert
          v-if="message"
          :type="messageType"
          variant="tonal"
          class="mb-4"
        >
          {{ message }}
        </v-alert>
        
        <v-row v-if="rootNodes.length > 0">
          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="selectedRoot"
              :items="rootNodes"
              item-title="name"
              item-value="id"
              label="اختر الجذر الرئيسي"
              variant="outlined"
              :loading="loading"
              :disabled="isProcessing"
              clearable
              density="comfortable"
              :menu-props="{ maxHeight: 300 }"
              return-object
              eager
              v-model:search="rootSearch"
              :filter="customFilter"
            >
              <template v-slot:no-data>
                <div class="pa-2 text-center">لا توجد بيانات</div>
              </template>
            </v-autocomplete>
          </v-col>
          
          <v-col cols="12" md="6" class="d-flex align-center">
            <v-btn
              color="primary"
              prepend-icon="mdi-link"
              :loading="isProcessing"
              :disabled="!selectedRoot || isProcessing"
              @click="linkAllRoots"
            >
              ربط جميع الجذور
            </v-btn>
          </v-col>
        </v-row>
        
        <v-dialog v-model="showLinkDialog" max-width="500">
          <v-card>
            <v-card-title class="text-h6 bg-primary text-white">
              ربط فرد بأب
            </v-card-title>
            
            <v-card-text class="pa-4">
              <v-autocomplete
                v-model="selectedFather"
                :items="potentialFathers"
                item-title="name"
                item-value="id"
                label="اختر الأب"
                variant="outlined"
                :loading="isLinking"
                :disabled="isLinking"
                clearable
                density="comfortable"
                :filter="customFilter"
                autocomplete="off"
                :menu-props="{ maxHeight: 300 }"
                eager
                v-model:search="fatherSearch"
              >
                <template v-slot:no-data>
                  <div class="pa-2 text-center">لا توجد بيانات</div>
                </template>
              </v-autocomplete>
            </v-card-text>
            
            <v-card-actions class="pa-4">
              <v-spacer />
              <v-btn
                color="grey"
                variant="outlined"
                @click="showLinkDialog = false"
                :disabled="isLinking"
              >
                إلغاء
              </v-btn>
              <v-btn
                color="primary"
                :loading="isLinking"
                :disabled="!selectedFather || isLinking"
                @click="linkToFather"
              >
                ربط
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </v-card-text>
    </v-card>

    <v-card v-if="otherNodes.length > 0">
      <v-card-title>الأفراد بدون أب</v-card-title>
      <v-card-text>
        <p class="text-body-1 mb-4">
          يمكنك ربط الأفراد التالية بالجذر الرئيسي أو بأي فرد آخر في الشجرة:
        </p>

        <v-table>
          <thead>
            <tr>
              <th>الاسم</th>
              <th>الجنس</th>
              <th>تاريخ الميلاد</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="node in otherNodes" :key="node.id">
              <td>{{ node.name }}</td>
              <td>
                <v-chip
                  :color="node.gender === 'male' ? 'blue' : 'pink'"
                  text-color="white"
                  size="small"
                >
                  {{ node.gender === 'male' ? 'ذكر' : 'أنثى' }}
                </v-chip>
              </td>
              <td>{{ formatDate(node.birth_date) }}</td>
              <td>
                <v-btn
                  size="small"
                  color="primary"
                  variant="text"
                  @click="openLinkDialog(node)"
                >
                  ربط بأب
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import type { TreeNode } from '../../types/family-tree';

// الحالة
const allNodes = ref<TreeNode[]>([]);
const hasMultipleRoots = ref(false);
const showLinkDialog = ref(false);
const selectedRoot = ref<TreeNode | null>(null);
const isProcessing = ref(false);
const message = ref('');
const messageType = ref('info');
const selectedFather = ref<number | null>(null);
const isLinking = ref(false);
const loading = ref(false);
const error = ref<string | null>(null);
const rootSearch = ref('');
const fatherSearch = ref('');

// الجذور (الأفراد بدون أب)
const rootNodes = computed(() => {
  return allNodes.value.filter(node => !node.father_id);
});

// الأفراد الآخرين بدون أب (باستثناء الجذر الرئيسي)
const otherNodes = computed(() => {
  if (selectedRoot.value) {
    return rootNodes.value.filter(node => node.id !== selectedRoot.value?.id);
  }
  return [];
});

// الآباء المحتملين (جميع الذكور)
const potentialFathers = computed(() => {
  return allNodes.value.filter(node => 
    node.gender === 'male' && 
    (!selectedRoot.value || node.id !== selectedRoot.value.id)
  );
});

// دالة للتحقق من حالة البيانات
function checkDataState() {
  console.log('Current root manager state:', {
    rootNodes: rootNodes.value,
    hasMultipleRoots: hasMultipleRoots.value,
    showLinkDialog: showLinkDialog.value,
    selectedRoot: selectedRoot.value,
    loading: loading.value,
    error: error.value
  });
}

// تحديث حالة البيانات عند التغيير
watch(rootNodes, (newValue) => {
  console.log('Root nodes changed:', {
    length: newValue?.length,
    firstItem: newValue?.[0],
    lastItem: newValue?.[newValue.length - 1]
  });
  checkDataState();
}, { deep: true });

watch(hasMultipleRoots, (newValue) => {
  console.log('Multiple roots state changed:', newValue);
  checkDataState();
});

watch(showLinkDialog, (newValue) => {
  console.log('Link dialog visibility changed:', newValue);
  checkDataState();
});

watch(selectedRoot, (newValue) => {
  console.log('Selected root changed:', newValue);
  checkDataState();
});

// دالة تحميل بيانات الشجرة
const loadTreeData = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get('/family-tree');
    
    if (response.data && Array.isArray(response.data)) {
      // فرز البيانات أبجديًا لتسهيل البحث
      allNodes.value = response.data.sort((a: TreeNode, b: TreeNode) => 
        a.name.localeCompare(b.name, 'ar')
      );
      
      // حساب عدد الجذور
      const roots = allNodes.value.filter(node => !node.father_id);
      hasMultipleRoots.value = roots.length > 1;
      
      // إذا كان هناك جذر واحد فقط، يتم تحديده تلقائياً
      if (roots.length === 1) {
        selectedRoot.value = roots[0];
      }
    } else {
      allNodes.value = [];
      error.value = 'تنسيق بيانات غير صالح من الخادم';
    }
    
    // تلقائيًا تحديد الجذر الرئيسي إذا كان واحدًا فقط
    if (rootNodes.value.length === 1) {
      selectedRoot.value = rootNodes.value[0];
    }
    
  } catch (err) {
    console.error('Error loading tree data:', err);
    error.value = 'حدث خطأ أثناء تحميل بيانات الشجرة';
    allNodes.value = [];
  } finally {
    loading.value = false;
  }
};

// دالة البحث المخصصة
function customFilter(item: any, queryText: string, itemText: string): boolean {
  if (queryText.trim() === '') return true;
  
  const normalizedText = queryText.trim().toLowerCase();
  const normalizedItemText = itemText.toLowerCase();
  
  return normalizedItemText.includes(normalizedText);
}

// التحقق من صلاحية الجذر
const validateRoot = (node: TreeNode): boolean => {
  return !node.father_id && node.gender === 'male';
};

// دالة التحقق من وجود جذر صالح
const ensureValidRoot = () => {
  if (!selectedRoot.value) {
    message.value = 'يرجى اختيار الجذر الرئيسي';
    messageType.value = 'warning';
    return false;
  }
  
  if (!validateRoot(selectedRoot.value)) {
    message.value = 'يجب أن يكون الجذر الرئيسي ذكراً وليس لديه أب';
    messageType.value = 'error';
    return false;
  }
  
  return true;
};

// تعيين الجذر الرئيسي
const setMainRoot = async () => {
  if (!selectedRoot.value) {
    message.value = 'يرجى اختيار الجذر الرئيسي أولاً';
    messageType.value = 'warning';
    return;
  }
  
  isProcessing.value = true;
  message.value = 'جاري تعيين الجذر الرئيسي...';
  messageType.value = 'info';
  
  try {
    // التحقق من عدم وجود أب للجذر المحدد
    if (selectedRoot.value.father_id) {
      throw new Error('لا يمكن تعيين هذا الفرد كجذر لأنه مرتبط بأب');
    }
    
    // ربط جميع الجذور الأخرى بالجذر الرئيسي
    const updatePromises = otherNodes.value.map(async (node) => {
      try {
        await axios.put(`/family-tree/${node.id}`, {
          ...node,
          father_id: selectedRoot.value?.id
        });
      } catch (err) {
        console.error(`Error updating node ${node.id}:`, err);
        throw err;
      }
    });
    
    await Promise.all(updatePromises);
    
    message.value = 'تم تعيين الجذر الرئيسي وربط باقي الأفراد به بنجاح.';
    messageType.value = 'success';
    
    // إعادة تحميل البيانات
    await loadTreeData();
  } catch (error) {
    console.error('Error setting main root:', error);
    message.value = 'حدث خطأ أثناء تعيين الجذر الرئيسي.';
    messageType.value = 'error';
  } finally {
    isProcessing.value = false;
  }
};

// ربط جميع الجذور الأخرى بالجذر الرئيسي
const linkAllRoots = async () => {
  if (!ensureValidRoot()) return;
  
  isProcessing.value = true;
  message.value = 'جاري ربط الجذور...';
  messageType.value = 'info';
  
  try {
    // التحقق من الجذور الأخرى
    const invalidNodes = otherNodes.value.filter(node => node.father_id);
    if (invalidNodes.length > 0) {
      throw new Error('بعض الأفراد مرتبطين بالفعل بآباء');
    }
    
    // ربط جميع الجذور الأخرى بالجذر الرئيسي
    const updatePromises = otherNodes.value.map(async (node) => {
      try {
        await axios.put(`/family-tree/${node.id}`, {
          ...node,
          father_id: selectedRoot.value?.id
        });
      } catch (err) {
        console.error(`Error updating node ${node.id}:`, err);
        throw new Error(`فشل في ربط الفرد ${node.name}`);
      }
    });
    
    await Promise.all(updatePromises);
    
    message.value = 'تم ربط جميع الجذور بالجذر الرئيسي بنجاح.';
    messageType.value = 'success';
    
    // إعادة تحميل البيانات
    await loadTreeData();
  } catch (error) {
    console.error('Error linking roots:', error);
    message.value = 'حدث خطأ أثناء ربط الجذور.';
    messageType.value = 'error';
  } finally {
    isProcessing.value = false;
  }
};

// فتح حوار ربط الفرد بأب
const openLinkDialog = (node: TreeNode) => {
  selectedRoot.value = node;
  selectedFather.value = null;
  showLinkDialog.value = true;
};

// ربط الفرد بأب
const linkToFather = async () => {
  if (!selectedRoot.value || !selectedFather.value) return;
  
  isLinking.value = true;
  
  try {
    await axios.put(`/family-tree/${selectedRoot.value.id}`, {
      ...selectedRoot.value,
      father_id: selectedFather.value
    });
    
    showLinkDialog.value = false;
    message.value = `تم ربط ${selectedRoot.value.name} بالأب المحدد بنجاح.`;
    messageType.value = 'success';
    
    // إعادة تحميل البيانات
    await loadTreeData();
  } catch (error) {
    console.error('Error linking to father:', error);
    message.value = 'حدث خطأ أثناء ربط الفرد بالأب.';
    messageType.value = 'error';
  } finally {
    isLinking.value = false;
  }
};

// تحديث الحالة بعد كل عملية
const updateState = async () => {
  await loadTreeData();
  isProcessing.value = false;
  checkDataState();
};

// تنسيق التاريخ
const formatDate = (dateString: string | null | undefined): string => {
  if (!dateString) return '-';
  
  try {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ar-SA', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(date);
  } catch (e) {
    console.error('Error formatting date:', e);
    return dateString || '-';
  }
};

// تحميل البيانات عند تهيئة المكون
onMounted(() => {
  loadTreeData();
});
</script>

<style scoped>
.tree-root-manager {
  max-width: 100%;
}

:deep(.v-autocomplete .v-field__input) {
  padding-top: 6px;
  padding-bottom: 6px;
}

:deep(.v-autocomplete .v-field__field) {
  min-height: 56px;
}

:deep(.v-list-item) {
  min-height: 44px;
  padding: 8px 16px;
}
</style>
