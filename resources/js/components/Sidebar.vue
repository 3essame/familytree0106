<template>
  <v-navigation-drawer
    v-model="drawer"
    :rail="isCollapsed"
    permanent
    location="right"
    :width="280"
    :rail-width="56"
    color="primary"
    theme="dark"
    class="sidebar"
  >
    <v-list-item
      prepend-avatar="https://randomuser.me/api/portraits/men/85.jpg"
      :title="authStore.user ? authStore.user.name : 'مستخدم'"
      :subtitle="authStore.user ? authStore.user.email : ''"
      class="py-3"
    >
      <template v-slot:append>
        <v-btn
          variant="text"
          :icon="isCollapsed ? 'mdi-chevron-left' : 'mdi-chevron-right'"
          @click.stop="toggleSidebar"
          class="toggle-btn"
        ></v-btn>
      </template>
    </v-list-item>

    <v-divider></v-divider>

    <v-list density="compact" nav>
      <!-- عناصر القائمة المتاحة لجميع المستخدمين -->
      <v-list-item
        prepend-icon="mdi-view-dashboard"
        title="الرئيسية"
        value="dashboard"
        to="/dashboard"
      ></v-list-item>
      
      <!-- عناصر القائمة المرتبطة بالصلاحيات -->
      <v-list-item
        v-if="can('view members')"
        prepend-icon="mdi-account-group"
        title="الأعضاء"
        value="members"
        to="/members"
      ></v-list-item>
      
      <v-list-item
        v-if="can('view subscriptions')"
        prepend-icon="mdi-credit-card"
        title="الاشتراكات"
        value="subscriptions"
        to="/subscriptions"
      ></v-list-item>
      
      <v-list-item
        v-if="can('view reports')"
        prepend-icon="mdi-chart-bar"
        title="التقارير"
        value="reports"
        to="/reports"
      ></v-list-item>
      
      <!-- عناصر القائمة المرتبطة بالأدوار -->
      <v-list-group
        v-if="hasRole('admin')"
        value="admin"
      >
        <template v-slot:activator="{ props }">
          <v-list-item
            v-bind="props"
            prepend-icon="mdi-cog"
            title="الإدارة"
          ></v-list-item>
        </template>

        <v-list-item
          prepend-icon="mdi-account-multiple"
          title="المستخدمين"
          value="users"
          to="/users"
        ></v-list-item>

        <v-list-item
          prepend-icon="mdi-cog-outline"
          title="الإعدادات"
          value="settings"
          to="/settings"
        ></v-list-item>
        
        <v-list-item
          prepend-icon="mdi-shield-account"
          title="الأدوار والصلاحيات"
          value="roles"
          to="/roles"
        ></v-list-item>
      </v-list-group>
    </v-list>

    <template v-slot:append>
      <div class="pa-2">
        <v-btn
          block
          color="error"
          prepend-icon="mdi-logout"
          @click="logout"
        >
          تسجيل الخروج
        </v-btn>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script>
import { ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { useRtl } from '@/composables/useRtl';

export default {
  name: 'Sidebar',
  props: {
    isCollapsed: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { emit }) {
    const authStore = useAuthStore();
    const router = useRouter();
    const drawer = ref(true);
    const { isRtl } = useRtl();
    
    watch(() => props.isCollapsed, (newValue) => {
      drawer.value = !newValue;
    });
    
    const toggleSidebar = () => {
      emit('toggle-sidebar');
    };
    
    const can = (permission) => {
      return authStore.permissions?.includes(permission) || false;
    };
    
    const hasRole = (role) => {
      return authStore.roles?.includes(role) || false;
    };
    
    const logout = async () => {
      await authStore.logout();
      router.push('/login');
    };
    
    return {
      authStore,
      drawer,
      can,
      hasRole,
      logout,
      toggleSidebar,
      isRtl
    };
  }
};
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  right: 0;
  height: 100%;
  z-index: 100;
}

/* تخصيصات للأزرار والعناصر */
:deep(.v-list-item__prepend) {
  margin-right: 0 !important;
  margin-left: 16px !important;
}

:deep(.v-list-item__append) {
  margin-left: 0 !important;
  margin-right: 16px !important;
}

:deep(.v-list-item__content) {
  text-align: right !important;
}

/* تخصيصات زر التبديل */
.toggle-btn {
  position: relative;
  right: 0;
  margin-right: 0;
  z-index: 1;
}

/* تخصيصات حالة التصغير */
:deep(.v-navigation-drawer--rail) {
  .v-list-item__prepend {
    margin-left: 0 !important;
  }
  
  .toggle-btn {
    margin-right: -8px !important;
  }
}
</style>
