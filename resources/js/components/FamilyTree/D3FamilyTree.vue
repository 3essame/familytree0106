<template>
  <div class="d3-family-tree">
    <div class="zoom-controls">
      <v-btn
        icon="mdi-plus"
        size="small"
        variant="outlined"
        class="mb-2"
        @click="zoomIn"
        :disabled="scale >= 2"
      />
      <v-btn
        icon="mdi-minus"
        size="small"
        variant="outlined"
        class="mb-2"
        @click="zoomOut"
        :disabled="scale <= 0.5"
      />
      <v-btn
        icon="mdi-fit-to-screen"
        size="small"
        variant="outlined"
        @click="resetZoom"
        :disabled="scale === 1"
      />
      <v-btn
        :icon="isHorizontal ? 'mdi-arrow-right' : 'mdi-arrow-down'"
        size="small"
        variant="outlined"
        @click="toggleOrientation"
        class="mt-2"
        v-tooltip="isHorizontal ? 'تغيير إلى عرض عمودي' : 'تغيير إلى عرض أفقي'"
      />
    </div>
    <div class="minimap" ref="minimapContainer"></div>
    <div ref="treeContainer" class="tree-container" @wheel.prevent="handleWheel"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import * as d3 from 'd3';
import type { TreeNode } from '../../types/family-tree';

const props = defineProps<{
  treeData: TreeNode[];
}>();

// تعريف الأحداث
const emit = defineEmits<{
  (e: 'node-click', node: TreeNode): void;
}>();

const treeContainer = ref<HTMLElement | null>(null);
const minimapContainer = ref<HTMLElement | null>(null);
const scale = ref(1);
const transform = ref({ x: 0, y: 0 });

// إضافة متغير لتتبع اتجاه الشجرة
const isHorizontal = ref(true);

interface TreeNodeWithChildren extends TreeNode {
  children: TreeNodeWithChildren[];
  _children?: TreeNodeWithChildren[]; // للعقد المخفية
}

interface ExtendedHierarchyNode extends d3.HierarchyNode<TreeNodeWithChildren> {
  x0?: number;
  y0?: number;
  _children?: d3.HierarchyNode<TreeNodeWithChildren>[];
}

// تخزين حالة العقد
const nodeStates = ref(new Map<number, boolean>());

function findChildren(parentId: number) {
  return props.treeData.filter(node => node.father_id === parentId);
}

function buildTree(node: TreeNode): TreeNodeWithChildren {
  const children = findChildren(node.id);
  const isExpanded = nodeStates.value.get(node.id) ?? true;
  
  return {
    ...node,
    children: isExpanded ? children.map(child => buildTree(child)) : []
  };
}

function prepareTreeData(data: TreeNode[]): TreeNodeWithChildren | null {
  const root = data.find(node => node.father_id === null);
  if (!root) return null;

  return buildTree(root);
}

// تحديث دالة toggleNode لتكون أكثر دقة في التعامل مع الأحداث
function toggleNode(event: MouseEvent, d: ExtendedHierarchyNode) {
  event.stopPropagation(); // منع انتشار الحدث
  if (!d.data.id) return;
  
  // تحديث حالة العقدة
  const isExpanded = nodeStates.value.get(d.data.id) ?? true;
  nodeStates.value.set(d.data.id, !isExpanded);
  
  renderTree();
}

// إضافة دالة لمعالجة النقر على الاسم
function handleNameClick(event: MouseEvent, d: ExtendedHierarchyNode) {
  event.stopPropagation();
  emit('node-click', d.data);
}

// تحديث الشجرة
function update(source: ExtendedHierarchyNode) {
  const margin = { top: 20, right: 90, bottom: 30, left: 90 };
  const width = 800 - margin.left - margin.right;
  const height = 600 - margin.top - margin.bottom;

  const svg = d3.select(treeContainer.value).select('svg g');
  const treeLayout = d3.tree<TreeNodeWithChildren>().size([height, width]);
  
  // حساب المواقع الجديدة
  const treeData = treeLayout(source);
  const nodes = treeData.descendants();
  const links = treeData.links();

  // تحديث الروابط
  const linkGenerator = d3.linkHorizontal();
  const link = svg.selectAll('.link')
    .data(links, (d: any) => d.target.data.id);

  // إزالة الروابط القديمة
  link.exit().remove();

  // إضافة روابط جديدة
  const linkEnter = link.enter()
    .append('path')
    .attr('class', 'link')
    .attr('d', (d: any) => {
      return linkGenerator({
        source: [source.y0 || source.y, source.x0 || source.x],
        target: [source.y0 || source.y, source.x0 || source.x]
      });
    });

  // تحديث جميع الروابط
  link.merge(linkEnter)
    .transition()
    .duration(500)
    .attr('d', (d: any) => {
      return linkGenerator({
        source: [d.source.y, d.source.x],
        target: [d.target.y, d.target.x]
      });
    });

  // تحديث العقد
  const node = svg.selectAll('.node')
    .data(nodes, (d: any) => d.data.id);

  // إزالة العقد القديمة
  node.exit().remove();

  // إضافة عقد جديدة
  const nodeEnter = node.enter()
    .append('g')
    .attr('class', 'node')
    .attr('transform', () => `translate(${source.y0 || source.y},${source.x0 || source.x})`)
    .on('click', (event: any, d: any) => toggleNode(event, d));

  // إضافة الدوائر للعقد الجديدة
  nodeEnter.append('circle')
    .attr('r', 10)
    .style('fill', d => d.data.gender === 'male' ? '#7CB9E8' : '#F4C2C2')
    .style('stroke', '#666')
    .style('stroke-width', '2px');

  // إضافة النصوص (الأسماء)
  const textNodes = nodeEnter.append('g')
    .attr('class', 'text-container');

  // إضافة خلفية بيضاء شفافة للنص
  textNodes.append('rect')
    .attr('class', 'text-background')
    .attr('x', d => {
      const hasChildren = findChildren(d.data.id).length > 0;
      const textWidth = d.data.name.length * 8; // تقدير تقريبي لعرض النص
      return hasChildren ? -textWidth - 13 : 13;
    })
    .attr('y', -10)
    .attr('width', d => d.data.name.length * 8) // تقدير تقريبي لعرض النص
    .attr('height', 20)
    .attr('rx', 4) // زوايا دائرية
    .attr('ry', 4);

  // إضافة النص
  textNodes.append('text')
    .attr('class', 'name-text')
    .attr('dy', '.35em')
    .attr('x', d => findChildren(d.data.id).length > 0 ? -13 : 13)
    .style('text-anchor', d => findChildren(d.data.id).length > 0 ? 'end' : 'start')
    .text(d => d.data.name);

  // تحديث جميع العقد
  const nodeUpdate = node.merge(nodeEnter);
  nodeUpdate.transition()
    .duration(500)
    .attr('transform', d => `translate(${d.y},${d.x})`);

  // تحديث حالة العقد (مفتوحة/مغلقة)
  nodeUpdate.select('circle')
    .style('fill', d => d.data.gender === 'male' ? '#7CB9E8' : '#F4C2C2')
    .attr('r', 10);

  // حفظ المواقع القديمة للحركة القادمة
  nodes.forEach(d => {
    d.x0 = d.x;
    d.y0 = d.y;
  });
}

// تحديث دالة renderTree لتحسين معالجة الأحداث
function renderTree() {
  if (!treeContainer.value || !props.treeData.length) return;

  // تنظيف الحاوية
  d3.select(treeContainer.value).selectAll('*').remove();

  const hierarchyData = prepareTreeData(props.treeData);
  if (!hierarchyData) return;

  // تحديد أبعاد الشجرة مع مساحة إضافية للأزرار
  const margin = isHorizontal.value 
    ? { top: 20, right: 120, bottom: 30, left: 120 }
    : { top: 50, right: 30, bottom: 50, left: 30 };
    
  const width = 1000 - margin.left - margin.right;
  const height = 800 - margin.top - margin.bottom;

  // إنشاء SVG بحجم أكبر
  const svg = d3.select(treeContainer.value)
    .append('svg')
    .attr('width', width + margin.right + margin.left)
    .attr('height', height + margin.top + margin.bottom)
    .append('g')
    .attr('transform', `translate(${margin.left},${margin.top})`);

  // إنشاء التخطيط الشجري مع دعم الاتجاه
  const treeLayout = d3.tree<TreeNodeWithChildren>()
    .size(isHorizontal.value ? [height, width] : [width, height])
    .separation((a, b) => {
      // زيادة المسافة بين العقد في العرض الرأسي
      return isHorizontal.value
        ? (a.parent === b.parent ? 1.5 : 2)
        : (a.parent === b.parent ? 2 : 2.5);
    });

  // تحويل البيانات إلى هيكل هرمي D3
  const root = d3.hierarchy<TreeNodeWithChildren>(hierarchyData);
  
  // حساب مواقع العقد
  const treeData = treeLayout(root);

  // إضافة مجموعة رئيسية للتحويلات
  const mainGroup = svg.append('g')
    .attr('transform', `translate(${transform.value.x},${transform.value.y}) scale(${scale.value})`);

  // إضافة الروابط (الخطوط)
  const linkGenerator = isHorizontal.value ? d3.linkHorizontal() : d3.linkVertical();
  
  mainGroup.selectAll('.link')
    .data(treeData.links())
    .enter()
    .append('path')
    .attr('class', 'link')
    .attr('d', (d: any) => {
      const sourceX = isHorizontal.value ? d.source.y : d.source.x;
      const sourceY = isHorizontal.value ? d.source.x : d.source.y;
      const targetX = isHorizontal.value ? d.target.y : d.target.x;
      const targetY = isHorizontal.value ? d.target.x : d.target.y;
      
      return isHorizontal.value
        ? `M${sourceX},${sourceY} C${(sourceX + targetX) / 2},${sourceY} ${(sourceX + targetX) / 2},${targetY} ${targetX},${targetY}`
        : `M${sourceX},${sourceY} C${sourceX},${(sourceY + targetY) / 2} ${targetX},${(sourceY + targetY) / 2} ${targetX},${targetY}`;
    })
    .style('fill', 'none')
    .style('stroke', '#ccc')
    .style('stroke-width', '2px');

  // إضافة العقد
  const nodes = mainGroup.selectAll('.node')
    .data(treeData.descendants())
    .enter()
    .append('g')
    .attr('class', 'node')
    .attr('transform', d => {
      if (isHorizontal.value) {
        return `translate(${d.y},${d.x})`;
      } else {
        // تعديل موضع العقد في العرض الرأسي
        return `translate(${d.x},${d.y})`;
      }
    });

  // إضافة الدوائر الرئيسية للعقد
  nodes.append('circle')
    .attr('r', 10)
    .style('fill', d => d.data.gender === 'male' ? '#7CB9E8' : '#F4C2C2')
    .style('stroke', '#666')
    .style('stroke-width', '2px');

  // إضافة أزرار التوسيع/الطي بموضع محسن
  nodes.each(function(d: any) {
    const hasChildren = findChildren(d.data.id).length > 0;
    if (hasChildren) {
      const isExpanded = nodeStates.value.get(d.data.id) ?? true;
      const toggleGroup = d3.select(this)
        .append('g')
        .attr('class', 'toggle-group')
        .attr('transform', () => {
          if (isHorizontal.value) {
            return 'translate(-40, 0)';
          } else {
            // تحسين موضع زر التوسيع في العرض الرأسي
            return 'translate(0, -30)';
          }
        });

      // إضافة خلفية دائرية للزر
      toggleGroup.append('circle')
        .attr('class', 'toggle-background')
        .attr('r', 12)
        .style('fill', '#f8f8f8')
        .style('stroke', '#666')
        .style('stroke-width', '1.5px')
        .style('cursor', 'pointer')
        .on('click', (event) => {
          event.stopPropagation();
          const currentState = nodeStates.value.get(d.data.id) ?? true;
          nodeStates.value.set(d.data.id, !currentState);
          renderTree();
        });

      // إضافة علامة + أو -
      toggleGroup.append('text')
        .attr('class', 'toggle-icon')
        .attr('dy', '0.35em')
        .attr('text-anchor', 'middle')
        .style('font-size', '16px')
        .style('font-weight', 'bold')
        .style('fill', '#666')
        .style('pointer-events', 'none')
        .style('user-select', 'none')
        .text(isExpanded ? '−' : '+');
    }
  });

  // إضافة مجموعة النص بموضع محسن
  const textGroups = nodes
    .append('g')
    .attr('class', 'text-container')
    .attr('transform', (d: any) => {
      if (isHorizontal.value) {
        return 'translate(20, 0)';
      } else {
        // تحسين موضع النص في العرض الرأسي
        const hasChildren = findChildren(d.data.id).length > 0;
        return hasChildren ? 'translate(0, 25)' : 'translate(0, 25)';
      }
    })
    .style('cursor', 'pointer')
    .on('click', (event, d) => {
      event.stopPropagation();
      emit('node-click', d.data);
    });

  // إضافة خلفية النص
  textGroups.append('rect')
    .attr('class', 'text-background')
    .attr('x', (d: any) => {
      const textWidth = d.data.name.length * 9 + 10;
      return isHorizontal.value ? -5 : -textWidth / 2;
    })
    .attr('y', -12)
    .attr('width', d => d.data.name.length * 9 + 10)
    .attr('height', 24)
    .attr('rx', 4)
    .attr('ry', 4);

  // إضافة النص
  textGroups.append('text')
    .attr('class', 'name-text')
    .attr('dy', '.35em')
    .attr('text-anchor', isHorizontal.value ? 'start' : 'middle')
    .text(d => d.data.name);

  // تحديث الخريطة المصغرة
  updateMinimap();
}

// مراقبة التغييرات في البيانات
watch(() => props.treeData, () => {
  nodeStates.value.clear(); // إعادة تعيين حالة العقد عند تغيير البيانات
  renderTree();
}, { deep: true });

// تهيئة الشجرة عند تحميل المكون
onMounted(() => {
  // إضافة خط Noto Sans Arabic
  const link = document.createElement('link');
  link.href = 'https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600&display=swap';
  link.rel = 'stylesheet';
  document.head.appendChild(link);
  
  renderTree();
});

// دالة التكبير
function zoomIn() {
  if (scale.value >= 2) return;
  scale.value = Math.min(2, scale.value + 0.1);
  updateTreeTransform();
}

// دالة التصغير
function zoomOut() {
  if (scale.value <= 0.5) return;
  scale.value = Math.max(0.5, scale.value - 0.1);
  updateTreeTransform();
}

// دالة إعادة التعيين
function resetZoom() {
  scale.value = 1;
  transform.value = { x: 0, y: 0 };
  updateTreeTransform();
}

// معالجة حركة عجلة الماوس
function handleWheel(event: WheelEvent) {
  if (event.ctrlKey || event.metaKey) {
    // التكبير/التصغير مع مفتاح Control
    const delta = -event.deltaY;
    const newScale = Math.max(0.5, Math.min(2, scale.value + delta * 0.001));
    if (newScale !== scale.value) {
      scale.value = newScale;
      updateTreeTransform();
    }
  } else {
    // التحريك العادي
    transform.value.x -= event.deltaX;
    transform.value.y -= event.deltaY;
    updateTreeTransform();
  }
}

// تحديث تحويل الشجرة
function updateTreeTransform() {
  if (!treeContainer.value) return;
  
  const svg = d3.select(treeContainer.value).select('svg g');
  svg.attr('transform', `translate(${transform.value.x},${transform.value.y}) scale(${scale.value})`);
  
  // تحديث الخريطة المصغرة
  updateMinimap();
}

// إنشاء وتحديث الخريطة المصغرة
function updateMinimap() {
  if (!minimapContainer.value || !treeContainer.value) return;

  const minimap = d3.select(minimapContainer.value);
  minimap.selectAll('*').remove();

  // إنشاء نسخة مصغرة من الشجرة
  const minimapSvg = minimap.append('svg')
    .attr('width', '150')
    .attr('height', '100')
    .style('background-color', '#f5f5f5')
    .style('border', '1px solid #ddd');

  // نسخ الشجرة الرئيسية بحجم مصغر
  const mainSvg = d3.select(treeContainer.value).select('svg g');
  const clone = mainSvg.node()?.cloneNode(true);
  if (clone) {
    minimapSvg.node()?.appendChild(clone);
    
    // تصغير النسخة لتناسب الخريطة المصغرة
    const bounds = (mainSvg.node() as SVGGElement)?.getBBox();
    if (bounds) {
      const scale = Math.min(150 / bounds.width, 100 / bounds.height) * 0.9;
      d3.select(clone)
        .attr('transform', `scale(${scale}) translate(${-bounds.x},${-bounds.y})`);
    }
  }
}

// دالة تبديل اتجاه الشجرة
function toggleOrientation() {
  isHorizontal.value = !isHorizontal.value;
  renderTree();
}
</script>

<style scoped>
.d3-family-tree {
  width: 100%;
  height: 100%;
  min-height: 800px;
  position: relative;
  background-color: #ffffff;
}

.tree-container {
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.zoom-controls {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background-color: white;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.minimap {
  position: absolute;
  bottom: 20px;
  right: 20px;
  width: 150px;
  height: 100px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

:deep(.node) {
  cursor: pointer;
}

:deep(.toggle-background) {
  transition: all 0.2s ease;
}

:deep(.toggle-background:hover) {
  fill: #eeeeee;
  stroke-width: 2px;
}

:deep(.toggle-group) {
  cursor: pointer;
}

:deep(.text-background) {
  fill: rgba(255, 255, 255, 0.95);
  stroke: #e0e0e0;
  stroke-width: 1px;
  transition: all 0.2s ease;
}

:deep(.name-text) {
  font-size: 14px;
  font-family: 'Noto Sans Arabic', sans-serif;
  font-weight: 500;
  fill: #333;
  transition: all 0.2s ease;
}

:deep(.text-container:hover .text-background) {
  fill: rgba(255, 255, 255, 1);
  stroke: #ccc;
  stroke-width: 1.5px;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

:deep(.text-container:hover .name-text) {
  font-weight: 600;
}

:deep(.link) {
  fill: none;
  stroke: #ccc;
  stroke-width: 1.5px;
  transition: all 0.2s ease;
}

:deep(.link:hover) {
  stroke: #999;
  stroke-width: 2px;
}
</style> 