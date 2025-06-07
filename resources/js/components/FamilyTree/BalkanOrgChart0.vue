<template>
  <div class="balkangraph-container" ref="container">
    <div class="controls">
      <v-btn-group rounded="lg" density="comfortable">
        <v-btn icon="mdi-zoom-in" @click="zoomIn" />
        <v-btn icon="mdi-zoom-out" @click="zoomOut" />
        <v-btn icon="mdi-refresh" @click="resetView" />
        <v-btn :icon="isRTL ? 'mdi-format-horizontal-align-right' : 'mdi-format-horizontal-align-left'" @click="toggleDirection" />
      </v-btn-group>
    </div>
    <div class="chart-area" ref="chartDiv"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import OrgChart from '@balkangraph/orgchart.js';
import type { TreeNode } from '../../types/family-tree';

const props = defineProps<{
  treeData: TreeNode[] | null;
}>();

const emit = defineEmits<{
  (e: 'node-click', nodeId: number): void;
}>();

const chartDiv = ref<HTMLDivElement | null>(null);
let chart: OrgChart | null = null;
const isRTL = ref(true);

// تعريف القوالب
OrgChart.templates.maleTemplate = { ...OrgChart.templates.olivia };
OrgChart.templates.maleTemplate.node = '<rect x="0" y="0" height="{h}" width="{w}" fill="#039BE5" stroke-width="1" rx="5" ry="5"></rect>';

OrgChart.templates.femaleTemplate = { ...OrgChart.templates.olivia };
OrgChart.templates.femaleTemplate.node = '<rect x="0" y="0" height="{h}" width="{w}" fill="#F57C00" stroke-width="1" rx="5" ry="5"></rect>';


function getChartConfig(): OrgChart.options {
  const alignEnum = 2; // Corresponds to OrgChart.align.center
  const orientationEnum = isRTL.value ? 2 : 3; // 2: right, 3: left

  return {
    template: "olivia",
    nodeBinding: {
      field_0: "name",
      img_0: "img"
    },
    align: alignEnum,
    orientation: orientationEnum,
    toolbar: {
      layout: true,
      zoom: true,
      fit: true,
      expandAll: true,
    },
    nodeMenu: {
      details: { text: "التفاصيل" },
    },
    tags: {
      "male": { template: "maleTemplate" },
      "female": { template: "femaleTemplate" }
    },
    // --- تم حذف السطرين التاليين ---
    // enablePan: true, 
    // enableZoom: true,
    scaleInitial: OrgChart.match.boundary,
  };
}

function initChart() {
  if (!chartDiv.value || !props.treeData) return;
  
  const config = getChartConfig();
  chart = new OrgChart(chartDiv.value, config);

  chart.on('click', (sender, args) => {
    const nodeId = Number(args.node.id);
    if (nodeId) {
      emit('node-click', nodeId);
    }
    return false;
  });

  chart.on('init', () => {
    chart?.fit();
  });

  loadData();
}

function formatChartData() {
  if (!props.treeData) return [];
  return props.treeData.map(node => ({
    id: node.id.toString(),
    pid: node.father_id ? node.father_id.toString() : undefined,
    name: node.name,
    img: (node.gender === 'female' ? '/images/female-avatar.png' : '/images/male-avatar.png'),
    tags: [node.gender]
  }));
}

function loadData() {
  if (chart) {
    const data = formatChartData();
    chart.load(data);
  }
}

// Controls
const zoomIn = () => chart?.zoom(true, [0.1, 0.1], true);
const zoomOut = () => chart?.zoom(true, [-0.1, -0.1], true);
const resetView = () => chart?.fit();

function toggleDirection() {
  isRTL.value = !isRTL.value;
  if (chart) {
    chart.destroy();
    initChart();
  }
}

onMounted(initChart);
onUnmounted(() => chart?.destroy());

watch(() => props.treeData, () => {
    if (chart) {
        loadData();
    } else {
        initChart();
    }
}, { deep: true });
</script>

<style scoped>
.balkangraph-container, .chart-area {
  width: 100%;
  height: 100%;
}
.controls {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 10;
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 4px;
}
</style>