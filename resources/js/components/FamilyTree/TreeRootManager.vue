<template>
  <div class="tree-root-manager">
    <v-card class="mb-4">
      <v-card-title class="d-flex align-center">
        <v-icon color="primary" class="me-2">mdi-family-tree</v-icon>
        <span>إدارة جذر شجرة العائلة</span>
      </v-card-title>
      <v-card-text>
        <p class="text-body-1 mb-4">
          يجب أن يكون لشجرة العائلة جذر واحد فقط (الشخص الأقدم في العائلة). 
          حالياً، هناك {{ rootNodes.length }} {{ rootNodes.length === 1 ? 'شخص' : 'أشخاص' }} بدون أب في الشجرة.
        </p>

        <v-alert v-if="rootNodes.length === 0" type="info" variant="tonal" class="mb-4">
          لا يوجد أي أفراد في شجرة العائلة. يرجى إضافة فرد جديد أولاً.
        </v-alert>

        <v-alert v-else-if="rootNodes.length === 1" type="success" variant="tonal" class="mb-4">
          يوجد جذر واحد فقط للشجرة، وهو: <strong>{{ rootNodes[0].name }}</strong>
        </v-alert>

        <v-alert v-else type="warning" variant="tonal" class="mb-4">
          يوجد {{ rootNodes.length }} جذور للشجرة. يرجى اختيار الجذر الرئيسي وربط الجذور الأخرى به.
        </v-alert>

        <div v-if="rootNodes.length > 1">
          <v-select
            v-model="selectedRoot"
            :items="rootNodes"
            item-title="name"
            item-value="id"
            label="اختر الجذر الرئيسي للشجرة"
            variant="outlined"
            class="mb-4"
          ></v-select>

          <v-btn
            color="primary"
            :loading="isProcessing"
            :disabled="!selectedRoot || isProcessing"
            @click="setMainRoot"
            class="mb-4"
          >
            تعيين كجذر رئيسي
          </v-btn>

          <v-alert v-if="message" :type="messageType" variant="tonal" class="mt-4">
            {{ message }}
          </v-alert>
        </div>
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

    <!-- حوار ربط الفرد بأب -->
    <v-dialog v-model="showLinkDialog" max-width="500">
      <v-card>
        <v-card-title>ربط {{ selectedNode?.name }} بأب</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedFather"
            :items="potentialFathers"
            item-title="name"
            item-value="id"
            label="اختر الأب"
            variant="outlined"
            class="mb-4"
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="showLinkDialog = false">إلغاء</v-btn>
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import type { TreeNode } from '../../types/family-tree';

// الحالة
const allNodes = ref<TreeNode[]>([]);
const selectedRoot = ref<number | null>(null);
const isProcessing = ref(false);
const message = ref('');
const messageType = ref('info');
const showLinkDialog = ref(false);
const selectedNode = ref<TreeNode | null>(null);
const selectedFather = ref<number | null>(null);
const isLinking = ref(false);

// الجذور (الأفراد بدون أب)
const rootNodes = computed(() => {
  return allNodes.value.filter(node => !node.father_id);
});

// الأفراد الآخرين بدون أب (باستثناء الجذر الرئيسي)
const otherNodes = computed(() => {
  if (selectedRoot.value) {
    return rootNodes.value.filter(node => node.id !== selectedRoot.value);
  }
  return [];
});

// الآباء المحتملين (جميع الذكور)
const potentialFathers = computed(() => {
  return allNodes.value.filter(node => 
    node.gender === 'male' && 
    (!selectedNode.value || node.id !== selectedNode.value.id)
  );
});

// تحميل بيانات الشجرة
const loadTreeData = async () => {
  try {
    const response = await axios.get('/family-tree');
    if (response.data && Array.isArray(response.data)) {
      allNodes.value = response.data;
    } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
      allNodes.value = response.data.data;
    } else {
      allNodes.value = [];
    }
    
    // إذا كان هناك جذر واحد فقط، اختره تلقائياً
    if (rootNodes.value.length === 1) {
      selectedRoot.value = rootNodes.value[0].id;
    }
  } catch (error) {
    console.error('Error loading tree data:', error);
  }
};

// تعيين الجذر الرئيسي
const setMainRoot = async () => {
  if (!selectedRoot.value) return;
  
  isProcessing.value = true;
  message.value = '';
  
  try {
    // ربط جميع الجذور الأخرى بالجذر الرئيسي
    const promises = otherNodes.value.map(node => 
      axios.put(`/family-tree/${node.id}`, {
        ...node,
        father_id: selectedRoot.value
      })
    );
    
    await Promise.all(promises);
    
    message.value = 'تم تعيين الجذر الرئيسي بنجاح وربط الجذور الأخرى به.';
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

// فتح حوار ربط الفرد بأب
const openLinkDialog = (node: TreeNode) => {
  selectedNode.value = node;
  selectedFather.value = null;
  showLinkDialog.value = true;
};

// ربط الفرد بأب
const linkToFather = async () => {
  if (!selectedNode.value || !selectedFather.value) return;
  
  isLinking.value = true;
  
  try {
    await axios.put(`/family-tree/${selectedNode.value.id}`, {
      ...selectedNode.value,
      father_id: selectedFather.value
    });
    
    showLinkDialog.value = false;
    message.value = `تم ربط ${selectedNode.value.name} بالأب المحدد بنجاح.`;
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
</style>
