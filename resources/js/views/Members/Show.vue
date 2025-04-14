<template>
  <v-container>
    <v-row>
      <v-col cols="12" class="d-flex align-center">
        <h1 class="text-h4">تفاصيل العضو</h1>
        <v-spacer></v-spacer>
        <v-btn
          color="primary"
          variant="outlined"
          :to="`/members/${memberId}/edit`"
          prepend-icon="mdi-pencil"
        >
          تعديل
        </v-btn>
      </v-col>
    </v-row>
    
    <v-row v-if="loading">
      <v-col cols="12" class="text-center">
        <v-progress-circular
          indeterminate
          color="primary"
          size="64"
        ></v-progress-circular>
      </v-col>
    </v-row>
    
    <v-row v-else-if="error">
      <v-col cols="12">
        <v-alert
          type="error"
          variant="tonal"
          title="خطأ في تحميل البيانات"
        >
          {{ error }}
        </v-alert>
      </v-col>
    </v-row>
    
    <template v-else>
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="mb-4">
            <v-card-title class="text-h6">
              المعلومات الأساسية
            </v-card-title>
            <v-card-text>
              <v-list lines="two">
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon icon="mdi-account" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>الاسم</v-list-item-title>
                  <v-list-item-subtitle>{{ member.name }}</v-list-item-subtitle>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon icon="mdi-phone" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>رقم الهاتف</v-list-item-title>
                  <v-list-item-subtitle>{{ member.phone }}</v-list-item-subtitle>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon icon="mdi-email" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>البريد الإلكتروني</v-list-item-title>
                  <v-list-item-subtitle>{{ member.email }}</v-list-item-subtitle>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon icon="mdi-card-account-details-outline" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>رقم الهوية</v-list-item-title>
                  <v-list-item-subtitle>{{ member.national_id }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="6">
          <v-card class="mb-4">
            <v-card-title class="text-h6">
              معلومات العمل والعضوية
            </v-card-title>
            <v-card-text>
              <v-list lines="two">
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon icon="mdi-briefcase" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>المسمى الوظيفي</v-list-item-title>
                  <v-list-item-subtitle>{{ member.job_title || 'غير محدد' }}</v-list-item-subtitle>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon icon="mdi-domain" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>مكان العمل</v-list-item-title>
                  <v-list-item-subtitle>{{ member.workplace || 'غير محدد' }}</v-list-item-subtitle>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon icon="mdi-map-marker" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>العنوان</v-list-item-title>
                  <v-list-item-subtitle>{{ member.address || 'غير محدد' }}</v-list-item-subtitle>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon :icon="statusIcon" :color="statusColor" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>الحالة</v-list-item-title>
                  <v-list-item-subtitle>
                    <v-chip
                      :color="statusColor"
                      size="small"
                      class="text-white"
                    >
                      {{ statusText }}
                    </v-chip>
                  </v-list-item-subtitle>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon :icon="subscriptionIcon" :color="subscriptionColor" class="me-3"></v-icon>
                  </template>
                  <v-list-item-title>حالة الاشتراك</v-list-item-title>
                  <v-list-item-subtitle>
                    <v-chip
                      :color="subscriptionColor"
                      size="small"
                      class="text-white"
                    >
                      {{ subscriptionText }}
                    </v-chip>
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
      
      <v-row v-if="member.notes">
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h6">
              ملاحظات
            </v-card-title>
            <v-card-text>
              <p>{{ member.notes }}</p>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </v-container>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'MemberShow',
  setup() {
    const route = useRoute();
    const memberId = route.params.id;
    
    const member = ref({});
    const loading = ref(true);
    const error = ref(null);
    
    // حالة العضو
    const statusText = computed(() => {
      switch (member.value.status) {
        case 'active':
          return 'نشط';
        case 'inactive':
          return 'غير نشط';
        default:
          return 'غير معروف';
      }
    });
    
    const statusColor = computed(() => {
      switch (member.value.status) {
        case 'active':
          return 'success';
        case 'inactive':
          return 'grey';
        default:
          return 'grey';
      }
    });
    
    const statusIcon = computed(() => {
      switch (member.value.status) {
        case 'active':
          return 'mdi-check-circle';
        case 'inactive':
          return 'mdi-cancel';
        default:
          return 'mdi-help-circle';
      }
    });
    
    // حالة الاشتراك
    const subscriptionText = computed(() => {
      switch (member.value.subscription_status) {
        case 'paid':
          return 'مدفوع';
        case 'overdue':
          return 'متأخر';
        case 'pending':
          return 'معلق';
        default:
          return 'غير معروف';
      }
    });
    
    const subscriptionColor = computed(() => {
      switch (member.value.subscription_status) {
        case 'paid':
          return 'success';
        case 'overdue':
          return 'error';
        case 'pending':
          return 'warning';
        default:
          return 'grey';
      }
    });
    
    const subscriptionIcon = computed(() => {
      switch (member.value.subscription_status) {
        case 'paid':
          return 'mdi-cash-check';
        case 'overdue':
          return 'mdi-cash-remove';
        case 'pending':
          return 'mdi-cash-clock';
        default:
          return 'mdi-help-circle';
      }
    });
    
    // تحميل بيانات العضو
    const fetchMember = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await axios.get(`/api/members/${memberId}`);
        
        if (response.data.success) {
          member.value = response.data.data;
        } else {
          error.value = response.data.message || 'حدث خطأ أثناء تحميل بيانات العضو';
        }
      } catch (err) {
        console.error('Fetch member error:', err);
        error.value = err.response?.data?.message || 'حدث خطأ أثناء تحميل بيانات العضو';
      } finally {
        loading.value = false;
      }
    };
    
    // تحميل البيانات عند تهيئة المكون
    onMounted(() => {
      fetchMember();
    });
    
    return {
      memberId,
      member,
      loading,
      error,
      statusText,
      statusColor,
      statusIcon,
      subscriptionText,
      subscriptionColor,
      subscriptionIcon
    };
  }
};
</script>

<style scoped>
/* تخصيصات إضافية إذا لزم الأمر */
</style>
