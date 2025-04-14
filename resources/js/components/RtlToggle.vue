<template>
  <v-btn
    icon
    :title="isRtl ? 'تبديل إلى اتجاه اليسار إلى اليمين' : 'تبديل إلى اتجاه اليمين إلى اليسار'"
    @click="toggleDirection"
  >
    <v-icon>{{ isRtl ? 'mdi-format-horizontal-align-left' : 'mdi-format-horizontal-align-right' }}</v-icon>
  </v-btn>
</template>

<script setup>
import { useRtl } from '@/composables/useRtl';

const { isRtl, toggleDirection } = useRtl();
</script>

<style scoped>
/* تخصيصات إضافية إذا لزم الأمر */
</style>
