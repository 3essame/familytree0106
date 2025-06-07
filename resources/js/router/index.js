import { createRouter, createWebHistory } from 'vue-router';
import { permissionGuard } from './permission-guard';


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
    path: '/new-family-tree',
    name: 'new-family-tree',
    component: () => import('../views/FamilyTree/NewTreeView.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['view family tree'],
      title: 'شجرة العائلة (النسخة الجديدة)'
    }
  },
  {
    path: '/family-tree/add-member',
    name: 'add-family-member',
    component: () => import('../views/FamilyTree/AddMember.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['create family member'],
      title: 'إضافة فرد جديد'
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
