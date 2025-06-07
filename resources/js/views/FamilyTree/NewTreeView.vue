<template>
  <v-container fluid>
    <!-- ================================================================= -->
    <!-- رأس الصفحة (متجاوب)                                             -->
    <!-- ================================================================= -->
    <v-row class="mb-4">
      <v-col cols="12">
        <div class="d-flex flex-wrap justify-space-between align-center ga-4">
          <!-- عنوان الصفحة -->
          <div class="d-flex align-center">
            <v-icon size="x-large" color="primary" class="me-3">mdi-family-tree</v-icon>
            <h1 class="text-h5 text-md-h4 font-weight-bold">شجرة العائلة</h1>
          </div>
          
          <!-- أزرار التحكم -->
          <div class="d-flex flex-wrap ga-2">
            <v-btn color="primary" prepend-icon="mdi-refresh" variant="tonal" :loading="loading" @click="loadTreeData" text="تحديث" />
            <v-btn color="success" prepend-icon="mdi-account-plus" variant="tonal" @click="showAddMemberDialog()" text="إضافة" />
            <v-btn v-if="treeData && treeData.length > 0" color="warning" prepend-icon="mdi-tree" variant="tonal" @click="showRootManager = true" text="الجذر" />
          </div>
        </div>
      </v-col>
    </v-row>  
      
    <!-- ================================================================= -->
    <!-- حاوية المحتوى الرئيسي                                            -->
    <!-- ================================================================= -->
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <div style="min-height: 65vh; display: flex; flex-direction: column;">
            <!-- حالة التحميل -->
            <div v-if="loading" class="d-flex flex-column justify-center align-center flex-grow-1">
              <v-progress-circular indeterminate color="primary" size="64" />
              <div class="mt-4 text-h6 text-medium-emphasis">جاري تحميل شجرة العائلة...</div>
            </div>
            
            <!-- حالة الخطأ -->
            <div v-else-if="error" class="d-flex flex-column justify-center align-center flex-grow-1 pa-4">
              <v-alert type="error" variant="tonal" class="mb-4" max-width="500" :text="error" />
              <v-btn color="primary" prepend-icon="mdi-refresh" @click="loadTreeData" text="إعادة المحاولة" />
            </div>
            
            <!-- حالة عدم وجود بيانات -->
            <div v-else-if="!treeData || treeData.length === 0" class="d-flex flex-column justify-center align-center flex-grow-1">
              <v-icon size="80" color="grey-lighten-2">mdi-account-group-outline</v-icon>
              <div class="text-h6 mt-4 text-medium-emphasis">لا يوجد بيانات لعرضها</div>
            </div>
            
            <!-- حالة النجاح - هنا يتم التبديل بين العروض -->
            <div v-else class="flex-grow-1">
              <!-- ===================================================== -->
              <!-- عرض سطح المكتب والأجهزة اللوحية (الشاشات الكبيرة)     -->
              <!-- ===================================================== -->
              <D3FamilyTree 
                v-if="display.mdAndUp"
                :tree-data="treeData" 
                @node-click="handleNodeClick"
              />

              <!-- ===================================================== -->
              <!-- عرض الهواتف (الشاشات الصغيرة)                        -->
              <!-- ===================================================== -->
              <v-list lines="two" class="py-0" v-else>
                <v-list-subheader>أفراد العائلة</v-list-subheader>
                <template v-for="person in treeData" :key="person.id">
                  <v-list-item @click="handleNodeClick(person)">
                    <template #prepend>
                      <v-avatar :color="person.gender === 'male' ? 'blue-lighten-1' : 'pink-lighten-1'">
                        <v-icon color="white">{{ person.gender === 'male' ? 'mdi-face-man' : 'mdi-face-woman' }}</v-icon>
                      </v-avatar>
                    </template>
                    <v-list-item-title class="font-weight-bold">{{ person.name }}</v-list-item-title>
                    <v-list-item-subtitle>{{ getPersonSubtitle(person) }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-divider inset />
                </template>
              </v-list>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ================================================================= -->
    <!-- النوافذ المنبثقة (Dialogs)                                     -->
    <!-- ================================================================= -->
    <v-dialog v-if="selectedNode" v-model="showNodeDetails" max-width="500px" scrollable>
      <v-card>
        <v-toolbar color="primary" dark>
          <v-toolbar-title>تفاصيل الفرد</v-toolbar-title>
          <v-spacer />
          <v-btn icon="mdi-close" @click="showNodeDetails = false" />
        </v-toolbar>
        <v-card-text class="pa-4">
          <FamilyTreeNode :node="selectedNode" @refresh="loadTreeData" />
        </v-card-text>
      </v-card>
    </v-dialog>
    
    <AddMemberForm v-model="showAddMemberModal" @submit="handleAddMemberSuccess" />
    
    <v-dialog v-model="showRootManager" max-width="800px" persistent>
        <v-card>
            <v-toolbar color="warning" dark>
                <v-toolbar-title>إدارة جذر شجرة العائلة</v-toolbar-title>
                <v-spacer />
                <v-btn icon="mdi-close" @click="showRootManager = false" />
            </v-toolbar>
            <v-card-text class="pa-4">
                <TreeRootManager :tree-data="treeData" @root-set="onRootSet" />
            </v-card-text>
        </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useDisplay } from 'vuetify';
import axios from 'axios';
import type { TreeNode } from '../../types/family-tree';

// استيراد المكونات الفرعية
import D3FamilyTree from '../../components/FamilyTree/D3FamilyTree.vue';
import FamilyTreeNode from './FamilyTreeNode.vue';
import AddMemberForm from '@/components/FamilyTree/AddMemberForm.vue';
import TreeRootManager from '../../components/FamilyTree/TreeRootManager.vue';

// =================================================================
// الحالة التفاعلية (Reactive State)
// =================================================================
const display = useDisplay();
const treeData = ref<TreeNode[] | null>(null);
const loading = ref<boolean>(true);
const error = ref<string>('');
const selectedNode = ref<TreeNode | null>(null);
const showNodeDetails = ref<boolean>(false);
const showAddMemberModal = ref(false);
const showRootManager = ref(false);

// =================================================================
// دوال جلب ومعالجة البيانات
// =================================================================
async function loadTreeData(): Promise<void> {
  loading.value = true;
  error.value = '';
  try {
    const response = await axios.get('/family-tree');
    let apiData: TreeNode[] = [];
    if (response.data) {
        // منطق مرن للتعامل مع مختلف أشكال استجابة الـ API
        apiData = Array.isArray(response.data) 
            ? response.data 
            : (Array.isArray(response.data.data) ? response.data.data : Object.values(response.data));
    }
    treeData.value = apiData.filter(item => typeof item === 'object' && item !== null);
  } catch (e) {
    error.value = 'فشل تحميل بيانات الشجرة. يرجى التحقق من اتصالك بالشبكة والمحاولة مرة أخرى.';
    console.error('Error loading tree data:', e);
  } finally {
    loading.value = false;
  }
}

// =================================================================
// دوال التعامل مع أحداث المستخدم
// =================================================================
function handleNodeClick(node: TreeNode) {
  selectedNode.value = node;
  showNodeDetails.value = true;
}

function handleAddMemberSuccess() {
  showAddMemberModal.value = false;
  loadTreeData(); // إعادة تحميل البيانات بعد الإضافة
}

function showAddMemberDialog() {
  showAddMemberModal.value = true;
}

function onRootSet() {
  showRootManager.value = false;
  loadTreeData(); // إعادة تحميل البيانات بعد تعيين الجذر
}

// =================================================================
// دوال مساعدة
// =================================================================
const getPersonSubtitle = (person: TreeNode): string => {
  if (person.birth_date) {
    const birthYear = new Date(person.birth_date).getFullYear();
    return `تاريخ الميلاد: ${birthYear}`;
  }
  return 'تاريخ الميلاد: غير معروف';
};

// =================================================================
// دورة حياة المكون (Lifecycle Hook)
// =================================================================
onMounted(loadTreeData);
</script>

<style scoped>
/* يمكن ترك هذا القسم فارغاً حيث أن Vuetify يعالج معظم التنسيقات */
</style>