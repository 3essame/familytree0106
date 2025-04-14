import { useAuthStore } from '../stores/auth';

/**
 * حارس المسارات للتحقق من الصلاحيات
 * @param {Object} to - المسار المقصود
 * @param {Object} from - المسار الحالي
 * @param {Function} next - دالة الاستمرار
 */
export const permissionGuard = async (to, from, next) => {
  const authStore = useAuthStore();
  
  // إذا كان المسار يتطلب المصادقة
  if (to.meta.requiresAuth) {
    // التحقق من وجود توكن المصادقة
    if (!authStore.isAuthenticated) {
      return next({ name: 'login' });
    }
    
    // إذا لم تكن بيانات المستخدم محملة، قم بتحميلها
    if (!authStore.user) {
      try {
        await authStore.fetchUser();
      } catch (error) {
        return next({ name: 'login' });
      }
    }
    
    // التحقق من الأدوار المطلوبة
    if (to.meta.roles && to.meta.roles.length > 0) {
      const hasRequiredRole = to.meta.roles.some(role => authStore.hasRole(role));
      
      if (!hasRequiredRole) {
        return next({ name: 'unauthorized' });
      }
    }
    
    // التحقق من الصلاحيات المطلوبة
    if (to.meta.permissions && to.meta.permissions.length > 0) {
      const hasRequiredPermission = to.meta.permissions.some(permission => 
        authStore.can(permission)
      );
      
      if (!hasRequiredPermission) {
        return next({ name: 'unauthorized' });
      }
    }
  }
  
  // استمرار إلى المسار المطلوب
  next();
};
