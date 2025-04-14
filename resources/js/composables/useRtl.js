import { ref, onMounted, watch } from 'vue';

/**
 * Composable لإدارة اتجاه RTL في التطبيق
 * يستخدم إمكانيات Vue 3 Composition API
 */
export function useRtl() {
  const isRtl = ref(true); // افتراضيًا نستخدم RTL للغة العربية

  /**
   * تطبيق اتجاه RTL على مستوى المستند
   */
  const applyRtlDirection = () => {
    document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr';
    document.body.dir = isRtl.value ? 'rtl' : 'ltr';
    
    // إضافة أو إزالة فئة CSS للتحكم في الأنماط
    if (isRtl.value) {
      document.documentElement.classList.add('rtl-active');
      document.body.classList.add('rtl-active');
    } else {
      document.documentElement.classList.remove('rtl-active');
      document.body.classList.remove('rtl-active');
    }
  };

  /**
   * تبديل اتجاه RTL/LTR
   */
  const toggleDirection = () => {
    isRtl.value = !isRtl.value;
  };

  /**
   * تعيين اتجاه محدد
   */
  const setDirection = (direction) => {
    isRtl.value = direction === 'rtl';
  };

  // مراقبة التغييرات في حالة RTL وتطبيقها
  watch(isRtl, () => {
    applyRtlDirection();
  });

  // تطبيق الاتجاه عند تحميل المكون
  onMounted(() => {
    applyRtlDirection();
  });

  return {
    isRtl,
    toggleDirection,
    setDirection
  };
}
