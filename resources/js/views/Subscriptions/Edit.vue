<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>تعديل اشتراك</span>
            <v-btn
              color="primary"
              variant="text"
              @click="goBack"
            >
              <v-icon start>mdi-arrow-right</v-icon>
              العودة للقائمة
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="updateSubscription">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="subscriptionForm.member_name"
                    label="العضو"
                    variant="outlined"
                    density="comfortable"
                    disabled
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="subscriptionForm.membership_number"
                    label="رقم العضوية"
                    variant="outlined"
                    density="comfortable"
                    disabled
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="subscriptionForm.amount"
                    label="المبلغ"
                    type="number"
                    variant="outlined"
                    density="comfortable"
                    :rules="[v => !!v || 'المبلغ مطلوب']"
                    required
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="subscriptionForm.status"
                    :items="statusOptions"
                    label="الحالة"
                    variant="outlined"
                    density="comfortable"
                    :rules="[v => !!v || 'الحالة مطلوبة']"
                    required
                  ></v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="subscriptionForm.payment_date"
                    label="تاريخ الدفع"
                    type="date"
                    variant="outlined"
                    density="comfortable"
                    :rules="[v => !!v || 'تاريخ الدفع مطلوب']"
                    required
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="subscriptionForm.payment_method"
                    label="طريقة الدفع"
                    variant="outlined"
                    density="comfortable"
                    :rules="[v => !!v || 'طريقة الدفع مطلوبة']"
                    required
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="subscriptionForm.notes"
                    label="ملاحظات"
                    variant="outlined"
                    density="comfortable"
                    rows="3"
                  ></v-textarea>
                </v-col>
              </v-row>

              <v-row class="mt-4">
                <v-col cols="12" class="d-flex justify-end">
                  <v-btn
                    color="primary"
                    variant="text"
                    @click="goBack"
                    class="ml-2"
                  >
                    إلغاء
                  </v-btn>
                  <v-btn
                    color="primary"
                    type="submit"
                    :loading="saving"
                    :disabled="!valid"
                  >
                    حفظ التغييرات
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>
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
const form = ref(null);
const valid = ref(false);
const saving = ref(false);
const loading = ref(false);

const statusOptions = [
  { title: 'مدفوع', value: 'paid' },
  { title: 'غير مدفوع', value: 'unpaid' },
  { title: 'متأخر', value: 'overdue' }
];

const subscriptionForm = reactive({
  id: null,
  member_id: null,
  member_name: '',
  membership_number: '',
  amount: '',
  status: '',
  payment_date: '',
  payment_method: '',
  notes: ''
});

const fetchSubscription = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/subscriptions/${route.params.id}`);
    
    if (response.data && response.data.data) {
      const subscription = response.data.data;
      
      // Populate the form with subscription data
      subscriptionForm.id = subscription.id;
      subscriptionForm.member_id = subscription.member_id;
      subscriptionForm.member_name = subscription.member_name || '';
      subscriptionForm.membership_number = subscription.membership_number || '';
      subscriptionForm.amount = subscription.amount || '';
      subscriptionForm.status = subscription.status || '';
      subscriptionForm.payment_date = subscription.payment_date || '';
      subscriptionForm.payment_method = subscription.payment_method || '';
      subscriptionForm.notes = subscription.notes || '';
    } else {
      toast.error('لم يتم العثور على بيانات الاشتراك');
      router.push('/subscriptions');
    }
  } catch (error) {
    console.error('Error fetching subscription:', error);
    toast.error('حدث خطأ أثناء تحميل بيانات الاشتراك');
    router.push('/subscriptions');
  } finally {
    loading.value = false;
  }
};

const updateSubscription = async () => {
  if (!form.value.validate()) return;
  
  saving.value = true;
  try {
    const response = await axios.put(`/subscriptions/${subscriptionForm.id}`, {
      amount: subscriptionForm.amount,
      status: subscriptionForm.status,
      payment_date: subscriptionForm.payment_date,
      payment_method: subscriptionForm.payment_method,
      notes: subscriptionForm.notes
    });
    
    if (response.data && response.data.success) {
      toast.success('تم تحديث الاشتراك بنجاح');
      router.push('/subscriptions');
    } else {
      toast.error('حدث خطأ أثناء تحديث الاشتراك');
    }
  } catch (error) {
    console.error('Error updating subscription:', error);
    
    if (error.response && error.response.data && error.response.data.message) {
      toast.error(`خطأ: ${error.response.data.message}`);
    } else {
      toast.error('حدث خطأ أثناء تحديث الاشتراك');
    }
  } finally {
    saving.value = false;
  }
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