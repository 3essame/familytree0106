<template>
  <div class="simple-tree-view">
    <v-tabs v-model="activeTab" bg-color="primary" centered>
      <v-tab value="table">عرض الجدول</v-tab>
      <v-tab value="cards">عرض البطاقات</v-tab>
      <v-tab value="tree">عرض الشجرة</v-tab>
    </v-tabs>

    <v-window v-model="activeTab" class="mt-4">
      <!-- عرض الجدول -->
      <v-window-item value="table">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon color="primary" class="me-2">mdi-table</v-icon>
            <span>عرض الجدول</span>
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="بحث"
              single-line
              hide-details
              density="compact"
              class="table-search"
            ></v-text-field>
          </v-card-title>
          <v-card-text>
            <div v-if="!treeData || treeData.length === 0" class="text-center py-4">
              <v-icon size="64" color="blue-grey-lighten-3" class="mb-4">mdi-account-group</v-icon>
              <div class="text-h6 mb-2">لا يوجد بيانات لعرض شجرة العائلة</div>
              <div class="text-body-1">يرجى إضافة أفراد للعائلة لعرض الشجرة</div>
            </div>
            <div v-else>
              <div class="text-subtitle-1 mb-4">عدد أفراد العائلة: {{ treeData.length }}</div>

              <v-table>
                <thead>
                  <tr>
                    <th>الاسم</th>
                    <th>الجنس</th>
                    <th>تاريخ الميلاد</th>
                    <th>الأب</th>
                    <th>الأجراءات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="node in filteredNodes" :key="node.id">
                    <td>{{ node.name }}</td>
                    <td class="text-center">
                      <v-chip
                        :color="node.gender === 'male' ? 'blue' : 'pink'"
                        text-color="white"
                        size="small"
                      >
                        {{ node.gender === 'male' ? 'ذكر' : 'أنثى' }}
                      </v-chip>
                    </td>
                    <td class="text-center">{{ formatDate(node.birth_date) }}</td>
                    <td class="text-center">{{ getFatherName(node) }}</td>
                    <td class="text-center">
                      <v-btn
                        size="small"
                        color="primary"
                        variant="text"
                        icon="mdi-eye"
                        @click="$emit('node-click', node.id)"
                      ></v-btn>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- عرض البطاقات -->
      <v-window-item value="cards">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon color="primary" class="me-2">mdi-card-account-details</v-icon>
            <span>عرض البطاقات</span>
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="بحث"
              single-line
              hide-details
              density="compact"
              class="table-search"
            ></v-text-field>
          </v-card-title>
          <v-card-text>
            <div v-if="!treeData || treeData.length === 0" class="text-center py-4">
              <v-icon size="64" color="blue-grey-lighten-3" class="mb-4">mdi-account-group</v-icon>
              <div class="text-h6 mb-2">لا يوجد بيانات لعرض شجرة العائلة</div>
              <div class="text-body-1">يرجى إضافة أفراد للعائلة لعرض الشجرة</div>
            </div>
            <div v-else>
              <div class="text-subtitle-1 mb-4">عدد أفراد العائلة: {{ treeData.length }}</div>

              <v-row>
                <v-col
                  v-for="node in filteredNodes"
                  :key="node.id"
                  cols="12"
                  sm="6"
                  md="4"
                  lg="3"
                >
                  <v-card
                    :color="node.gender === 'male' ? 'blue-lighten-5' : 'pink-lighten-5'"
                    class="mb-4 person-card"
                    @click="$emit('node-click', node.id)"
                  >
                    <v-card-item>
                      <template v-slot:prepend>
                        <v-avatar
                          :color="node.gender === 'male' ? 'blue' : 'pink'"
                          class="me-3"
                        >
                          <v-icon color="white">
                            {{ node.gender === 'male' ? 'mdi-account' : 'mdi-account-female' }}
                          </v-icon>
                        </v-avatar>
                      </template>
                      <v-card-title>{{ node.name }}</v-card-title>
                    </v-card-item>
                    <v-card-text>
                      <div class="d-flex align-center mb-2">
                        <v-icon size="small" color="grey" class="me-2">mdi-calendar</v-icon>
                        <span>{{ formatDate(node.birth_date) }}</span>
                      </div>
                      <div class="d-flex align-center mb-2">
                        <v-icon size="small" color="grey" class="me-2">mdi-account-child</v-icon>
                        <span>الأب: {{ getFatherName(node) }}</span>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </div>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- عرض الشجرة -->
      <v-window-item value="tree">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon color="primary" class="me-2">mdi-family-tree</v-icon>
            <span>عرض الشجرة</span>
          </v-card-title>
          <v-card-text>
            <div v-if="!treeData || treeData.length === 0" class="text-center py-4">
              <v-icon size="64" color="blue-grey-lighten-3" class="mb-4">mdi-account-group</v-icon>
              <div class="text-h6 mb-2">لا يوجد بيانات لعرض شجرة العائلة</div>
              <div class="text-body-1">يرجى إضافة أفراد للعائلة لعرض الشجرة</div>
            </div>
            <div v-else>
              <div class="text-subtitle-1 mb-4">عدد أفراد العائلة: {{ treeData.length }}</div>

              <BalkanOrgChart :tree-data="treeData" @node-click="emit('node-click', $event)" />
            </div>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import BalkanOrgChart from './BalkanOrgChart.vue';
import type { TreeNode } from '../../types/family-tree';

const props = defineProps<{
  treeData: TreeNode[] | null;
}>();

const emit = defineEmits<{
  (e: 'node-click', nodeId: number): void;
}>();

// Refs
const activeTab = ref('cards'); // Default active tab
const search = ref(''); // Search text
const loading = ref(false);
const error = ref(null);
const containerWidth = ref(0);
const containerHeight = ref(0);

// Filtered nodes based on search
const filteredNodes = computed(() => {
  if (!props.treeData) return [];
  if (!search.value) return props.treeData;

  const searchLower = search.value.toLowerCase();
  return props.treeData.filter(node => {
    return (
      node.name.toLowerCase().includes(searchLower) ||
      (node.birth_date && node.birth_date.includes(searchLower)) ||
      getFatherName(node).toLowerCase().includes(searchLower)
    );
  });
});

// Format date function
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

// Get father name
const getFatherName = (node: TreeNode): string => {
  if (!node.father_id || !props.treeData) return '-';

  const father = props.treeData.find(n => n.id === node.father_id);
  return father ? father.name : '-';
};

// دالة للتحقق من حالة البيانات
function checkDataState() {
  console.log('Current simple tree state:', {
    treeData: props.treeData,
    loading: loading.value,
    error: error.value,
    containerWidth: containerWidth.value,
    containerHeight: containerHeight.value
  });
}

// تحديث حالة البيانات عند التغيير
watch(() => props.treeData, (newValue) => {
  console.log('Tree data changed:', {
    length: newValue?.length,
    firstItem: newValue?.[0],
    lastItem: newValue?.[newValue.length - 1]
  });
  checkDataState();
}, { deep: true });

watch(loading, (newValue) => {
  console.log('Loading state changed:', newValue);
  checkDataState();
});

watch(error, (newValue) => {
  console.log('Error state changed:', newValue);
  checkDataState();
});
</script>

<style scoped>
.simple-tree-view {
  width: 100%;
}

.table-search {
  max-width: 300px;
}

.person-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.person-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.tree-container {
  width: 100%;
  height: 500px;
  background-color: #f9f9f9;
  border-radius: 8px;
  overflow: hidden;
  position: relative;
}
</style>
