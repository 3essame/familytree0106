<template>
  <div class="family-tree-container">
    <!-- Tree Controls -->
    <div class="tree-controls">
      <v-btn-group class="control-group">
        <v-btn
          icon="mdi-plus"
          @click="zoomIn"
          class="control-btn"
          :size="isMobile ? 'large' : 'default'"
        ></v-btn>
        <v-btn
          icon="mdi-minus"
          @click="zoomOut"
          class="control-btn"
          :size="isMobile ? 'large' : 'default'"
        ></v-btn>
        <v-btn
          icon="mdi-refresh"
          @click="resetView"
          class="control-btn"
          :size="isMobile ? 'large' : 'default'"
        ></v-btn>
      </v-btn-group>
    </div>

    <!-- Tree View with Touch Support -->
    <div 
      class="tree-view" 
      ref="treeView"
      v-touch="{
        start: touchStart,
        move: touchMove,
        end: touchEnd
      }"
    >
      <div 
        class="tree-content"
        :style="{
          transform: `scale(${zoomLevel}) translate(${panX}px, ${panY}px)`,
          touchAction: 'none'
        }"
      >
        <div 
          class="tree-level" 
          v-for="(level, index) in treeLevels" 
          :key="index"
        >
          <div class="tree-nodes">
            <div v-for="person in level" :key="person.id" class="tree-node">
              <PersonCard
                :person="person"
                :current-marriage="getCurrentMarriage(person)"
                @edit="handleEdit"
                @view-details="handleViewDetails"
              />
              
              <div class="node-connections" v-if="index < treeLevels.length - 1">
                <div class="connection-line" v-if="hasChildren(person)"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Responsive Modal -->
    <v-dialog
      v-model="showModal"
      :fullscreen="isMobile"
      :width="isMobile ? '100%' : '600'"
      transition="dialog-bottom-transition"
    >
      <v-card v-if="selectedPerson">
        <v-toolbar
          dark
          color="primary"
        >
          <v-btn
            icon
            @click="closeModal"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
          <v-toolbar-title>{{ selectedPerson.name }}</v-toolbar-title>
        </v-toolbar>

        <v-card-text>
          <v-container class="person-details">
            <div class="detail-section">
              <h3>المعلومات الأساسية</h3>
              <div class="detail-item">
                <span class="label">تاريخ الميلاد:</span>
                <span>{{ formatDate(selectedPerson.birth_date) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">الجنس:</span>
                <span>{{ selectedPerson.gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
              </div>
            </div>

            <div class="detail-section">
              <h3>العلاقات العائلية</h3>
              <div class="relationships">
                <div v-if="selectedPerson.father" class="relationship-item">
                  <span class="label">الأب:</span>
                  <span>{{ selectedPerson.father.name }}</span>
                </div>
                <div v-if="selectedPerson.mother" class="relationship-item">
                  <span class="label">الأم:</span>
                  <span>{{ selectedPerson.mother.name }}</span>
                </div>
                <div v-if="selectedPerson.spouse" class="relationship-item">
                  <span class="label">الزوج/ة:</span>
                  <span>{{ selectedPerson.spouse.name }}</span>
                </div>
              </div>
            </div>
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDisplay } from 'vuetify';
import PersonCard from './PersonCard.vue';

// Responsive helpers
const display = useDisplay();
const isMobile = computed(() => display.mdAndDown.value);

// Touch handling
const touchStartX = ref(0);
const touchStartY = ref(0);
const panX = ref(0);
const panY = ref(0);
const zoomLevel = ref(1);

const touchStart = (event) => {
  touchStartX.value = event.touches[0].clientX - panX.value;
  touchStartY.value = event.touches[0].clientY - panY.value;
};

const touchMove = (event) => {
  panX.value = event.touches[0].clientX - touchStartX.value;
  panY.value = event.touches[0].clientY - touchStartY.value;
};

const touchEnd = () => {
  // Save final position
};

// Zoom controls
const zoomIn = () => {
  zoomLevel.value = Math.min(zoomLevel.value + 0.1, 2);
};

const zoomOut = () => {
  zoomLevel.value = Math.max(zoomLevel.value - 0.1, 0.5);
};

const resetView = () => {
  zoomLevel.value = 1;
  panX.value = 0;
  panY.value = 0;
};

const getCurrentMarriage = (person) => {
  return person.marriages?.find(m => m.status === 'active');
};

const hasChildren = (person) => {
  return person.children?.length > 0;
};

const handleEdit = (person) => {
  emit('edit', person);
};

const handleViewDetails = (person) => {
  selectedPerson.value = person;
};

const closeModal = () => {
  selectedPerson.value = null;
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ar-SA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const organizeTreeLevels = () => {
  // Organize family members into levels based on their relationships
  const levels = [];
  const processed = new Set();

  // Start with the oldest generation
  const oldestGeneration = familyData.value.filter(person => 
    !person.father && !person.mother
  );
  levels.push(oldestGeneration);
  oldestGeneration.forEach(person => processed.add(person.id));

  // Process subsequent generations
  let currentLevel = oldestGeneration;
  while (currentLevel.length > 0) {
    const nextLevel = [];
    currentLevel.forEach(person => {
      const children = familyData.value.filter(child =>
        (child.father_id === person.id || child.mother_id === person.id) &&
        !processed.has(child.id)
      );
      nextLevel.push(...children);
      children.forEach(child => processed.add(child.id));
    });
    if (nextLevel.length > 0) {
      levels.push(nextLevel);
    }
    currentLevel = nextLevel;
  }

  treeLevels.value = levels;
};

onMounted(() => {
  organizeTreeLevels();
});

watch(familyData, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    organizeTreeLevels();
  }
}, { deep: true });
</script>

<style>
.family-tree-container {
  position: relative;
  width: 100%;
  height: 100vh;
  overflow: hidden;
}

.tree-controls {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}

.control-group {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.control-btn {
  margin: 4px;
}

.tree-view {
  width: 100%;
  height: 100%;
  overflow: hidden;
  position: relative;
}

.tree-content {
  transform-origin: 0 0;
  will-change: transform;
}

/* Responsive styles */
@media (max-width: 768px) {
  .tree-controls {
    bottom: 16px;
    right: 16px;
  }

  .control-btn {
    width: 48px;
    height: 48px;
  }

  .tree-node {
    min-width: 200px;
    margin: 8px;
  }
}

/* Touch-friendly styles */
@media (hover: none) {
  .tree-node {
    padding: 16px;
  }

  .control-btn {
    padding: 12px;
  }
}
</style>