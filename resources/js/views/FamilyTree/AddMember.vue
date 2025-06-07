<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="text-h5 bg-primary text-white">
            <v-icon class="me-2">mdi-account-plus</v-icon>
            إضافة فرد جديد إلى شجرة العائلة
          </v-card-title>
          
          <v-card-text class="pa-4">
            <v-alert
              v-if="message"
              :type="messageType"
              variant="tonal"
              class="mb-4"
              closable
            >
              {{ message }}
            </v-alert>
            
            <AddMemberForm
              @submit="handleSubmit"
              @cancel="handleCancel"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AddMemberForm from '@/components/FamilyTree/AddMemberForm.vue';

const router = useRouter();
const message = ref('');
const messageType = ref<'success' | 'error' | 'info'>('info');

// معالجة نجاح إضافة فرد جديد
function handleSubmit(newMember: any) {
  message.value = `تمت إضافة ${newMember.name} بنجاح إلى شجرة العائلة`;
  messageType.value = 'success';
  
  // بعد فترة قصيرة، انتقل إلى صفحة عرض شجرة العائلة
  setTimeout(() => {
    router.push({ 
      name: 'family-tree', 
      params: { highlight: newMember.id } 
    });
  }, 2000);
}

// معالجة إلغاء النموذج
function handleCancel() {
  router.push({ name: 'family-tree' });
}
</script>

<style scoped>
.v-card-title {
  border-radius: 4px 4px 0 0;
}
</style> 