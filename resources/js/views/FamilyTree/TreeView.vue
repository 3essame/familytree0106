<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12" class="d-flex justify-space-between align-center mb-3">
        <div class="d-flex align-center">
          <v-icon size="x-large" color="primary" class="me-3">mdi-family-tree</v-icon>
          <h1 class="text-h4 font-weight-bold">شجرة العائلة</h1>
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

    <v-row class="mb-4">
      <v-col cols="12" md="6">
        <v-card elevation="2" rounded="lg">
          <v-card-text class="pa-2">
            <v-text-field
              v-model="searchQuery"
              prepend-inner-icon="mdi-magnify"
              label="البحث عن فرد"
              placeholder="اكتب اسم الفرد للبحث..."
              variant="outlined"
              density="comfortable"
              hide-details
              @keyup.enter="searchNodes"
              clearable
            >
              <template v-slot:append>
                <v-btn
                  color="primary"
                  icon="mdi-magnify"
                  variant="text"
                  @click="searchNodes"
                  :disabled="!searchQuery"
                />
              </template>
            </v-text-field>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="6" class="d-flex justify-end align-center">
        <v-btn-toggle v-model="viewMode" mandatory color="primary" rounded="lg" density="comfortable">
          <v-btn value="chart" prepend-icon="mdi-family-tree">
            عرض الشجرة
          </v-btn>
          <v-btn value="list" prepend-icon="mdi-format-list-bulleted">
            عرض القائمة
          </v-btn>
        </v-btn-toggle>
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
                <v-alert type="info" variant="tonal" icon="mdi-information" border="start" class="mb-4">
                  لا يوجد بيانات في شجرة العائلة حاليًا
                </v-alert>
                <v-btn color="success" prepend-icon="mdi-account-plus" variant="elevated" rounded @click="showAddDialog = true">
                  إضافة أول فرد في الشجرة
                </v-btn>
              </div>
              <div v-else>
                <v-alert v-if="isSearchResult" type="info" variant="tonal" icon="mdi-magnify" class="mb-4" closable @click:close="resetSearch">
                  نتائج البحث عن: <strong>{{ searchQuery }}</strong>
                  <template v-slot:append>
                    <v-btn color="primary" variant="text" size="small" @click="resetSearch">العودة للشجرة الكاملة</v-btn>
                  </template>
                </v-alert>

                <!-- عرض الشجرة البيانية -->
                <div v-if="viewMode === 'chart'">
                  <D3OrgChart
                    :tree-data="treeData"
                    @node-click="handleNodeClick"
                  />
                </div>

                <!-- عرض القائمة -->
                <div v-else class="family-tree-grid">
                  <div v-for="node in treeData" :key="node.id" class="mb-4">
                    <FamilyTreeNode
                      :node="{
                        ...node,
                        birth_date: node.birth_date || undefined,
                        death_date: node.death_date || undefined,
                        father_id: node.father_id || undefined,
                        mother_id: node.mother_id || undefined,
                        info: node.info || undefined,
                        children: node.children || []
                      }"
                      @refresh="loadTreeData"
                    />
                  </div>
                </div>
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
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import FamilyTreeNode from './FamilyTreeNode.vue';
import D3OrgChart from '../../components/FamilyTree/D3OrgChart.vue';
import EditNode from './EditNode.vue';
import axios from 'axios';
import type { TreeNode } from '../../types/family-tree';

interface ApiResponse {
  data?: TreeNode[] | Record<string, TreeNode>;
}

// Refs with type annotations
const treeData = ref<TreeNode[] | null>(null);
const loading = ref<boolean>(true);
const error = ref<string>('');
const searchQuery = ref<string>('');
const isSearchResult = ref<boolean>(false);
const showAddDialog = ref<boolean>(false);
const viewMode = ref<'chart' | 'list'>('chart');

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

    treeData.value = apiData;
    console.log('Tree Data processed:', treeData.value);
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

function resetSearch(): void {
  searchQuery.value = '';
  isSearchResult.value = false;
  loadTreeData();
}

async function searchNodes(): Promise<void> {
  if (!searchQuery.value) {
    resetSearch();
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const response = await axios.get<ApiResponse>(`/family-tree/search?q=${encodeURIComponent(searchQuery.value)}`);
    console.log('Search Response:', response.data);

    let searchData: TreeNode[] = [];
    if (response.data && typeof response.data === 'object') {
      if (Array.isArray(response.data)) {
        searchData = response.data;
      } else if (response.data.data && Array.isArray(response.data.data)) {
        searchData = response.data.data;
      } else {
        searchData = Object.values(response.data).filter((item): item is TreeNode =>
          typeof item === 'object' && item !== null
        );
      }
    }

    console.log('Search data processed:', searchData);
    console.log('Search data length:', searchData.length);

    treeData.value = searchData;
    console.log('Search Data processed:', treeData.value);
    isSearchResult.value = true;
  } catch (e) {
    error.value = 'فشل البحث عن الأفراد';
    console.error('Error searching nodes:', e);
  } finally {
    loading.value = false;
  }
}

function handleNodeClick(nodeId: string | number): void {
  console.log('Node clicked:', nodeId);
  const node = treeData.value?.find(n => n.id.toString() === nodeId.toString());
  if (node) {
    // Handle node click
  }
}

onMounted(() => {
  loadTreeData();
  viewMode.value = 'chart';
});
</script>

<style scoped>
.family-tree-container {
  min-height: 600px;
  padding: 16px;
}

.family-tree-card {
  border: 1px solid rgba(0, 0, 0, 0.05);
  background-color: #fafafa;
}

.family-tree-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  padding: 10px;
}

@media (max-width: 768px) {
  .family-tree-grid {
    grid-template-columns: 1fr;
    gap: 10px;
    padding: 5px;
  }

  .family-tree-container {
    min-height: 400px;
    padding: 8px;
  }
}

/* أنماط جديدة للشجرة البيانية */
:deep(.family-tree-container) {
  direction: rtl;
}
</style>
