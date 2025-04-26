<template>
  <div class="person-card" :class="{ 'is-male': person.gender === 'male', 'is-female': person.gender === 'female' }">
    <div class="person-header">
      <h3 class="person-name">{{ person.name }}</h3>
      <div class="person-gender">
        <i :class="person.gender === 'male' ? 'fas fa-male' : 'fas fa-female'"></i>
      </div>
    </div>
    
    <div class="person-details">
      <div class="detail-item" v-if="person.birth_date">
        <i class="fas fa-birthday-cake"></i>
        <span>{{ formatDate(person.birth_date) }}</span>
      </div>
      
      <div class="detail-item" v-if="person.death_date">
        <i class="fas fa-cross"></i>
        <span>{{ formatDate(person.death_date) }}</span>
      </div>

      <div class="marriage-info" v-if="currentMarriage">
        <div class="detail-item">
          <i class="fas fa-ring"></i>
          <span>{{ formatDate(currentMarriage.marriage_date) }}</span>
        </div>
      </div>
    </div>

    <div class="person-actions">
      <button @click="$emit('edit', person)" class="btn-edit">
        <i class="fas fa-edit"></i>
      </button>
      <button @click="$emit('view-details', person)" class="btn-view">
        <i class="fas fa-info-circle"></i>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    person: {
      type: Object,
      required: true
    },
    currentMarriage: {
      type: Object,
      default: null
    }
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }
  }
}
</script>

<style scoped>
.person-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.person-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.person-card.is-male {
  border-right: 4px solid #4a90e2;
}

.person-card.is-female {
  border-right: 4px solid #e24a90;
}

.person-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.person-name {
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0;
  color: #2c3e50;
}

.person-gender {
  font-size: 1.5rem;
  color: #666;
}

.person-details {
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  color: #666;
}

.detail-item i {
  width: 20px;
  text-align: center;
}

.person-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-edit, .btn-view {
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: #666;
  transition: color 0.3s ease;
}

.btn-edit:hover {
  color: #4a90e2;
}

.btn-view:hover {
  color: #2c3e50;
}

.marriage-info {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #eee;
}
</style> 