<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card elevation="10" class="rounded-lg">
          <v-card-title class="text-center primary white--text py-4">
            <h2>تسجيل الدخول</h2>
            <p class="text-subtitle-1 mb-0 mt-2">مرحبًا بك في نظام ادارة اشتراكات العاملين </p>
          </v-card-title>
          
          <v-card-text class="pa-6">
            <v-alert
              v-if="error"
              type="error"
              variant="tonal"
              class="mb-4"
              density="compact"
            >
              {{ error }}
            </v-alert>
            
            <v-form @submit.prevent="handleLogin">
              <v-text-field
                v-model="form.email"
                label="البريد الإلكتروني"
                type="email"
                required
                variant="outlined"
                prepend-inner-icon="mdi-email"
                autocomplete="email"
                class="mb-2"
              ></v-text-field>
              
              <v-text-field
                v-model="form.password"
                label="كلمة المرور"
                type="password"
                required
                variant="outlined"
                prepend-inner-icon="mdi-lock"
                autocomplete="current-password"
                class="mb-4"
              ></v-text-field>
              
              <v-checkbox
                v-model="form.remember"
                label="تذكرني"
                color="primary"
                hide-details
                class="mb-6"
              ></v-checkbox>
              
              <v-btn
                type="submit"
                color="primary"
                block
                size="large"
                :loading="loading"
                :disabled="loading"
              >
                تسجيل الدخول
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

export default {
  name: 'Login',
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();
    
    // نموذج تسجيل الدخول
    const form = ref({
      email: '',
      password: '',
      remember: false
    });
    
    // حالة التحميل
    const loading = ref(false);
    
    // رسالة الخطأ
    const error = ref('');
    
    // معالجة تسجيل الدخول
    const handleLogin = async () => {
      loading.value = true;
      error.value = '';
      
      try {
        const success = await authStore.login({
          email: form.value.email,
          password: form.value.password
        });
        
        if (success) {
          router.push('/dashboard');
        } else {
          error.value = 'فشل تسجيل الدخول. يرجى التحقق من بيانات الاعتماد الخاصة بك.';
        }
      } catch (err) {
        error.value = 'حدث خطأ أثناء محاولة تسجيل الدخول. يرجى المحاولة مرة أخرى.';
        console.error('Login error:', err);
      } finally {
        loading.value = false;
      }
    };
    
    return {
      form,
      loading,
      error,
      handleLogin
    };
  }
};
</script>

<style scoped>
/* تم استبدال الأنماط بمكونات Vuetify */
</style>
