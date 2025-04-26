<template>
  <v-app-bar
    color="white"
    elevation="1"
    class="px-3"
  >
    <template v-if="isRtl">
      <div :style="{width: '10px'}"></div>
      <v-app-bar-nav-icon
        @click="toggleSidebar"
        class="mr-2"
      ></v-app-bar-nav-icon>
    </template>

    <template v-else>

      <v-app-bar-nav-icon
        @click="toggleSidebar"
        class="ml-2"
      ></v-app-bar-nav-icon>
    </template>

    <v-toolbar-title>
      <div class="d-flex align-center">
        <v-img
          src="/images/family-tree-icon.svg"
          alt="شعار شجرة العائلة"
          width="32"
          height="32"
          class="me-2"
        ></v-img>
        <span>شجرة العائلة | {{ currentRoute }}</span>
      </div>
    </v-toolbar-title>

    <v-spacer></v-spacer>
    <v-spacer></v-spacer>

    <!-- الإشعارات -->
    <v-menu
      location="bottom end"
      :close-on-content-click="false"
    >
      <template v-slot:activator="{ props }">
        <v-btn
          icon
          v-bind="props"
          class="mr-2"
        >
          <v-badge
            :content="notifications.length"
            :value="notifications.length"
            color="error"
            overlap
          >
            <v-icon>mdi-bell</v-icon>
          </v-badge>
        </v-btn>
      </template>

      <v-card min-width="300">
        <v-list>
          <v-list-subheader>الإشعارات</v-list-subheader>
          <v-divider></v-divider>

          <v-list-item
            v-for="(notification, index) in notifications"
            :key="index"
            :title="notification.message"
            :subtitle="notification.time"
          >
            <template v-slot:prepend>
              <v-avatar color="primary">
                <v-icon color="white">mdi-information</v-icon>
              </v-avatar>
            </template>
          </v-list-item>

          <v-list-item v-if="notifications.length === 0">
            <v-list-item-title>لا توجد إشعارات جديدة</v-list-item-title>
          </v-list-item>
        </v-list>

        <v-divider></v-divider>

        <v-card-actions>
          <v-btn
            variant="text"
            block
            color="primary"
            @click="clearNotifications"
          >
            تحديد الكل كمقروء
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-menu>

    <!-- قائمة المستخدم -->
    <v-menu location="bottom end">
      <template v-slot:activator="{ props }">
        <v-btn
          v-bind="props"
          variant="text"
        >
          <v-avatar size="32" class="ml-2">
            <v-img :src="userAvatar" alt="صورة المستخدم"></v-img>
          </v-avatar>
          {{ user.name }}
          <v-icon>mdi-chevron-down</v-icon>
        </v-btn>
      </template>

      <v-list>
        <v-list-item
          prepend-icon="mdi-account"
          title="الملف الشخصي"
          to="/profile"
        ></v-list-item>

        <v-divider></v-divider>

        <v-list-item
          prepend-icon="mdi-logout"
          title="تسجيل الخروج"
          @click="logout"
        ></v-list-item>
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useRtl } from '../composables/useRtl';
import RtlToggle from './RtlToggle.vue';

export default {
  name: 'Navbar',
  emits: ['toggle-sidebar'],
  setup(props, { emit }) {
    const authStore = useAuthStore();
    const router = useRouter();
    const route = useRoute();
    const { isRtl } = useRtl(); // استخدام composable الخاص بالـ RTL

    // بيانات المستخدم
    const user = computed(() => authStore.user || {});

    // الصورة الافتراضية للمستخدم
    const userAvatar = computed(() => {
      return user.value.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value.name || 'User') + '&background=random';
    });

    // اسم المسار الحالي
    const currentRoute = computed(() => {
      return route.meta.title || route.name || '';
    });

    // الإشعارات (يمكن استبدالها بجلب البيانات من API)
    const notifications = ref([]);

    // تبديل حالة الشريط الجانبي
    const toggleSidebar = () => {
      emit('toggle-sidebar');
    };

    // مسح الإشعارات
    const clearNotifications = () => {
      notifications.value = [];
    };

    // تسجيل الخروج
    const logout = async () => {
      await authStore.logout();
      router.push('/login');
    };

    return {
      user,
      userAvatar,
      currentRoute,
      notifications,
      toggleSidebar,
      clearNotifications,
      logout,
      isRtl
    };
  }
};
</script>

<style scoped>
.v-app-bar {
  z-index: 99;
}

:deep(.v-toolbar-title) {
  text-align: right !important;
  margin-right: 16px !important;
}

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

:deep(.v-list-subheader) {
  text-align: right !important;
}

:deep(.v-card-title) {
  text-align: right !important;
}

:deep(.v-card-text) {
  text-align: right !important;
}

/* تخصيصات إضافية للشريط العلوي في وضع RTL */
:deep(.v-app-bar__content) {
  flex-direction: row-reverse;
}
</style>
