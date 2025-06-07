<template>
  <v-app :dir="isRtl ? 'rtl' : 'ltr'">
    <v-layout>
      <!-- الشريط العلوي - دائماً في الأعلى وفوق كل المكونات -->
      <Navbar 
        v-if="authStore.isAuthenticated" 
        @toggle-drawer="toggleDrawer" 
      />
      
      <!-- الشريط الجانبي -->
      <Sidebar 
        v-if="authStore.isAuthenticated"
        v-model="drawer"
        :is-mobile="isMobile"
        :is-rtl="isRtl"
      />
      
      <!-- المحتوى الرئيسي - دائماً تحت الشريط العلوي -->
      <v-main>        
        <v-container fluid class="pa-4 content-container">
          <router-view />
        </v-container>
      </v-main>
    </v-layout>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Sidebar from './components/Sidebar.vue';
import Navbar from './components/Navbar.vue';
import { useAuthStore } from './stores/auth';
import { useRtl } from './composables/useRtl';
import { useDisplay } from 'vuetify';

const authStore = useAuthStore();
const { isRtl } = useRtl();
const { mobile } = useDisplay();

// حساب حالة الهاتف المحمول
const isMobile = computed(() => mobile.value);

// إعداد الشريط الجانبي بناءً على حجم الشاشة
const drawer = ref(!mobile.value);

// دالة لتبديل حالة الشريط الجانبي
const toggleDrawer = () => {
  drawer.value = !drawer.value;
};

// مراقبة تغيير حجم الشاشة وتعديل سلوك الشريط الجانبي
watch(isMobile, (newVal) => {
  if (newVal) {
    // عند التحول للشاشة الصغيرة: إخفاء الشريط الجانبي
    drawer.value = false;
  } else {
    // عند التحول للشاشة الكبيرة: إظهار الشريط الجانبي
    drawer.value = true;
  }
}, { immediate: true });

onMounted(async () => {
  if (authStore.token) {
    try {
      await authStore.fetchUser();
    } catch (error) {
      console.error('Failed to fetch user on mount:', error);
      authStore.clearAuthData();
    }
  }
});
</script>

<style>
/* تأكد من أن المحتوى يظهر تحت الشريط العلوي */
.content-container {
  padding-top: 64px; /* يجب أن يكون على الأقل بنفس ارتفاع الشريط العلوي */
}

/* ضمان أن شريط التنقل يظهر فوق جميع المكونات الأخرى */
.v-app-bar {
  z-index: 1000 !important;
}
</style>