<template>
  <v-footer
    app
    class="bg-white"
    border
  >
    <v-row no-gutters>
      <v-col cols="12" sm="6" class="text-center text-sm-right">
        <span class="text-body-2">&copy; {{ currentYear }} اشتراكات العاملين. جميع الحقوق محفوظة.</span>
      </v-col>
      <v-col cols="12" sm="6" class="text-center text-sm-left">
        <span class="text-body-2">الإصدار {{ version }}</span>
      </v-col>
    </v-row>
  </v-footer>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'Footer',
  setup() {
    // السنة الحالية
    const currentYear = computed(() => new Date().getFullYear());
    
    // إصدار التطبيق
    const version = ref('1.0.0');
    
    return {
      currentYear,
      version
    };
  }
};
</script>

<style scoped>
/* تخصيصات إضافية إذا لزم الأمر */
</style>
