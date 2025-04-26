import { ref } from 'vue'

interface RtlComposable {
  isRtl: Ref<boolean>
  applyRtlDirection: () => void
  toggleDirection: () => void
}

export function useRtl(): RtlComposable {
  const isRtl = ref(true)

  const applyRtlDirection = (): void => {
    document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr'
    document.body.dir = isRtl.value ? 'rtl' : 'ltr'
    
    if (isRtl.value) {
      document.documentElement.classList.add('rtl-active')
      document.body.classList.add('rtl-active')
    } else {
      document.documentElement.classList.remove('rtl-active')
      document.body.classList.remove('rtl-active')
    }
  }

  const toggleDirection = (): void => {
    isRtl.value = !isRtl.value
  }

  return {
    isRtl,
    applyRtlDirection,
    toggleDirection
  }
}