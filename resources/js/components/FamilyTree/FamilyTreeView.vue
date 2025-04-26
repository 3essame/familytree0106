<template>
  <div class="family-tree-container">
    <div class="tree-controls">
      <button @click="zoomIn" class="btn-control">
        <i class="fas fa-search-plus"></i>
      </button>
      <button @click="zoomOut" class="btn-control">
        <i class="fas fa-search-minus"></i>
      </button>
      <button @click="resetView" class="btn-control">
        <i class="fas fa-sync"></i>
      </button>
    </div>

    <div class="tree-view" ref="treeView">
      <div class="tree-level" v-for="(level, index) in treeLevels" :key="index">
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

    <!-- Modal for person details -->
    <div class="modal" v-if="selectedPerson" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ selectedPerson.name }}</h2>
          <button @click="closeModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="person-details">
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
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import PersonCard from './PersonCard.vue';

export default {
  components: {
    PersonCard
  },
  props: {
    familyData: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      zoomLevel: 1,
      selectedPerson: null,
      treeLevels: []
    }
  },
  methods: {
    zoomIn() {
      this.zoomLevel = Math.min(this.zoomLevel + 0.1, 2);
      this.updateZoom();
    },
    zoomOut() {
      this.zoomLevel = Math.max(this.zoomLevel - 0.1, 0.5);
      this.updateZoom();
    },
    resetView() {
      this.zoomLevel = 1;
      this.updateZoom();
    },
    updateZoom() {
      this.$refs.treeView.style.transform = `scale(${this.zoomLevel})`;
    },
    getCurrentMarriage(person) {
      return person.marriages?.find(m => m.status === 'active');
    },
    hasChildren(person) {
      return person.children?.length > 0;
    },
    handleEdit(person) {
      this.$emit('edit', person);
    },
    handleViewDetails(person) {
      this.selectedPerson = person;
    },
    closeModal() {
      this.selectedPerson = null;
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    organizeTreeLevels() {
      // Organize family members into levels based on their relationships
      const levels = [];
      const processed = new Set();

      // Start with the oldest generation
      const oldestGeneration = this.familyData.filter(person => 
        !person.father && !person.mother
      );
      levels.push(oldestGeneration);
      oldestGeneration.forEach(person => processed.add(person.id));

      // Process subsequent generations
      let currentLevel = oldestGeneration;
      while (currentLevel.length > 0) {
        const nextLevel = [];
        currentLevel.forEach(person => {
          const children = this.familyData.filter(child =>
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

      this.treeLevels = levels;
    }
  },
  mounted() {
    this.organizeTreeLevels();
  },
  watch: {
    familyData: {
      handler() {
        this.organizeTreeLevels();
      },
      deep: true
    }
  }
}
</script>

<style scoped>
.family-tree-container {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: #f5f5f5;
  padding: 2rem;
}

.tree-controls {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  gap: 0.5rem;
  z-index: 10;
}

.btn-control {
  background: white;
  border: none;
  border-radius: 4px;
  padding: 0.5rem;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}

.btn-control:hover {
  background: #f0f0f0;
  transform: translateY(-1px);
}

.tree-view {
  transform-origin: top center;
  transition: transform 0.3s ease;
}

.tree-level {
  display: flex;
  justify-content: center;
  margin-bottom: 2rem;
}

.tree-nodes {
  display: flex;
  gap: 2rem;
  position: relative;
}

.tree-node {
  position: relative;
}

.node-connections {
  position: absolute;
  bottom: -2rem;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  height: 2rem;
  background: #ccc;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 1rem;
}

.detail-section {
  margin-bottom: 1.5rem;
}

.detail-section h3 {
  margin-bottom: 1rem;
  color: #2c3e50;
}

.detail-item, .relationship-item {
  display: flex;
  margin-bottom: 0.5rem;
}

.label {
  font-weight: 600;
  margin-left: 0.5rem;
  color: #666;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #666;
}

.btn-close:hover {
  color: #333;
}
</style> 