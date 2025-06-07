<template>
  <v-navigation-drawer
    v-if="!isMobile || modelValue"
    :model-value="modelValue"
    @update:model-value="handleDrawerUpdate"
    :temporary="isMobile"
    :permanent="!isMobile"
    :location="isRtl ? 'right' : 'left'"
    width="280"
    color="primary"
    theme="dark"
    :rail="!isMobile && !modelValue"
    :rail-width="56"
    :disable-resize-watcher="false"
    :touchless="false"
    :scrim="isMobile && modelValue"
    app
  >
    <!-- رأس الشريط الجانبي -->
    <v-list-item
      v-if="authStore.user && (modelValue || !isMobile)"
      :prepend-avatar="'/images/family-tree-logo.svg'"
      :title="!isMobile && !modelValue ? '' : authStore.user.name"
      :subtitle="!isMobile && !modelValue ? '' : 'شجرة العائلة'"
      class="py-3"
      nav
    />
    <v-divider v-if="modelValue || !isMobile" />

    <!-- قائمة التنقل -->
    <v-list density="compact" nav>
      <v-list-item 
        prepend-icon="mdi-view-dashboard" 
        :title="(!isMobile && !modelValue) ? '' : 'الرئيسية'" 
        to="/dashboard"
        :class="{ 'justify-center': !isMobile && !modelValue }"
      />
      
      <v-list-item 
        v-if="can('view family tree')" 
        prepend-icon="mdi-family-tree" 
        :title="(!isMobile && !modelValue) ? '' : 'شجرة العائلة'" 
        to="/new-family-tree"
        :class="{ 'justify-center': !isMobile && !modelValue }"
      />
      
      <v-list-group 
        v-if="hasRole('admin') && (modelValue || isMobile)" 
        value="admin"
      >
        <template v-slot:activator="{ props: activatorProps }">
          <v-list-item 
            v-bind="activatorProps" 
            prepend-icon="mdi-shield-account" 
            title="الإدارة" 
          />
        </template>
        <v-list-item
          v-for="item in adminMenuItems"
          :key="item.value"
          :value="item.value"
          :title="item.title"
          :to="item.to"
          :prepend-icon="item.icon"
          density="comfortable"
        />
      </v-list-group>

      <!-- عناصر الإدارة في حالة Rail Mode -->
      <template v-if="hasRole('admin') && !isMobile && !modelValue">
        <v-list-item
          v-for="item in adminMenuItems"
          :key="item.value"
          :value="item.value"
          :title="''"
          :to="item.to"
          :prepend-icon="item.icon"
          class="justify-center"
        />
      </template>
      
      <v-list-item 
        prepend-icon="mdi-cog" 
        :title="(!isMobile && !modelValue) ? '' : 'الإعدادات'" 
        to="/settings"
        :class="{ 'justify-center': !isMobile && !modelValue }"
      />
      
      <v-list-item 
        prepend-icon="mdi-logout" 
        :title="(!isMobile && !modelValue) ? '' : 'تسجيل الخروج'" 
        @click="logout"
        :class="{ 'justify-center': !isMobile && !modelValue }"
      />
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';

// استقبال الخصائص (Props)
const props = defineProps({
  modelValue: Boolean,
  isRtl: Boolean,
  isMobile: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const authStore = useAuthStore();

const adminMenuItems = [
  { title: 'المستخدمين', icon: 'mdi-account-multiple', value: 'users', to: '/users' },
  { title: 'الأدوار والصلاحيات', icon: 'mdi-shield', value: 'roles', to: '/roles' },
];

// دالة التعامل مع تحديث حالة الشريط الجانبي
const handleDrawerUpdate = (value) => {
  emit('update:modelValue', value);
};

const can = (permission) => authStore.can(permission);
const hasRole = (role) => authStore.hasRole(role);
const logout = async () => await authStore.logout();
</script>

<style scoped>
/* أنماط مخصصة للـ Rail Mode */
.v-list-item.justify-center {
  justify-content: center;
}

.v-list-item.justify-center :deep(.v-list-item__prepend) {
  margin-inline-end: 0;
}
</style>