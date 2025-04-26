<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12" class="d-flex justify-space-between align-center mb-3">
        <div class="d-flex align-center">
          <v-icon size="x-large" color="primary" class="me-3">mdi-family-tree</v-icon>
          <h1 class="text-h4 font-weight-bold">شجرة العائلة (النسخة الجديدة)</h1>
        </div>
        <div class="d-flex">
          <v-btn
            color="primary"
            prepend-icon="mdi-refresh"
            variant="outlined"
            class="me-2"
            :loading="loading"
            :disabled="loading"
            @click="loadTreeData"
            v-tooltip="'تحديث الشجرة'"
          >
            تحديث
          </v-btn>
          <v-btn
            color="success"
            prepend-icon="mdi-account-plus"
            variant="elevated"
            rounded="pill"
            elevation="2"
            @click="showAddDialog = true"
          >
            إضافة فرد جديد
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- إدارة جذر الشجرة -->
    <v-row v-if="treeData && treeData.length > 0 && hasMultipleRoots">
      <v-col cols="12">
        <TreeRootManager />
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card elevation="3" rounded="lg" class="family-tree-card">
          <v-card-text class="pa-4">
            <div class="family-tree-container">
              <div v-if="loading" class="text-center my-8">
                <v-progress-circular indeterminate color="primary" size="64" />
                <div class="mt-4 text-body-1">جاري تحميل شجرة العائلة...</div>
              </div>
              <div v-else-if="error" class="text-center">
                <v-alert type="error" variant="tonal" icon="mdi-alert-circle" border="start" class="mb-4">
                  {{ error }}
                </v-alert>
                <v-btn color="primary" prepend-icon="mdi-refresh" variant="outlined" rounded @click="loadTreeData">
                  إعادة المحاولة
                </v-btn>
              </div>
              <div v-else-if="!treeData || (Array.isArray(treeData) && treeData.length === 0)" class="text-center py-8">
                <v-icon size="64" color="blue-grey-lighten-3" class="mb-4">mdi-account-group</v-icon>
                <div class="text-h6 mb-2">لا يوجد بيانات لعرض شجرة العائلة</div>
                <div class="text-body-1 mb-4">يرجى إضافة أفراد للعائلة لعرض الشجرة</div>
                <v-btn color="primary" prepend-icon="mdi-account-plus" @click="showAddDialog = true">
                  إضافة فرد جديد
                </v-btn>
              </div>
              <div v-else-if="hasMultipleRoots" class="text-center py-8">
                <v-icon size="64" color="amber-darken-2" class="mb-4">mdi-alert</v-icon>
                <div class="text-h6 mb-2">يجب تعيين جذر واحد للشجرة</div>
                <div class="text-body-1 mb-4">يرجى استخدام أداة إدارة جذر الشجرة أعلاه لتعيين جذر واحد للشجرة.</div>
              </div>
              <div v-else>
                <D3FamilyTree 
                  :tree-data="treeData" 
                  @node-click="handleNodeClick"
                />
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- حوار إضافة فرد جديد -->
    <EditNode
      v-if="showAddDialog"
      :show="showAddDialog"
      @update:show="showAddDialog = false"
      @saved="onNodeSaved"
    />

    <!-- عرض تفاصيل الفرد -->
    <v-dialog
      v-if="selectedNode"
      v-model="showNodeDetails"
      max-width="500"
    >
      <v-card>
        <v-card-title class="text-h5 bg-primary text-white">
          تفاصيل الفرد
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" color="white" @click="selectedNode = null"></v-btn>
        </v-card-title>
        <v-card-text class="pa-4">
          <FamilyTreeNode
            :node="selectedNode"
            @refresh="loadTreeData"
          />
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import SimpleTreeView from '../../components/FamilyTree/SimpleTreeView.vue';
import TreeRootManager from '../../components/FamilyTree/TreeRootManager.vue';
import EditNode from './EditNode.vue';
import FamilyTreeNode from './FamilyTreeNode.vue';
import axios from 'axios';
import type { TreeNode } from '../../types/family-tree';
import D3FamilyTree from '../../components/FamilyTree/D3FamilyTree.vue';

interface ApiResponse {
  data?: TreeNode[] | Record<string, TreeNode>;
}

// Refs with type annotations
const treeData = ref<TreeNode[] | null>(null);
const loading = ref<boolean>(true);
const error = ref<string>('');
const showAddDialog = ref<boolean>(false);
const selectedNode = ref<TreeNode | null>(null);
const showNodeDetails = ref<boolean>(false);

// Computed properties
const rootNodes = computed(() => {
  if (!treeData.value) return [];
  return treeData.value.filter(node => node.father_id === null);
});

const hasMultipleRoots = computed(() => {
  return rootNodes.value.length > 1;
});

// Watch for selectedNode changes to control dialog visibility
watch(selectedNode, (newValue) => {
  showNodeDetails.value = !!newValue;
});

async function loadTreeData(): Promise<void> {
  loading.value = true;
  error.value = '';

  try {
    console.log('جاري طلب بيانات الشجرة من API...');
    const response = await axios.get<ApiResponse>('/family-tree');
    console.log('API Response (raw):', response);
    console.log('API Response (data):', response.data);
    console.log('API Response type:', typeof response.data);

    let apiData: TreeNode[] = [];
    if (response.data && typeof response.data === 'object') {
      if (Array.isArray(response.data)) {
        apiData = response.data;
      } else if (response.data.data && Array.isArray(response.data.data)) {
        apiData = response.data.data;
      } else {
        apiData = Object.values(response.data).filter((item): item is TreeNode =>
          typeof item === 'object' && item !== null
        );
      }
    }

    console.log('API data processed:', apiData);
    console.log('API data length:', apiData.length);

    // تحقق من الجذور
    const roots = apiData.filter(node => !node.father_id);
    
  
    console.log('Number of root nodes:', roots.length);

    treeData.value = apiData;
    console.log('Tree Data processed:', treeData.value);
    console.log('Tree Data is array?', Array.isArray(treeData.value));
    console.log('Tree Data length:', treeData.value?.length || 0);
    console.log('First node:', treeData.value?.[0]);
  } catch (e) {
    error.value = 'فشل تحميل بيانات الشجرة';
    console.error('Error loading tree data:', e);
  } finally {
    loading.value = false;
  }
}

function onNodeSaved(): void {
  loadTreeData();
}

function handleNodeClick(node: TreeNode): void {
  selectedNode.value = node;
  showNodeDetails.value = true;
}

// تم إزالة دوال handleEditNode و handleDeleteNode لأنها لم تعد مطلوبة
// نستخدم الآن مكون FamilyTreeNode الذي يحتوي على وظائف التعديل والحذف

// Validate father-child age difference
function validateFatherChildAge(fatherBirthDate: string | null | undefined, childBirthDate: string | null | undefined): boolean {
  if (!fatherBirthDate || !childBirthDate) return true;

  try {
    const fatherDate = new Date(fatherBirthDate);
    const childDate = new Date(childBirthDate);

    // Father must be at least 10 years older than child
    const diffTime = childDate.getTime() - fatherDate.getTime();
    const diffYears = diffTime / (1000 * 60 * 60 * 24 * 365.25);

    return diffYears >= 10;
  } catch (e) {
    console.error('Error validating age difference:', e);
    return false;
  }
}

onMounted(() => {
  loadTreeData();
});
</script>

<style scoped>
.family-tree-container {
  min-height: 600px;
  padding: 16px;
}

.family-tree-card {
  overflow: hidden;
  background-color: #ffffff;
}

.family-tree-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

@media (max-width: 600px) {
  .family-tree-grid {
    grid-template-columns: 1fr;
  }
}
</style>
