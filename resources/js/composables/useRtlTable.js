import { computed } from 'vue';
import { useRtl } from './useRtl';

export function useRtlTable(headers, options = {}) {
  const { isRtl } = useRtl();

  // معالجة ترتيب الأعمدة للـ RTL
  const tableHeaders = computed(() => {
    if (!isRtl.value) return headers;
    
    // نسخ المصفوفة وعكس ترتيبها
    const reversedHeaders = [...headers].reverse();
    
    // تعديل محاذاة الأعمدة
    return reversedHeaders.map(header => ({
      ...header,
      align: header.align === 'start' ? 'end' : 
             header.align === 'end' ? 'start' : 
             header.align,
      // إضافة خصائص إضافية لدعم RTL
      fixed: header.fixed,
      width: header.width,
      sortable: header.sortable !== undefined ? header.sortable : true,
      class: header.class
    }));
  });

  // خيارات الجدول الافتراضية مع دعم RTL
  const defaultTableProps = computed(() => ({
    // تفعيل RTL على مستوى الجدول
    class: isRtl.value ? 'v-data-table--rtl' : '',
    // خيارات تذييل الجدول
    'footer-props': {
      'items-per-page-text': 'عناصر في الصفحة',
      'page-text': '{0}-{1} من {2}',
      'items-per-page-options': [5, 10, 15, 20, 25, 50],
      ...options.footerProps
    },
    // خيارات إضافية للجدول
    density: 'comfortable',
    hover: true,
    'fixed-header': true,
    'return-object': true,
    ...options.tableProps
  }));

  return {
    tableHeaders,
    defaultTableProps
  };
} 