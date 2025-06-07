<template>
  <div class="family-tree-node">
    <v-card class="pa-3 mb-3" elevation="2" :color="node.gender === 'male' ? 'blue-lighten-5' : 'pink-lighten-5'" rounded="lg">
      <v-card-title class="d-flex flex-wrap align-center justify-space-between pb-1">
        <div class="d-flex align-center">
          <v-avatar :color="node.gender === 'male' ? 'blue-lighten-2' : 'pink-lighten-2'" class="me-2" :size="isMobile ? 28 : 36">
            <v-icon :size="isMobile ? 'small' : 'default'" :color="node.gender === 'male' ? 'blue-darken-2' : 'pink-darken-2'">
              {{ node.gender === 'male' ? 'mdi-account' : 'mdi-account-female' }}
            </v-icon>
          </v-avatar>
          <span :class="isMobile ? 'text-subtitle-1 font-weight-bold text-truncate' : 'text-h6 font-weight-bold'" style="max-width: 200px;">
            {{ node.name || 'بدون اسم' }}
          </span>
        </div>
        <div class="d-flex node-actions">
          <v-btn
            v-if="node.children?.length"
            icon="mdi-chevron-down"
            :size="isMobile ? 'x-small' : 'small'"
            color="grey-darken-1"
            variant="text"
            :class="{ 'rotate-icon': !expanded }"
            @click="toggleExpand"
          />
        </div>
      </v-card-title>

      <v-card-text>
        <div class="d-flex flex-wrap gap-2">
          <v-chip
            v-if="node.relation"
            size="small"
            color="primary"
            variant="outlined"
            class="me-2"
          >
            {{ node.relation }}
          </v-chip>
          <v-chip
            v-if="node.birth_date"
            size="small"
            color="success"
            variant="outlined"
            class="me-2"
          >
            <v-icon size="small" class="me-1">mdi-calendar</v-icon>
            {{ formatDate(node.birth_date) }}
          </v-chip>
          <v-chip
            v-if="node.death_date"
            size="small"
            color="error"
            variant="outlined"
            class="me-2"
          >
            <v-icon size="small" class="me-1">mdi-calendar-remove</v-icon>
            {{ formatDate(node.death_date) }}
          </v-chip>
        </div>

        <div v-if="node.notes" class="mt-2 text-body-2">
          {{ node.notes }}
        </div>

        <div v-if="expanded && node.children?.length" class="children mt-3 pt-2">
          <div class="children-label d-flex align-center">
            <v-icon :size="isMobile ? 'x-small' : 'small'" color="primary" class="me-2">mdi-account-child</v-icon>
            <span :class="isMobile ? 'text-caption font-weight-bold' : ''">الأبناء ({{ node.children.length }}):</span>
          </div>
          <div class="children-list mt-2">
            <FamilyTreeNode
              v-for="child in node.children"
              :key="child.id"
              :node="child"
              @refresh="$emit('refresh')"
            />
          </div>
        </div>

        <!-- للتشخيص -->
        <div v-if="node.id && !isMobile" class="mt-2 text-caption text-grey">
          <v-chip size="x-small" color="grey-lighten-3" class="me-1">ID: {{ node.id }}</v-chip>
          <v-chip v-if="node.father_id" size="x-small" color="blue-lighten-4" class="me-1">الأب: {{ node.father_id }}</v-chip>
          <v-chip v-if="node.mother_id" size="x-small" color="pink-lighten-4" class="me-1">الأم: {{ node.mother_id }}</v-chip>
          <v-chip v-if="node.spouse_id" size="x-small" color="purple-lighten-4">الزوج/ة: {{ node.spouse_id }}</v-chip>
        </div>

        <!-- للتشخيص على الهواتف (مبسط) -->
        <div v-if="node.id && isMobile" class="mt-2 text-caption text-grey">
          <v-chip size="x-small" color="grey-lighten-3" class="me-1 mb-1">ID: {{ node.id }}</v-chip>
          <v-chip v-if="node.father_id || node.mother_id" size="x-small" color="blue-lighten-5" class="me-1 mb-1">الوالدين: {{ node.father_id || '-' }}/{{ node.mother_id || '-' }}</v-chip>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  node: { type: Object, required: true }
});

console.log('FamilyTreeNode received node:', props.node);

const emit = defineEmits(['refresh']);

const expanded = ref(true);
const isMobile = ref(false);

// دالة للتحقق من حجم الشاشة
const checkMobile = () => {
  isMobile.value = window.innerWidth < 768;
};

// التحقق من حجم الشاشة عند التحميل وعند تغيير الحجم
onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile);
});

function formatDate(date) {
  if (!date) return '';

  try {
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) return date; // إذا كان التاريخ غير صالح

    // تنسيق التاريخ بالعربية (ميلادي)
    const options = { year: 'numeric', month: 'long', day: 'numeric', calendar: 'gregory' };
    return new Intl.DateTimeFormat('ar', options).format(dateObj);
  } catch (error) {
    console.error('خطأ في تنسيق التاريخ:', error);
    return date;
  }
}

function toggleExpand() {
  expanded.value = !expanded.value;
}

// دالة للتحقق من حالة البيانات
function checkDataState() {
  console.log('Current node state:', {
    node: props.node,
    expanded: expanded.value,
    isMobile: isMobile.value
  });
}
</script>

<style scoped>
.family-tree-node {
  margin-bottom: 12px;
}

.children {
  margin-top: 12px;
  padding-right: 16px;
  border-right: 2px dashed #bdbdbd;
}

.children-label {
  font-weight: bold;
  margin-bottom: 8px;
}

.children-list {
  margin-right: 12px;
}

.rotate-icon {
  transform: rotate(-90deg);
}

.v-card {
  transition: all 0.3s ease;
}

.v-card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

/* تحسينات للهواتف */
@media (max-width: 768px) {
  .family-tree-node {
    margin-bottom: 8px;
  }

  .v-card {
    padding: 8px !important;
  }

  .v-card-title {
    padding: 0 0 4px 0 !important;
  }

  .v-card-text {
    padding: 8px 0 0 0 !important;
  }

  .children {
    margin-top: 8px;
    padding-right: 8px;
  }

  .children-list {
    margin-right: 8px;
  }

  .node-actions {
    margin-top: 4px;
  }

  .text-truncate {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
  }
}
</style>
