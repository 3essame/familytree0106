import { computed } from 'vue';
import { useRtl } from './useRtl';

export function useTableHeaders(headers) {
  const { isRtl } = useRtl();

  const rtlHeaders = computed(() => {
    if (!isRtl.value) return headers;

    // Create a copy of headers and reverse their order
    return [...headers].reverse().map(header => ({
      ...header,
      align: header.align === 'start' ? 'end' : 
             header.align === 'end' ? 'start' : 
             header.align
    }));
  });

  // Default table props for RTL support
  const tableProps = computed(() => ({
    headers: rtlHeaders.value,
    'footer-props': {
      'items-per-page-text': 'عناصر في الصفحة',
      'page-text': '{0}-{1} من {2}',
      'items-per-page-options': [5, 10, 15, 20, 25, 50]
    }
  }));

  return {
    rtlHeaders,
    tableProps
  };
} 