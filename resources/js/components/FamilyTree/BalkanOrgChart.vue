<template>
  <div class="balkan-org-chart-container" ref="chartContainer">
    <div v-if="isLoading" class="chart-loading">
      <v-progress-circular indeterminate color="primary" size="64" />
      <div class="mt-4">جاري تحميل الشجرة...</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';
import type { TreeNode } from '../../types/family-tree';

const props = defineProps<{
  treeData: TreeNode[] | null;
}>();

const emit = defineEmits<{
  (e: 'node-click', nodeId: number): void;
}>();

// Refs
const chartContainer = ref<HTMLElement | null>(null);
const isLoading = ref(true);
let chart: any = null;

// تحويل البيانات إلى الشكل المطلوب للمكتبة
const transformData = (data: TreeNode[] | null): any[] => {
  if (!data || data.length === 0) return [];

  console.log('Original data:', data);
  console.log('Root nodes:', data.filter(node => !node.father_id));

  // إنشاء مصفوفة جديدة للبيانات المحولة
  const transformedData = data.map(item => {
    // طباعة بيانات العنصر للتشخيص
    console.log('Processing item:', item);
    console.log('Birth date:', item.birth_date);
    console.log('Death date:', item.death_date);

    // التأكد من أن جميع الحقول لها قيم صالحة
    const birthDateFormatted = item.birth_date ? formatDate(item.birth_date) : '';
    const deathDateFormatted = item.death_date ? formatDate(item.death_date) : '';

    console.log('Formatted birth date:', birthDateFormatted);
    console.log('Formatted death date:', deathDateFormatted);

    return {
      id: item.id ? item.id.toString() : `node_${Math.random().toString(36).substr(2, 9)}`,
      pid: item.father_id ? item.father_id.toString() : '',
      name: item.name || 'بدون اسم',
      gender: item.gender || 'unknown',
      birthDate: birthDateFormatted,
      deathDate: deathDateFormatted,
      birthDateLabel: birthDateFormatted ? `م: ${birthDateFormatted}` : '',
      deathDateLabel: deathDateFormatted ? `و: ${deathDateFormatted}` : '',
      tags: [item.gender || 'unknown'], // إضافة الجنس كعلامة للتنسيق
      // إضافة أي بيانات أخرى قد تكون مفيدة
      originalData: item // الاحتفاظ بالبيانات الأصلية للاستخدام لاحقًا
    };
  });

  console.log('Transformed data:', transformedData);
  return transformedData;
};

// تنسيق التاريخ بالتقويم الميلادي بصيغة يوم/شهر/سنة فقط
const formatDate = (dateString: string | null | undefined): string => {
  console.log('formatDate called with:', dateString);
  if (!dateString) {
    console.log('Date string is empty or null');
    return '';
  }

  try {
    // التعامل مع التاريخ كنص أولاً
    // التعامل مع صيغة ISO مثل "2023-01-01T00:00:00.000000Z"
    if (typeof dateString === 'string' && dateString.includes('T')) {
      // استخراج الجزء الخاص بالتاريخ فقط (YYYY-MM-DD)
      const datePart = dateString.split('T')[0];
      const [year, month, day] = datePart.split('-').map(Number);

      // التحقق من صحة المكونات
      if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
        return `${day}/${month}/${year}`;
      }
    }

    // إذا لم تنجح الطريقة السابقة، جرب باستخدام Date
    const date = new Date(dateString);
    if (!isNaN(date.getTime())) {
      const day = date.getDate();
      const month = date.getMonth() + 1;
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    }

    // إذا لم ينجح أي شيء، أعد النص الأصلي
    return dateString;
  } catch (e) {
    console.error('Error formatting date:', e);
    return dateString || '';
  }
};

// تهيئة المخطط
const initChart = () => {
  console.log('initChart called');
  console.log('chartContainer:', chartContainer.value);
  console.log('props.treeData:', props.treeData);

  if (!chartContainer.value || !props.treeData) {
    console.warn('Missing chartContainer or treeData');
    isLoading.value = false;
    return;
  }

  isLoading.value = true;

  try {
    const data = transformData(props.treeData);

    // التحقق من وجود بيانات
    if (!data || data.length === 0) {
      console.warn('No data to display in chart');
      isLoading.value = false;
      return;
    }

    console.log('Data ready for chart:', data);

    // إنشاء مخطط جديد
    chart = new OrgChart(chartContainer.value, {
      template: 'olivia',
      enableDragDrop: true,
      nodeBinding: {
        field_0: 'name',
        field_1: 'birthDateLabel',
        field_2: 'deathDateLabel',
        img_0: (d: any) => d.gender === 'male' ? '/images/male.png' : '/images/female.png'
      } as any,
      nodes: data,
      orientation: OrgChart.orientation.right_top,
      mouseScrool: OrgChart.action.zoom,
      nodeMouseClick: OrgChart.action.edit,
      enableSearch: false,
      enableExport: true,
      searchFields: [],
      searchFieldsWeight: {},
      levelSeparation: 60,
      siblingSeparation: 40,
      subtreeSeparation: 60,
      padding: 30,
      miniMap: true,
      nodeCircleMenu: {
        edit: { icon: OrgChart.icon.edit(24, 24, '#0085ff'), text: 'تعديل' },
        details: { icon: OrgChart.icon.details(24, 24, '#0085ff'), text: 'التفاصيل' }
      },
      nodeMenu: {
        details: { text: 'التفاصيل' },
        edit: { text: 'تعديل' },
        add: { text: 'إضافة' },
        remove: { text: 'حذف' }
      },
      menu: {
        pdf: { text: 'تصدير PDF' },
        png: { text: 'تصدير PNG' },
        svg: { text: 'تصدير SVG' },
        csv: { text: 'تصدير CSV' }
      }
    } as any);

    // إضافة مستمع الأحداث للنقر على العقدة
    chart.on('click', (sender: any, args: any) => {
      if (args.node && args.node.id) {
        try {
          const nodeId = parseInt(args.node.id);
          if (!isNaN(nodeId)) {
            emit('node-click', nodeId);
          }
        } catch (e) {
          console.error('Error parsing node ID:', e);
        }
      }
    });

    console.log('Chart created successfully:', chart);
  } catch (error) {
    console.error('Error initializing chart:', error);
  } finally {
    isLoading.value = false;
  }
};

// مراقبة التغييرات في البيانات
watch(() => props.treeData, () => {
  initChart();
}, { deep: true });

// تهيئة المخطط عند تحميل المكون
onMounted(() => {
  initChart();
});

// تنظيف عند إزالة المكون
onBeforeUnmount(() => {
  try {
    if (chart) {
      // محاولة استدعاء أي طريقة تنظيف متاحة
      if (typeof chart.destroy === 'function') {
        chart.destroy();
      }

      chart = null;
    }
  } catch (error) {
    console.error('Error cleaning up chart:', error);
  }
});
</script>

<style scoped>
.balkan-org-chart-container {
  width: 100%;
  height: 600px;
  position: relative;
  background-color: #f9f9f9;
  border-radius: 8px;
  overflow: hidden;
}

.chart-loading {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background-color: rgba(255, 255, 255, 0.8);
  z-index: 10;
}

/* تخصيص مظهر العقد حسب الجنس */
:deep([data-tagged=male]) rect {
  fill: #bbdefb !important; /* أزرق فاتح - Light Blue 100 */
  stroke: #2196f3 !important;
  stroke-width: 2px !important;
}

:deep([data-tagged=female]) rect {
  fill: #f8bbd0 !important; /* وردي فاتح - Pink 100 */
  stroke: #f06292 !important;
  stroke-width: 2px !important;
}

:deep([data-tagged=unknown]) rect {
  fill: #f5f5f5 !important;
  stroke: #9e9e9e !important;
  stroke-width: 2px !important;
}

/* تحسين مظهر النص داخل العقد */
:deep(.field_0) {
  font-weight: bold !important;
  font-size: 16px !important;
  color: #333 !important;
  margin-bottom: 5px !important;
}

:deep(.field_1) {
  font-size: 13px !important;
  color: #0277bd !important; /* لون أزرق لتاريخ الميلاد */
  margin-bottom: 2px !important;
}

:deep(.field_2) {
  font-size: 13px !important;
  color: #c2185b !important; /* لون وردي لتاريخ الوفاة */
}

/* تحسين مظهر العقد عند التحويم */
:deep([data-tagged]) rect:hover {
  filter: brightness(0.95) !important;
  transition: all 0.3s ease !important;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.2) !important;
}
</style>
