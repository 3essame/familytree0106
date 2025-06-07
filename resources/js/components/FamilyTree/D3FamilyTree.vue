<template>
  <div class="d3-family-tree">
    <div class="zoom-controls">
      <v-btn
        icon="mdi-plus"
        size="small"
        variant="outlined"
        class="mb-2"
        @click="handleZoomIn"
        :disabled="scale >= 2"
      />
      <v-btn
        icon="mdi-minus"
        size="small"
        variant="outlined"
        class="mb-2"
        @click="handleZoomOut"
        :disabled="scale <= 0.5"
      />
      <v-btn
        icon="mdi-fit-to-screen"
        size="small"
        variant="outlined"
        @click="handleResetZoom"
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
    <div ref="treeContainer" class="tree-container" 
      @wheel.prevent="handleWheel"
      v-touch="{
        start: touchStart,
        move: touchMove,
        end: touchEnd,
        pinch: handlePinch
      }"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted, computed } from 'vue';
import * as d3 from 'd3';
import type { TreeNode } from '../../types/family-tree';
import { useDisplay } from 'vuetify';

const props = defineProps<{
  treeData: TreeNode[];
}>();

// تعريف الأحداث
const emit = defineEmits<{
  (e: 'node-click', node: TreeNode): void;
}>();

// Responsive helpers
const display = useDisplay();
const isMobile = computed(() => display.mdAndDown.value);

const treeContainer = ref<HTMLElement | null>(null);
const minimapContainer = ref<HTMLElement | null>(null);
const scale = ref(1);
const transform = ref({ x: 0, y: 0 });
const isLoading = ref(false);
const error = ref<string | null>(null);
const zoom = ref(1);
const containerWidth = ref(0);
const containerHeight = ref(0);

// Touch interaction variables
const touchStartX = ref(0);
const touchStartY = ref(0);
const initialScale = ref(1);
const initialDistance = ref(0);

// إضافة متغير لتتبع اتجاه الشجرة
const isHorizontal = ref(true);

// ResizeObserver instance
let resizeObserver: ResizeObserver | null = null;

interface TreeNodeWithChildren extends TreeNode {
  children: TreeNodeWithChildren[];
  _children?: TreeNodeWithChildren[]; // للعقد المخفية
}

interface ExtendedHierarchyNode extends d3.HierarchyNode<TreeNodeWithChildren> {
  x0?: number;
  y0?: number;
  _children?: TreeNodeWithChildren[];
}

// Add interface for link objects
interface TreeLink {
  source: [number, number];
  target: [number, number];
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

// إضافة دالة لمعالجة تغيير حجم الحاوية
function handleResize() {
  if (treeContainer.value) {
    containerWidth.value = treeContainer.value.offsetWidth;
    containerHeight.value = treeContainer.value.offsetHeight;
    renderTree(); // إعادة رسم الشجرة عند تغيير الحجم
  }
}

// تحديث دالة onMounted لتشغيل ResizeObserver
onMounted(() => {
  if (treeContainer.value) {
    // تشغيل ResizeObserver
    resizeObserver = new ResizeObserver(handleResize);
    resizeObserver.observe(treeContainer.value);
    
    // القياس الأولي للحاوية والرسم
    handleResize();
  }
});

// إضافة onUnmounted لتنظيف ResizeObserver
onUnmounted(() => {
  if (resizeObserver && treeContainer.value) {
    resizeObserver.unobserve(treeContainer.value);
    resizeObserver.disconnect();
  }
});

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
        source: [source.y0 || source.y, source.x0 || source.x]
      } as any);
    });

  // تحديث جميع الروابط
  link.merge(linkEnter as any)
    .transition()
    .duration(500)
    .attr('d', (d: any) => {
      return linkGenerator({
        source: [d.source.y, d.source.x],
        target: [d.target.y, d.target.x]
      } as any);
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
  const nodeUpdate = node.merge(nodeEnter as any);
  nodeUpdate.transition()
    .duration(500)
    .attr('transform', d => `translate(${d.y},${d.x})`);

  // تحديث حالة العقد (مفتوحة/مغلقة)
  nodeUpdate.select('circle')
    .style('fill', d => d.data.gender === 'male' ? '#7CB9E8' : '#F4C2C2')
    .attr('r', 10);

  // حفظ المواقع القديمة للحركة القادمة
  nodes.forEach((d: any) => {
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

  // تحديد أبعاد الشجرة بناءً على حجم الحاوية
  const margin = isHorizontal.value 
    ? { 
        top: 20, 
        right: isMobile.value ? 60 : 120, 
        bottom: 30, 
        left: isMobile.value ? 60 : 120 
      }
    : { 
        top: isMobile.value ? 80 : 50, 
        right: 30, 
        bottom: isMobile.value ? 80 : 50, 
        left: 30 
      };
    
  // استخدام أبعاد الحاوية الفعلية - مع ضبط القيم الافتراضية لمنع التمدد المفرط
  const width = containerWidth.value ? containerWidth.value - margin.left - margin.right : 300;
  const height = containerHeight.value ? containerHeight.value - margin.top - margin.bottom : 400;

  // إنشاء SVG بحجم مناسب
  const svg = d3.select(treeContainer.value)
    .append('svg')
    .attr('width', containerWidth.value || 400)
    .attr('height', containerHeight.value || 500)
    .append('g')
    .attr('transform', `translate(${margin.left},${margin.top})`);

  // تحديد عمق الشجرة - مهم للهواتف
  const maxDepth = isMobile.value ? 2 : 5; // تقييد عمق الشجرة على الهواتف
  const treeNodes = limitTreeDepth(hierarchyData, maxDepth);

  // إنشاء التخطيط الشجري مع دعم الاتجاه
  const treeLayout = d3.tree<TreeNodeWithChildren>()
    .size(isHorizontal.value ? [height, width] : [width, height])
    .separation((a, b) => {
      // ضبط المسافة بين العقد بناءً على الجهاز
      return isHorizontal.value
        ? (a.parent === b.parent ? (isMobile.value ? 1.2 : 1.5) : (isMobile.value ? 1.5 : 2))
        : (a.parent === b.parent ? (isMobile.value ? 1.2 : 2) : (isMobile.value ? 1.5 : 2.5));
    });

  // تحويل البيانات إلى هيكل هرمي D3
  const root = d3.hierarchy<TreeNodeWithChildren>(treeNodes);
  
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
      
      // تعديل منحنى الخط بناءً على نوع الجهاز
      const curveFactor = isMobile.value ? 0.3 : 0.5;
      
      return isHorizontal.value
        ? `M${sourceX},${sourceY} C${sourceX + (targetX - sourceX) * curveFactor},${sourceY} ${sourceX + (targetX - sourceX) * (1 - curveFactor)},${targetY} ${targetX},${targetY}`
        : `M${sourceX},${sourceY} C${sourceX},${sourceY + (targetY - sourceY) * curveFactor} ${targetX},${sourceY + (targetY - sourceY) * (1 - curveFactor)} ${targetX},${targetY}`;
    })
    .style('fill', 'none')
    .style('stroke', '#ccc')
    .style('stroke-width', isMobile.value ? '1.5px' : '2px');

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

  // إضافة الدوائر الرئيسية للعقد - تغيير حجم الدوائر حسب الجهاز
  nodes.append('circle')
    .attr('r', isMobile.value ? 8 : 10)
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
            // تعديل موضع الزر حسب الجهاز
            return isMobile.value ? 'translate(-30, 0)' : 'translate(-40, 0)';
          } else {
            // تحسين موضع زر التوسيع في العرض الرأسي
            return isMobile.value ? 'translate(0, -25)' : 'translate(0, -30)';
          }
        });

      // إضافة خلفية دائرية للزر مع تعديل الحجم حسب الجهاز
      toggleGroup.append('circle')
        .attr('class', 'toggle-background')
        .attr('r', isMobile.value ? 10 : 12)
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

      // إضافة علامة + أو - مع تعديل الحجم حسب الجهاز
      toggleGroup.append('text')
        .attr('class', 'toggle-icon')
        .attr('dy', '0.35em')
        .attr('text-anchor', 'middle')
        .style('font-size', isMobile.value ? '14px' : '16px')
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
        // تعديل موضع النص حسب الجهاز في العرض الأفقي
        return isMobile.value ? 'translate(15, 0)' : 'translate(20, 0)';
      } else {
        // تحسين موضع النص في العرض الرأسي
        const hasChildren = findChildren(d.data.id).length > 0;
        return isMobile.value ? 'translate(0, 20)' : 'translate(0, 25)';
      }
    })
    .style('cursor', 'pointer')
    .on('click', (event, d) => {
      event.stopPropagation();
      emit('node-click', d.data);
    });

  // إضافة خلفية النص مع تعديل العرض والارتفاع حسب الجهاز
  textGroups.append('rect')
    .attr('class', 'text-background')
    .attr('x', (d: any) => {
      // تعديل عرض المستطيل حسب طول النص والجهاز
      const textWidth = isMobile.value 
        ? d.data.name.length * 7 + 10  // أقل عرض للهواتف
        : d.data.name.length * 9 + 10;
      return isHorizontal.value ? -5 : -textWidth / 2;
    })
    .attr('y', -12)
    .attr('width', d => {
      // تعديل عرض المستطيل حسب طول النص والجهاز
      return isMobile.value 
        ? Math.min(d.data.name.length * 7 + 10, 100)  // تحديد الحد الأقصى للعرض
        : d.data.name.length * 9 + 10;
    })
    .attr('height', isMobile.value ? 22 : 24)
    .attr('rx', 4)
    .attr('ry', 4);

  // إضافة النص مع تعديل الحجم حسب الجهاز
  textGroups.append('text')
    .attr('class', 'name-text')
    .attr('dy', '.35em')
    .attr('text-anchor', isHorizontal.value ? 'start' : 'middle')
    .style('font-size', isMobile.value ? '12px' : '14px')
    .text(d => {
      // اختصار النص إذا كان طويلاً جدًا على الهواتف
      if (isMobile.value && d.data.name.length > 12) {
        return d.data.name.substring(0, 12) + '...';
      }
      return d.data.name;
    });

  // تحديث الخريطة المصغرة
  updateMinimap();

  // بعد الرسم، قم بضبط المقياس للهواتف تلقائيًا
  if (isMobile.value) {
    // تعيين مقياس أصغر للهواتف
    scale.value = 0.8;
    // تعيين موضع مناسب في المنتصف
    transform.value = { 
      x: margin.left,
      y: margin.top
    };
    updateTreeTransform();
  }
}

// وظيفة جديدة لتقييد عمق الشجرة للهواتف
function limitTreeDepth(node: TreeNodeWithChildren, maxDepth: number, currentDepth: number = 0): TreeNodeWithChildren {
  if (currentDepth >= maxDepth) {
    return { ...node, children: [] };
  }
  
  return {
    ...node,
    children: node.children.map(child => limitTreeDepth(child, maxDepth, currentDepth + 1))
  };
}

// إنشاء وتحديث الخريطة المصغرة
function updateMinimap() {
  if (!minimapContainer.value) return;
  
  const minimap = d3.select(minimapContainer.value);
  minimap.selectAll('*').remove();

  // إنشاء نسخة مصغرة من الشجرة
  const minimapSvg = minimap.append('svg')
    .attr('width', '150')
    .attr('height', '100')
    .style('background-color', '#f5f5f5')
    .style('border', '1px solid #ddd');

  // Instead of cloning, create a simplified representation
  if (treeContainer.value) {
    const mainSvg = d3.select(treeContainer.value).select('svg g');
    const mainSvgElement = mainSvg.node() as SVGGElement;
    
    if (mainSvgElement) {
      try {
        const bounds = mainSvgElement.getBBox();
        const scale = Math.min(150 / bounds.width, 100 / bounds.height) * 0.9;
        
        // Create a rectangle to represent the viewport
        minimapSvg.append('rect')
          .attr('width', bounds.width * scale)
          .attr('height', bounds.height * scale)
          .attr('x', -bounds.x * scale)
          .attr('y', -bounds.y * scale)
          .style('fill', 'rgba(200, 200, 200, 0.3)')
          .style('stroke', '#888')
          .style('stroke-width', '1px');
      } catch (e) {
        console.error('Error creating minimap:', e);
      }
    }
  }
}

// دالة تبديل اتجاه الشجرة
function toggleOrientation() {
  isHorizontal.value = !isHorizontal.value;
  renderTree();
}

// دالة للتحقق من حالة البيانات
function checkDataState() {
  console.log('Current D3 tree state:', {
    treeData: props.treeData,
    loading: isLoading.value,
    error: error.value,
    zoom: zoom.value,
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

// Touch event handlers
function touchStart(event: TouchEvent) {
  if (event.touches.length === 1) {
    touchStartX.value = event.touches[0].clientX - transform.value.x;
    touchStartY.value = event.touches[0].clientY - transform.value.y;
  } else if (event.touches.length === 2) {
    // Store initial scale for pinch zoom
    initialScale.value = scale.value;
    initialDistance.value = getDistance(
      event.touches[0].clientX, event.touches[0].clientY,
      event.touches[1].clientX, event.touches[1].clientY
    );
  }
}

function touchMove(event: TouchEvent) {
  if (event.touches.length === 1) {
    transform.value.x = event.touches[0].clientX - touchStartX.value;
    transform.value.y = event.touches[0].clientY - touchStartY.value;
    updateTreeTransform();
  }
}

function touchEnd() {
  // No specific action needed on touch end
}

function handlePinch(event: TouchEvent) {
  if (event.touches.length !== 2) return;
  
  const currentDistance = getDistance(
    event.touches[0].clientX, event.touches[0].clientY,
    event.touches[1].clientX, event.touches[1].clientY
  );
  
  const newScale = Math.max(0.5, Math.min(2, initialScale.value * (currentDistance / initialDistance.value)));
  if (newScale !== scale.value) {
    scale.value = newScale;
    updateTreeTransform();
  }
}

function getDistance(x1: number, y1: number, x2: number, y2: number): number {
  return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
}

// تعديل وظيفة تحديث تحويل الشجرة
function updateTreeTransform() {
  if (!treeContainer.value) return;
  
  const svg = d3.select(treeContainer.value).select('svg g');
  svg.attr('transform', `translate(${transform.value.x},${transform.value.y}) scale(${scale.value})`);
  
  // تحديث الخريطة المصغرة
  updateMinimap();
}

// دالة التكبير - معدلة للهواتف
function handleZoomIn() {
  if (scale.value >= 2) return;
  // تكبير بمقدار أقل على الهواتف
  const zoomStep = isMobile.value ? 0.05 : 0.1;
  scale.value = Math.min(2, scale.value + zoomStep);
  updateTreeTransform();
}

// دالة التصغير - معدلة للهواتف
function handleZoomOut() {
  if (scale.value <= 0.5) return;
  // تصغير بمقدار أقل على الهواتف
  const zoomStep = isMobile.value ? 0.05 : 0.1;
  scale.value = Math.max(0.5, scale.value - zoomStep);
  updateTreeTransform();
}

// دالة إعادة التعيين - معدلة للهواتف
function handleResetZoom() {
  // مقياس أصغر للهواتف
  scale.value = isMobile.value ? 0.8 : 1;
  // موضع مركزي للهواتف
  if (isMobile.value) {
    transform.value = { x: 60, y: 20 };
  } else {
    transform.value = { x: 0, y: 0 };
  }
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

// تهيئة الشجرة عند تحميل المكون
onMounted(() => {
  // إضافة خط Noto Sans Arabic
  const link = document.createElement('link');
  link.href = 'https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600&display=swap';
  link.rel = 'stylesheet';
  document.head.appendChild(link);
  
  renderTree();
});
</script>

<style scoped>
.d3-family-tree {
  width: 100%;
  height: 100%;
  min-height: 600px;
  position: relative;
  background-color: #ffffff;
  touch-action: none; /* Prevent browser handling of touch events */
  margin-top: 10px; /* إضافة مسافة من الأعلى لتجنب التداخل مع الشريط العلوي */
}

.tree-container {
  width: 100%;
  height: 100%;
  overflow: hidden;
  touch-action: none;
}

.zoom-controls {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 900; /* تأكد من أن قيمة z-index أقل من الشريط العلوي */
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
  z-index: 900; /* تأكد من أن قيمة z-index أقل من الشريط العلوي */
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .d3-family-tree {
    min-height: 500px;
    margin-top: 15px; /* مسافة أكبر للشاشات الصغيرة */
  }
  
  .zoom-controls {
    top: 10px;
    right: 10px;
    padding: 6px;
  }
  
  .minimap {
    bottom: 10px;
    right: 10px;
    width: 100px;
    height: 70px;
  }
  
  :deep(.node) {
    touch-action: none;
  }
  
  :deep(.name-text) {
    font-size: 12px;
  }
  
  :deep(.text-background) {
    rx: 3px;
    ry: 3px;
  }
  
  :deep(.toggle-background) {
    cursor: pointer;
    touch-action: none;
  }
  
  :deep(.link) {
    stroke-width: 1.5px;
  }
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