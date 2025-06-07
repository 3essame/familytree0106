<template>
  <div class="d3-org-chart-container" ref="chartContainer">
    <div v-if="isLoading" class="chart-loading">
      <v-progress-circular indeterminate color="primary" size="64" />
      <div class="mt-4">جاري تحميل الشجرة...</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as d3OrgChart from 'd3-org-chart';
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
const error = ref<string | null>(null);
const containerWidth = ref(0);
const containerHeight = ref(0);
let chart: any = null;

// تعريف نوع للبيانات المحولة - تم التعليق عليه لأننا نستخدم any[] الآن
/*
interface TransformedNode {
  id: string;
  parentId: string;
  name: string;
  gender: 'male' | 'female' | string;
  birthDate?: string | null;
  deathDate?: string | null;
  [key: string]: any; // للسماح بخصائص إضافية
}
*/

// تحويل البيانات إلى الشكل المطلوب للمكتبة
const transformData = (data: TreeNode[] | null): any[] => {
  if (!data || data.length === 0) return [];

  console.log('Original data:', data);
  console.log('Root nodes:', data.filter(node => !node.father_id));

  // إنشاء مصفوفة جديدة للبيانات المحولة
  // استخدام الأسماء المطلوبة بالضبط من قبل المكتبة
  return data.map(item => ({
    id: item.id.toString(),
    parentId: item.father_id ? item.father_id.toString() : '',
    name: item.name,
    gender: item.gender,
    birthDate: item.birth_date,
    deathDate: item.death_date,
    // إضافة أي بيانات أخرى قد تكون مفيدة
    data: item // الاحتفاظ بالبيانات الأصلية للاستخدام لاحقًا
  }));
};

// تنسيق التاريخ بالتقويم الميلادي بصيغة يوم/شهر/سنة فقط
const formatDate = (dateString: string | null | undefined): string => {
  if (!dateString) return '';

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

    if (chart) {
      // إذا كان المخطط موجودًا بالفعل، قم بتحديث البيانات فقط
      try {
        chart
          .data(data)
          .render();
      } catch (updateError) {
        console.error('Error updating chart:', updateError);
        // إعادة إنشاء المخطط في حالة فشل التحديث
        chart = null;
        initChart();
        return;
      }
    } else {
      // إنشاء مخطط جديد وفقًا لتوثيق المكتبة
      console.log('Creating new OrgChart instance');
      try {
        // إنشاء مخطط جديد باستخدام الطريقة الموصى بها
        chart = new d3OrgChart.OrgChart()
          .container(chartContainer.value)
          .data(data)
          .nodeWidth(150)
          .nodeHeight(80)
          .childrenMargin(50)
          .compactMarginBetween(25)
          .compactMarginPair(50)
          .siblingsMargin(20)
          .nodeContent((d: any) => {
            // التحقق من وجود البيانات قبل استخدامها
            if (!d || !d.data) {
              return `<div class="node-card">بيانات غير متوفرة</div>`;
            }

            // إخفاء الجذر الوهمي
            if (d.data.id === 'root' || d.data.isRoot) {
              return `<div class="node-card root-node" style="opacity: 0;"></div>`;
            }

            const gender = d.data.gender || 'unknown';
            const name = d.data.name || 'بدون اسم';
            const birthDate = d.data.birthDate ? `م: ${formatDate(d.data.birthDate)}` : '';
            const deathDate = d.data.deathDate ? `و: ${formatDate(d.data.deathDate)}` : '';

            return `
              <div class="node-card ${gender === 'male' ? 'male' : gender === 'female' ? 'female' : 'unknown'}">
                <div class="node-name">${name}</div>
                ${birthDate ? `<div class="node-birth">${birthDate}</div>` : ''}
                ${deathDate ? `<div class="node-death">${deathDate}</div>` : ''}
              </div>
            `;
          })
          .onNodeClick((node: any) => {
            if (node && node.id && node.id !== 'root' && !node.data.isRoot) {
              try {
                const nodeId = parseInt(node.id);
                if (!isNaN(nodeId)) {
                  emit('node-click', nodeId);
                }
              } catch (e) {
                console.error('Error parsing node ID:', e);
              }
            }
          })
          .render();

        console.log('Chart created successfully');
      } catch (err) {
        console.error('Error creating chart:', err);
        error.value = 'حدث خطأ أثناء إنشاء الشجرة';
      }
    }
  } catch (err) {
    console.error('Error in initChart:', err);
    error.value = 'حدث خطأ أثناء تحميل الشجرة';
  } finally {
    isLoading.value = false;
  }
};

// دالة للتحقق من حالة البيانات
function checkDataState() {
  console.log('Current OrgChart state:', {
    treeData: props.treeData,
    loading: isLoading.value,
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

watch(isLoading, (newValue) => {
  console.log('Loading state changed:', newValue);
  checkDataState();
});

watch(error, (newValue) => {
  console.log('Error state changed:', newValue);
  checkDataState();
});

// تهيئة المخطط عند تحميل المكون
onMounted(() => {
  initChart();
});

// تنظيف عند إزالة المكون
onBeforeUnmount(() => {
  try {
    // إزالة مستمعي الأحداث للأزرار
    if (chartContainer.value) {
      const zoomButtons = chartContainer.value.querySelector('.zoom-buttons');
      if (zoomButtons) {
        // إزالة مستمعي الأحداث لكل زر
        const zoomInButton = zoomButtons.querySelector('.zoom-in');
        const zoomOutButton = zoomButtons.querySelector('.zoom-out');
        const zoomResetButton = zoomButtons.querySelector('.zoom-reset');

        if (zoomInButton) zoomInButton.replaceWith(zoomInButton.cloneNode(true));
        if (zoomOutButton) zoomOutButton.replaceWith(zoomOutButton.cloneNode(true));
        if (zoomResetButton) zoomResetButton.replaceWith(zoomResetButton.cloneNode(true));

        // إزالة حاوية الأزرار
        zoomButtons.remove();
      }

      // إزالة جميع مستمعي الأحداث من حاوية المخطط
      const clonedContainer = chartContainer.value.cloneNode(false);
      if (chartContainer.value.parentNode) {
        chartContainer.value.parentNode.replaceChild(clonedContainer, chartContainer.value);
      }
    }

    // إزالة المخطط
    if (chart) {
      // محاولة استدعاء أي طريقة تنظيف متاحة
      if (typeof chart.destroy === 'function') {
        chart.destroy();
      } else if (typeof chart.clear === 'function') {
        chart.clear();
      }

      chart = null;
    }
  } catch (error) {
    console.error('Error cleaning up chart:', error);
  }
});
</script>

<style scoped>
.d3-org-chart-container {
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

.zoom-buttons {
  position: absolute;
  bottom: 20px;
  right: 20px;
  display: flex;
  flex-direction: column;
  z-index: 100;
}

.zoom-button {
  margin: 5px;
  padding: 8px 12px;
  background-color: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  font-family: 'Cairo', sans-serif;
  transition: all 0.2s;
}

.zoom-button:hover {
  background-color: #f5f5f5;
  transform: translateY(-2px);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.zoom-button:active {
  transform: translateY(0);
  box-shadow: none;
}

:deep(.node-card) {
  padding: 10px;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  text-align: center;
  transition: all 0.3s;
  cursor: pointer;
  min-width: 120px;
  max-width: 150px;
  min-height: 60px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

:deep(.node-card:hover) {
  transform: translateY(-3px);
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
}

:deep(.node-card.male) {
  background-color: #bbdefb; /* أزرق فاتح - Light Blue 100 */
  border: 2px solid #2196f3;
}

:deep(.node-card.female) {
  background-color: #f8bbd0; /* وردي فاتح - Pink 100 */
  border: 2px solid #f06292;
}

:deep(.node-card.unknown) {
  background-color: #f5f5f5;
  border: 2px solid #9e9e9e;
}

:deep(.node-name) {
  font-weight: bold;
  font-size: 16px;
  color: #333;
  margin-bottom: 5px;
}

:deep(.node-birth) {
  font-size: 13px;
  color: #0277bd; /* لون أزرق لتاريخ الميلاد */
  margin-bottom: 2px;
}

:deep(.node-death) {
  font-size: 13px;
  color: #c2185b; /* لون وردي لتاريخ الوفاة */
}

:deep(.oc-link) {
  stroke: #ccc;
  stroke-width: 1.5px;
}

:deep(.oc-link:hover) {
  stroke: #90caf9;
  stroke-width: 2.5px;
}

:deep(.oc-expander) {
  fill: #fff;
  stroke: #999;
  stroke-width: 1px;
}

:deep(.oc-expander:hover) {
  fill: #f5f5f5;
  stroke: #666;
}
</style>
