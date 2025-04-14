<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="6">
        <v-card class="text-center pa-6" elevation="2">
          <v-card-item>
            <v-icon
              size="128"
              color="error"
              icon="mdi-shield-alert"
              class="mb-6"
            ></v-icon>
            <v-card-title class="text-h4 mb-4">غير مصرح</v-card-title>
            <v-card-text>
              <p class="text-body-1 mb-6">
                عذراً، ليس لديك الصلاحيات الكافية للوصول إلى هذه الصفحة.
              </p>
              <v-btn
                color="primary"
                size="large"
                to="/dashboard"
                prepend-icon="mdi-home"
              >
                العودة إلى الرئيسية
              </v-btn>
            </v-card-text>
          </v-card-item>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  name: 'Unauthorized'
}
</script>

<style scoped>
/* تخصيصات إضافية إذا لزم الأمر */
</style>
