import { createRouter, createWebHistory } from 'vue-router';
import { permissionGuard } from './permission-guard';
import SubscriptionsIndex from '@/views/Subscriptions/Index.vue'
import SubscriptionsImport from '@/views/Subscriptions/Import.vue'

// تعريف المسارات
const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/Login.vue'),
    meta: {
      requiresAuth: false
    }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('../views/Profile.vue'),
    meta: {
      requiresAuth: true,
      title: 'الملف الشخصي'
    }
  },
  {
    path: '/members',
    name: 'members',
    component: () => import('../views/Members/Index.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/members/create',
    name: 'members.create',
    component: () => import('../views/Members/Create.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['create members']
    }
  },
  {
    path: '/members/:id/edit',
    name: 'members.edit',
    component: () => import('../views/Members/Edit.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['edit members']
    }
  },
  {
    path: '/members/:id',
    name: 'members.show',
    component: () => import('../views/Members/Show.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['view members']
    }
  },
  {
    path: '/subscriptions',
    name: 'subscriptions',
    component: SubscriptionsIndex,
    meta: {
      requiresAuth: true,
      permissions: ['view subscriptions'],
      title: 'الاشتراكات'
    }
  },
  {
    path: '/subscriptions/import',
    name: 'subscriptions-import',
    component: SubscriptionsImport,
    meta: { requiresAuth: true }
  },
  {
    path: '/subscriptions/create',
    name: 'subscriptions.create',
    component: () => import('../views/Subscriptions/Create.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['create subscriptions'],
      title: 'إضافة اشتراك'
    }
  },
  {
    path: '/subscriptions/:id/edit',
    name: 'subscriptions.edit',
    component: () => import('../views/Subscriptions/Edit.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['edit subscriptions'],
      title: 'تعديل اشتراك'
    }
  },
  {
    path: '/subscriptions/:id',
    name: 'subscriptions.show',
    component: () => import('../views/Subscriptions/Show.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['view subscriptions'],
      title: 'تفاصيل الاشتراك'
    }
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('../views/Users/Index.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin']
    }
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('../views/Settings.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin']
    }
  },
  {
    path: '/roles',
    name: 'roles',
    component: () => import('../views/Roles/Index.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin']
    }
  },
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: () => import('../views/Unauthorized.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../views/NotFound.vue'),
  }
];

// إنشاء الموجه
const router = createRouter({
  history: createWebHistory('/'),
  routes
});

// تسجيل حارس المسارات
router.beforeEach(permissionGuard);

export default router;
