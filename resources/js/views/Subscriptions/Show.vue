<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>تفاصيل الاشتراك</span>
            <div>
              <v-btn
                color="primary"
                variant="text"
                @click="editSubscription"
                class="ml-2"
              >
                <v-icon start>mdi-pencil</v-icon>
                تعديل
              </v-btn>
              <v-btn
                color="primary"
                variant="text"
                @click="goBack"
              >
                <v-icon start>mdi-arrow-right</v-icon>
                العودة للقائمة
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text v-if="loading">
            <v-row class="justify-center">
              <v-col cols="12" class="text-center">
                <v-progress-circular
                  indeterminate
                  color="primary"
                ></v-progress-circular>
              </v-col>
            </v-row>
          </v-card-text>

          <v-card-text v-else-if="subscription">
            <v-row>
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="mb-4">
                  <v-card-title class="text-subtitle-1 font-weight-bold">
                    معلومات العضو
                  </v-card-title>
                  <v-card-text>
                    <v-row>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">الاسم</div>
                        <div class="text-body-1">{{ subscription.member_name }}</div>
                      </v-col>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">رقم العضوية</div>
                        <div class="text-body-1">{{ subscription.membership_number }}</div>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>
              
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="mb-4">
                  <v-card-title class="text-subtitle-1 font-weight-bold">
                    معلومات الاشتراك
                  </v-card-title>
                  <v-card-text>
                    <v-row>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">المبلغ</div>
                        <div class="text-body-1">{{ subscription.amount }}</div>
                      </v-col>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">الحالة</div>
                        <v-chip
                          :color="getStatusColor(subscription.status)"
                          size="small"
                          class="mt-1"
                        >
                          {{ getStatusText(subscription.status) }}
                        </v-chip>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>
              
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="mb-4">
                  <v-card-title class="text-subtitle-1 font-weight-bold">
                    معلومات الدفع
                  </v-card-title>
                  <v-card-text>
                    <v-row>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">تاريخ الدفع</div>
                        <div class="text-body-1">{{ subscription.payment_date }}</div>
                      </v-col>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">طريقة الدفع</div>
                        <div class="text-body-1">{{ subscription.payment_method }}</div>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>
              
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="mb-4">
                  <v-card-title class="text-subtitle-1 font-weight-bold">
                    معلومات إضافية
                  </v-card-title>
                  <v-card-text>
                    <v-row>
                      <v-col cols="12">
                        <div class="text-caption text-grey">ملاحظات</div>
                        <div class="text-body-1">{{ subscription.notes || 'لا توجد ملاحظات' }}</div>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>

          <v-card-text v-else>
            <v-alert
              type="error"
              title="خطأ"
              text="لم يتم العثور على بيانات الاشتراك"
            ></v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const loading = ref(true);
const subscription = ref(null);

const statusOptions = [
  { title: 'مدفوع', value: 'paid' },
  { title: 'غير مدفوع', value: 'unpaid' },
  { title: 'متأخر', value: 'overdue' }
];

const getStatusColor = (status) => {
  switch (status) {
    case 'paid':
      return 'success';
    case 'unpaid':
      return 'error';
    case 'overdue':
      return 'warning';
    default:
      return 'grey';
  }
};

const getStatusText = (status) => {
  const option = statusOptions.find(opt => opt.value === status);
  return option ? option.title : status;
};

const fetchSubscription = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/subscriptions/${route.params.id}`);
    
    if (response.data && response.data.data) {
      subscription.value = response.data.data;
    } else {
      toast.error('لم يتم العثور على بيانات الاشتراك');
    }
  } catch (error) {
    console.error('Error fetching subscription:', error);
    toast.error('حدث خطأ أثناء تحميل بيانات الاشتراك');
  } finally {
    loading.value = false;
  }
};

const editSubscription = () => {
  router.push(`/subscriptions/${route.params.id}/edit`);
};

const goBack = () => {
  router.push('/subscriptions');
};

onMounted(() => {
  fetchSubscription();
});
</script>

<style scoped>
.rtl-dialog {
  direction: rtl;
  text-align: right;
}
</style> 