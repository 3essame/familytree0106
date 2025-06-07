<template>
  <v-app-bar 
    color="white" 
    elevation="4" 
    height="64"
    app
    fixed
    class="navbar-fixed"
  >
    <!-- ======================================================= -->
    <!-- 1. زر القائمة: يظهر دائماً للمستخدم المسجل دخوله       -->
    <!-- ======================================================= -->
    <v-app-bar-nav-icon
      v-if="isAuthenticated"
      @click.stop="$emit('toggleDrawer')"
    />

    <!-- ======================================================= -->
    <!-- 2. عنوان وشعار التطبيق                                 -->
    <!-- ======================================================= -->
    <v-toolbar-title>
      <div class="d-flex align-center">
        <v-img
          src="/images/family-tree-icon.svg"
          alt="شعار شجرة العائلة"
          :width="32"
          :height="32"
          class="me-3"
        />
        <!-- نص متجاوب: يظهر العنوان الكامل على الشاشات الكبيرة فقط -->
        <span class="d-none d-sm-inline-block">شجرة العائلة</span>
      </div>
    </v-toolbar-title>

    <v-spacer />

    <!-- ======================================================= -->
    <!-- 3. أزرار للمستخدم الزائر أو المسجل                       -->
    <!-- ======================================================= -->
    <!-- في حالة عدم تسجيل الدخول -->
    <v-btn v-if="!isAuthenticated" color="primary" variant="text" to="/login" text="تسجيل الدخول" prepend-icon="mdi-login" />

    <!-- في حالة تسجيل الدخول -->
    <template v-if="isAuthenticated">
      <!-- الإشعارات -->
      <v-btn icon>
        <v-badge content="0" color="error" dot>
          <v-icon>mdi-bell</v-icon>
        </v-badge>
      </v-btn>

      <!-- قائمة المستخدم -->
      <v-menu location="bottom end">
        <template v-slot:activator="{ props }">
          <v-btn icon v-bind="props" class="ms-2">
            <v-avatar color="primary" size="36">
              <span class="white--text text-h6">{{ userInitials }}</span>
            </v-avatar>
          </v-btn>
        </template>
        <v-list width="200">
          <v-list-item
            v-for="item in userMenuItems" :key="item.action"
            :title="item.title"
            :prepend-icon="item.icon"
            @click="handleUserMenuClick(item)"
          />
        </v-list>
      </v-menu>
    </template>
  </v-app-bar>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// التعريف الصريح للحدث الذي يرسله المكون
const emit = defineEmits(['toggleDrawer']);

const router = useRouter();
const authStore = useAuthStore();

const isAuthenticated = computed(() => authStore.isAuthenticated);

// حساب الحروف الأولى من اسم المستخدم
const userInitials = computed(() => {
  if (authStore.user?.name) {
    return authStore.user.name.match(/\b(\w)/g)?.join('').slice(0, 2).toUpperCase() || 'A';
  }
  return '';
});

const userMenuItems = [
  { title: 'الملف الشخصي', icon: 'mdi-account', action: 'profile' },
  { title: 'الإعدادات', icon: 'mdi-cog', action: 'settings' },
  { title: 'تسجيل الخروج', icon: 'mdi-logout', action: 'logout' }
];

// دالة موحدة للتعامل مع قائمة المستخدم
const handleUserMenuClick = async (item) => {
  if (item.action === 'logout') {
    await authStore.logout();
  } else {
    router.push(`/${item.action}`);
  }
};
</script>

<style scoped>
.v-toolbar-title {
  min-width: 150px;
}

.navbar-fixed {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000 !important;
}
</style>