<template>
  <div 
    class="person-card" 
    :class="{
      'is-male': person.gender === 'male',
      'is-female': person.gender === 'female',
      'touch-device': isTouchDevice
    }"
  >
    <div class="card-content">
      <!-- Header -->
      <div class="person-header">
        <div class="avatar" :class="person.gender">
          <img 
            :src="person.gender === 'male' ? '/images/male.png' : '/images/female.png'"
            :alt="person.gender"
          >
        </div>
        <h3 class="person-name" :class="{ 'mobile-text': isMobile }">
          {{ person.name }}
        </h3>
      </div>

      <!-- Details -->
      <div class="person-details">
        <div v-if="person.birth_date" class="detail-row">
          <v-icon size="small">mdi-cake-variant</v-icon>
          <span>{{ formatDate(person.birth_date) }}</span>
        </div>

        <div v-if="person.death_date" class="detail-row">
          <v-icon size="small">mdi-cross</v-icon>
          <span>{{ formatDate(person.death_date) }}</span>
        </div>

        <div v-if="currentMarriage?.marriage_date" class="detail-row">
          <v-icon size="small">mdi-ring</v-icon>
          <span>{{ formatDate(currentMarriage.marriage_date) }}</span>
        </div>
      </div>

      <!-- Actions -->
      <div class="person-actions">
        <v-btn
          icon
          variant="text"
          :size="isMobile ? 'large' : 'default'"
          @click="$emit('edit', person)"
          class="action-btn"
        >
          <v-icon>mdi-pencil</v-icon>
        </v-btn>
        <v-btn
          icon
          variant="text"
          :size="isMobile ? 'large' : 'default'"
          @click="$emit('view-details', person)"
          class="action-btn"
        >
          <v-icon>mdi-information</v-icon>
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDisplay } from 'vuetify';

const props = defineProps({
  person: {
    type: Object,
    required: true
  },
  currentMarriage: {
    type: Object,
    default: null
  }
});

const display = useDisplay();
const isMobile = computed(() => display.mdAndDown.value);
const isTouchDevice = ref(false);

onMounted(() => {
  isTouchDevice.value = 'ontouchstart' in window;
});

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('ar-SA');
};
</script>

<style scoped>
.person-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 300px;
  margin: 8px;
  transition: all 0.3s ease;
}

.card-content {
  padding: 16px;
}

.person-header {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  margin-right: 12px;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar.male {
  background-color: var(--v-primary-base, #1976d2);
}

.avatar.female {
  background-color: var(--v-pink-base, #e91e63);
}

.person-name {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 500;
}

.person-details {
  margin: 12px 0;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 4px 0;
  font-size: 0.9rem;
}

.person-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}

/* Responsive Styles */
@media (max-width: 768px) {
  .person-card {
    max-width: 100%;
  }

  .mobile-text {
    font-size: 1rem;
  }

  .avatar {
    width: 48px;
    height: 48px;
  }

  .action-btn {
    min-width: 44px;
    min-height: 44px;
  }
}

/* Touch Device Styles */
.touch-device .action-btn {
  min-width: 48px;
  min-height: 48px;
}

.touch-device .detail-row {
  padding: 8px 0;
}

/* RTL Support */
[dir="rtl"] .avatar {
  margin-right: 0;
  margin-left: 12px;
}

[dir="rtl"] .person-actions {
  flex-direction: row-reverse;
}
</style>