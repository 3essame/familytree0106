<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between pa-4">
      <span>تفاصيل الزواج</span>
      <v-btn
        v-if="canEdit"
        icon="mdi-pencil"
        size="small"
        @click="isEditing = !isEditing"
      ></v-btn>
    </v-card-title>

    <v-card-text>
      <v-form v-if="isEditing" ref="form" @submit.prevent="saveChanges">
        <!-- معلومات الخطوبة -->
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="editedMarriage.engagement_date"
              label="تاريخ الخطوبة"
              type="date"
              hide-details="auto"
              class="mb-3"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="editedMarriage.engagement_location"
              label="مكان الخطوبة"
              hide-details="auto"
              class="mb-3"
            ></v-text-field>
          </v-col>
        </v-row>

        <!-- معلومات الزواج الديني -->
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="editedMarriage.religious_marriage_date"
              label="تاريخ عقد القران"
              type="date"
              hide-details="auto"
              class="mb-3"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="editedMarriage.religious_marriage_location"
              label="مكان عقد القران"
              hide-details="auto"
              class="mb-3"
            ></v-text-field>
          </v-col>
        </v-row>

        <!-- معلومات الزواج المدني -->
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="editedMarriage.civil_marriage_date"
              label="تاريخ الزواج المدني"
              type="date"
              hide-details="auto"
              class="mb-3"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="editedMarriage.civil_marriage_location"
              label="مكان الزواج المدني"
              hide-details="auto"
              class="mb-3"
            ></v-text-field>
          </v-col>
        </v-row>

        <!-- معلومات المأذون -->
        <v-text-field
          v-model="editedMarriage.officiant_name"
          label="اسم المأذون"
          hide-details="auto"
          class="mb-3"
        ></v-text-field>

        <!-- نوع الزواج -->
        <v-select
          v-model="editedMarriage.marriage_type"
          :items="marriageTypes"
          label="نوع الزواج"
          hide-details="auto"
          class="mb-3"
        ></v-select>

        <!-- الشهود -->
        <v-combobox
          v-model="editedMarriage.witnesses"
          label="الشهود"
          multiple
          chips
          hide-details="auto"
          class="mb-3"
        ></v-combobox>

        <!-- الوثائق -->
        <v-combobox
          v-model="editedMarriage.documents"
          label="الوثائق"
          multiple
          chips
          hide-details="auto"
          class="mb-3"
        ></v-combobox>

        <!-- الأحداث المخصصة -->
        <v-expansion-panels class="mb-3">
          <v-expansion-panel>
            <v-expansion-panel-title>أحداث إضافية</v-expansion-panel-title>
            <v-expansion-panel-text>
              <v-btn
                color="primary"
                size="small"
                class="mb-2"
                @click="addCustomEvent"
              >
                إضافة حدث
              </v-btn>
              <div
                v-for="(event, index) in editedMarriage.events"
                :key="index"
                class="d-flex align-center mb-2"
              >
                <v-text-field
                  v-model="event.date"
                  type="date"
                  label="التاريخ"
                  hide-details="auto"
                  class="me-2"
                ></v-text-field>
                <v-text-field
                  v-model="event.description"
                  label="الوصف"
                  hide-details="auto"
                  class="me-2"
                ></v-text-field>
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  color="error"
                  @click="removeCustomEvent(index)"
                ></v-btn>
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <!-- أزرار الحفظ والإلغاء -->
        <div class="d-flex justify-end">
          <v-btn
            color="primary"
            class="me-2"
            type="submit"
            :loading="isSaving"
          >
            حفظ
          </v-btn>
          <v-btn @click="cancelEdit">
            إلغاء
          </v-btn>
        </div>
      </v-form>

      <div v-else>
        <!-- عرض المعلومات -->
        <v-timeline density="compact">
          <v-timeline-item
            v-for="event in marriageTimeline"
            :key="event.date"
            :dot-color="getEventColor(event.event)"
            size="small"
          >
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-subtitle-2">{{ getEventTitle(event.event) }}</div>
                <div class="text-caption">{{ formatDate(event.date) }}</div>
                <div v-if="event.location" class="text-caption">
                  {{ event.location }}
                </div>
                <div v-if="event.description" class="text-caption">
                  {{ event.description }}
                </div>
              </div>
            </div>
          </v-timeline-item>
        </v-timeline>

        <v-divider class="my-3"></v-divider>

        <!-- معلومات إضافية -->
        <div v-if="marriage.witnesses?.length" class="mb-2">
          <div class="text-subtitle-2">الشهود:</div>
          <v-chip-group>
            <v-chip
              v-for="witness in marriage.witnesses"
              :key="witness"
              size="small"
            >
              {{ witness }}
            </v-chip>
          </v-chip-group>
        </div>

        <div v-if="marriage.documents?.length" class="mb-2">
          <div class="text-subtitle-2">الوثائق:</div>
          <v-chip-group>
            <v-chip
              v-for="doc in marriage.documents"
              :key="doc"
              size="small"
            >
              {{ doc }}
            </v-chip>
          </v-chip-group>
        </div>

        <div v-if="marriage.officiant_name" class="mb-2">
          <div class="text-subtitle-2">المأذون:</div>
          <div>{{ marriage.officiant_name }}</div>
        </div>

        <div class="text-caption mt-3">
          مدة الزواج: {{ formatDuration }}
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  marriage: {
    type: Object,
    required: true
  },
  canEdit: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:marriage']);

const isEditing = ref(false);
const isSaving = ref(false);
const editedMarriage = ref({ ...props.marriage });

const marriageTypes = [
  { title: 'ديني', value: 'religious' },
  { title: 'مدني', value: 'civil' },
  { title: 'ديني ومدني', value: 'both' }
];

const marriageTimeline = computed(() => {
  return props.marriage.timeline || [];
});

const formatDuration = computed(() => {
  const years = props.marriage.getDurationInYears;
  if (years < 1) {
    return 'أقل من سنة';
  }
  return `${Math.floor(years)} سنة${years > 2 ? '' : 'تان'}`;
});

function getEventColor(eventType) {
  switch (eventType) {
    case 'engagement':
      return 'purple';
    case 'religious_marriage':
      return 'green';
    case 'civil_marriage':
      return 'blue';
    case 'separation':
      return 'orange';
    case 'divorce':
      return 'red';
    default:
      return 'grey';
  }
}

function getEventTitle(eventType) {
  switch (eventType) {
    case 'engagement':
      return 'الخطوبة';
    case 'religious_marriage':
      return 'عقد القران';
    case 'civil_marriage':
      return 'الزواج المدني';
    case 'separation':
      return 'الانفصال';
    case 'divorce':
      return 'الطلاق';
    default:
      return 'حدث مخصص';
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('ar-SA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function addCustomEvent() {
  if (!editedMarriage.value.events) {
    editedMarriage.value.events = [];
  }
  editedMarriage.value.events.push({
    date: '',
    description: ''
  });
}

function removeCustomEvent(index) {
  editedMarriage.value.events.splice(index, 1);
}

async function saveChanges() {
  try {
    isSaving.value = true;
    const response = await axios.put(`/api/marriages/${props.marriage.id}`, editedMarriage.value);
    emit('update:marriage', response.data);
    isEditing.value = false;
  } catch (error) {
    console.error('Error saving marriage details:', error);
  } finally {
    isSaving.value = false;
  }
}

function cancelEdit() {
  editedMarriage.value = { ...props.marriage };
  isEditing.value = false;
}
</script>
