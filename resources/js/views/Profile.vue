<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-6">الملف الشخصي</h1>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col cols="12" md="4">
        <v-card class="mb-4">
          <v-card-item class="text-center">
            <v-avatar size="150" class="mb-4">
              <v-img :src="userAvatar" alt="صورة المستخدم"></v-img>
            </v-avatar>
            <v-card-title class="text-h5">{{ user.name }}</v-card-title>
            <v-card-subtitle>{{ user.email }}</v-card-subtitle>
            <v-chip-group class="mt-4">
              <v-chip v-for="role in user.roles" :key="role" color="primary" variant="outlined">
                {{ role }}
              </v-chip>
            </v-chip-group>
          </v-card-item>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title class="text-subtitle-1 font-weight-bold">
            معلومات الحساب
          </v-card-title>
          
          <v-card-text>
            <v-form @submit.prevent="updateProfile">
              <v-alert
                v-if="message"
                :type="messageType"
                variant="tonal"
                class="mb-4"
                density="compact"
              >
                {{ message }}
              </v-alert>
              
              <v-text-field
                v-model="form.name"
                label="الاسم"
                variant="outlined"
                class="mb-3"
              ></v-text-field>
              
              <v-text-field
                v-model="form.email"
                label="البريد الإلكتروني"
                type="email"
                variant="outlined"
                class="mb-3"
                readonly
              ></v-text-field>
              
              <v-divider class="my-6"></v-divider>
              
              <v-card-title class="text-subtitle-1 font-weight-bold px-0">
                تغيير كلمة المرور
              </v-card-title>
              
              <v-text-field
                v-model="form.current_password"
                label="كلمة المرور الحالية"
                type="password"
                variant="outlined"
                class="mb-3"
              ></v-text-field>
              
              <v-text-field
                v-model="form.new_password"
                label="كلمة المرور الجديدة"
                type="password"
                variant="outlined"
                class="mb-3"
              ></v-text-field>
              
              <v-text-field
                v-model="form.new_password_confirmation"
                label="تأكيد كلمة المرور الجديدة"
                type="password"
                variant="outlined"
                class="mb-6"
              ></v-text-field>
              
              <v-btn
                type="submit"
                color="primary"
                size="large"
                :loading="loading"
                :disabled="loading"
              >
                حفظ التغييرات
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

export default {
  name: 'Profile',
  setup() {
    const authStore = useAuthStore();
    
    // بيانات المستخدم
    const user = computed(() => authStore.user || {});
    
    // الصورة الافتراضية للمستخدم
    const userAvatar = computed(() => {
      return user.value.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value.name || 'User') + '&background=random';
    });
    
    // نموذج تحديث الملف الشخصي
    const form = ref({
      name: '',
      email: '',
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    });
    
    // حالة التحميل
    const loading = ref(false);
    
    // رسالة النجاح/الخطأ
    const message = ref('');
    const messageType = ref('success');
    
    // تحميل بيانات المستخدم
    onMounted(() => {
      if (user.value) {
        form.value.name = user.value.name || '';
        form.value.email = user.value.email || '';
      }
    });
    
    // تحديث الملف الشخصي
    const updateProfile = async () => {
      loading.value = true;
      message.value = '';
      
      try {
        // تحقق مما إذا كان المستخدم يريد تغيير كلمة المرور
        const data = {
          name: form.value.name
        };
        
        if (form.value.current_password && form.value.new_password) {
          data.current_password = form.value.current_password;
          data.new_password = form.value.new_password;
          data.new_password_confirmation = form.value.new_password_confirmation;
        }
        
        const response = await axios.post('/api/profile/update', data, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        });
        
        if (response.data.success) {
          message.value = 'تم تحديث الملف الشخصي بنجاح';
          messageType.value = 'success';
          
          // تحديث بيانات المستخدم في المتجر
          if (response.data.user) {
            authStore.user = response.data.user;
          }
          
          // مسح حقول كلمة المرور
          form.value.current_password = '';
          form.value.new_password = '';
          form.value.new_password_confirmation = '';
        } else {
          message.value = response.data.message || 'حدث خطأ أثناء تحديث الملف الشخصي';
          messageType.value = 'error';
        }
      } catch (error) {
        console.error('Update profile error:', error);
        message.value = error.response?.data?.message || 'حدث خطأ أثناء تحديث الملف الشخصي';
        messageType.value = 'error';
      } finally {
        loading.value = false;
      }
    };
    
    return {
      user,
      userAvatar,
      form,
      loading,
      message,
      messageType,
      updateProfile
    };
  }
};
</script>

<style scoped>
/* تخصيصات إضافية إذا لزم الأمر */
</style>
