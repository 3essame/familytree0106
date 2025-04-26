<template>
  <div class="family-events">
    <div class="events-header">
      <h2>الأحداث العائلية</h2>
      <button @click="showAddEventModal = true" class="btn-add">
        <i class="fas fa-plus"></i>
        إضافة حدث
      </button>
    </div>

    <div class="events-timeline">
      <div v-for="event in sortedEvents" :key="event.id" class="event-item">
        <div class="event-date">
          {{ formatDate(event.date) }}
        </div>
        <div class="event-content">
          <div class="event-title">
            <i :class="getEventIcon(event.type)"></i>
            {{ event.title }}
          </div>
          <div class="event-description">{{ event.description }}</div>
          <div class="event-participants" v-if="event.participants?.length">
            <span class="label">المشاركون:</span>
            <span>{{ event.participants.map(p => p.name).join('، ') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal" v-if="showAddEventModal" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>إضافة حدث جديد</h2>
          <button @click="closeModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="addEvent" class="event-form">
            <div class="form-group">
              <label>نوع الحدث</label>
              <select v-model="newEvent.type" required>
                <option value="birth">ولادة</option>
                <option value="marriage">زواج</option>
                <option value="death">وفاة</option>
                <option value="other">أخرى</option>
              </select>
            </div>

            <div class="form-group">
              <label>عنوان الحدث</label>
              <input type="text" v-model="newEvent.title" required>
            </div>

            <div class="form-group">
              <label>التاريخ</label>
              <input type="date" v-model="newEvent.date" required>
            </div>

            <div class="form-group">
              <label>الوصف</label>
              <textarea v-model="newEvent.description" rows="3"></textarea>
            </div>

            <div class="form-group">
              <label>المشاركون</label>
              <select v-model="newEvent.participants" multiple>
                <option v-for="person in familyMembers" :key="person.id" :value="person">
                  {{ person.name }}
                </option>
              </select>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-submit">إضافة</button>
              <button type="button" @click="closeModal" class="btn-cancel">إلغاء</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    events: {
      type: Array,
      required: true
    },
    familyMembers: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      showAddEventModal: false,
      newEvent: {
        type: 'other',
        title: '',
        date: '',
        description: '',
        participants: []
      }
    }
  },
  computed: {
    sortedEvents() {
      return [...this.events].sort((a, b) => new Date(b.date) - new Date(a.date));
    }
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    getEventIcon(type) {
      const icons = {
        birth: 'fas fa-baby',
        marriage: 'fas fa-ring',
        death: 'fas fa-cross',
        other: 'fas fa-calendar'
      };
      return icons[type] || icons.other;
    },
    addEvent() {
      this.$emit('add-event', { ...this.newEvent });
      this.closeModal();
    },
    closeModal() {
      this.showAddEventModal = false;
      this.newEvent = {
        type: 'other',
        title: '',
        date: '',
        description: '',
        participants: []
      };
    }
  }
}
</script>

<style scoped>
.family-events {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.events-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.btn-add {
  background: #4a90e2;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 0.5rem 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-add:hover {
  background: #357abd;
  transform: translateY(-1px);
}

.events-timeline {
  position: relative;
  padding-right: 2rem;
}

.events-timeline::before {
  content: '';
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #eee;
}

.event-item {
  position: relative;
  margin-bottom: 2rem;
  padding-right: 2rem;
}

.event-item::before {
  content: '';
  position: absolute;
  right: -5px;
  top: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #4a90e2;
}

.event-date {
  font-weight: 600;
  color: #666;
  margin-bottom: 0.5rem;
}

.event-content {
  background: #f8f9fa;
  border-radius: 4px;
  padding: 1rem;
}

.event-title {
  font-weight: 600;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.event-description {
  color: #666;
  margin-bottom: 0.5rem;
}

.event-participants {
  font-size: 0.9rem;
  color: #666;
}

.label {
  font-weight: 600;
  margin-left: 0.5rem;
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
  max-width: 500px;
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

.event-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #666;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1rem;
}

.btn-submit {
  background: #4a90e2;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 0.5rem 1rem;
  cursor: pointer;
}

.btn-cancel {
  background: #f8f9fa;
  color: #666;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 0.5rem 1rem;
  cursor: pointer;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #666;
}
</style> 