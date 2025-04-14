<template>
  <v-app :dir="isRtl ? 'rtl' : 'ltr'">
    <v-layout>
      <!-- الشريط الجانبي -->
      <Sidebar :is-collapsed="isSidebarCollapsed" />
      
      <!-- المحتوى الرئيسي -->
      <v-main>
        <!-- الشريط العلوي -->
        <Navbar @toggle-sidebar="toggleSidebar" />
        
        <v-container fluid class="pa-4">
          <router-view />
        </v-container>
        
        <!-- الشريط السفلي -->
        <Footer />
      </v-main>
    </v-layout>
  </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Sidebar from './components/Sidebar.vue';
import Navbar from './components/Navbar.vue';
import Footer from './components/Footer.vue';
import { useAuthStore } from './stores/auth';
import { useRouter } from 'vue-router';
import { useRtl } from './composables/useRtl';

const isSidebarCollapsed = ref(false);
const authStore = useAuthStore();
const router = useRouter();
const { isRtl } = useRtl(); // استخدام composable الخاص بالـ RTL

// تبديل حالة الشريط الجانبي
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

onMounted(async () => {
  console.log('App mounted, checking authentication...');
  // جلب CSRF token
  await authStore.getCsrfToken();
  
  // التحقق مما إذا كان المستخدم مسجل دخوله
  if (authStore.isAuthenticated) {
    try {
      console.log('User is authenticated, fetching user data...');
      await authStore.fetchUser();
    } catch (error) {
      console.error('Failed to fetch user data:', error);
      // إذا فشل جلب بيانات المستخدم، قم بتسجيل الخروج
      authStore.clearAuthData();
      router.push('/login');
    }
  }
});
</script>

<style>
/* تخصيص عام للتطبيق */
html {
  overflow-y: auto;
}

/* تخصيص الخطوط */
.v-application {
  font-family: 'Cairo', sans-serif !important;
}

/* تخصيص المحتوى الرئيسي */
.v-main {
  background-color: #f5f5f5;
}
</style>
