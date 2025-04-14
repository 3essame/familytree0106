<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>إضافة اشتراك جديد</span>
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
            <v-form ref="form" v-model="valid" @submit.prevent="saveSubscription">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="subscriptionForm.member_id"
                    :items="members"
                    item-title="name"
                    item-value="id"
                    label="العضو"
                    variant="outlined"
                    density="comfortable"
                    :rules="[v => !!v || 'العضو مطلوب']"
                    required
                    :loading="loading"
                    :disabled="loading"
                    @update:model-value="handleMemberSelect"
                  >
                    <template v-slot:item="{ item, props }">
                      <v-list-item v-bind="props">
                        <template v-slot:title>
                          {{ item.raw.name }} ({{ item.raw.membership_number }})
                        </template>
                      </v-list-item>
                    </template>
                  </v-select>
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
                    v-model="subscriptionForm.subscription_status"
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
                    حفظ
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

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'SubscriptionCreate',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const saving = ref(false);
    const valid = ref(false);
    const form = ref(null);
    const members = ref([]);
    const subscriptionForm = ref({
      member_id: null,
      subscription_status: 'unpaid',
      payment_date: '',
      amount: '',
      payment_method: '',
      notes: ''
    });

    const statusOptions = [
      { title: 'مدفوع', value: 'paid' },
      { title: 'غير مدفوع', value: 'unpaid' },
      { title: 'متأخر', value: 'overdue' }
    ];

    // جلب قائمة الأعضاء
    const fetchMembers = async () => {
      loading.value = true;
      try {
        const response = await axios.get('/members');
        console.log('Members API response:', response.data);
        
        let rawMembers = [];
        
        // Handle different response formats
        if (response.data?.success && response.data?.data?.data) {
          // Laravel paginated response with nested data
          rawMembers = response.data.data.data;
        } else if (response.data?.success && Array.isArray(response.data?.data)) {
          // Direct array in data property
          rawMembers = response.data.data;
        } else if (Array.isArray(response.data)) {
          // Direct array response
          rawMembers = response.data;
        } else {
          console.warn('Unexpected API response format:', response.data);
          rawMembers = [];
        }

        // Format each member object to ensure consistent structure
        members.value = rawMembers.map(member => ({
          id: member.id,
          name: member.name,
          membership_number: member.membership_number
        }));
        
        console.log('Processed members:', members.value);
      } catch (error) {
        console.error('Fetch members error:', error);
        alert('حدث خطأ أثناء جلب قائمة الأعضاء');
        members.value = [];
      } finally {
        loading.value = false;
      }
    };

    // Handle member selection
    const handleMemberSelect = (selectedMember) => {
      if (selectedMember) {
        subscriptionForm.value.member_id = selectedMember;
        console.log('Selected member ID:', selectedMember);
      } else {
        subscriptionForm.value.member_id = null;
      }
    };

    // حفظ الاشتراك
    const saveSubscription = async () => {
      if (!valid.value) {
        alert('يرجى ملء جميع الحقول المطلوبة');
        return;
      }
      
      if (!subscriptionForm.value.member_id) {
        alert('يرجى اختيار العضو');
        return;
      }

      saving.value = true;
      try {
        // Log the form data to see what we're sending
        console.log('Sending subscription data:', subscriptionForm.value);
        
        // Make sure we're using the correct endpoint
        const response = await axios.post('/subscriptions', subscriptionForm.value);
        
        console.log('API response:', response.data);
        
        if (response.data && response.data.success) {
          alert('تم إضافة الاشتراك بنجاح');
          router.push('/subscriptions');
        } else {
          alert('حدث خطأ أثناء إضافة الاشتراك');
        }
      } catch (error) {
        console.error('Save subscription error:', error);
        if (error.response && error.response.data && error.response.data.message) {
          alert(`خطأ: ${error.response.data.message}`);
        } else {
          alert('حدث خطأ أثناء إضافة الاشتراك');
        }
      } finally {
        saving.value = false;
      }
    };

    const goBack = () => {
      router.push('/subscriptions');
    };

    onMounted(() => {
      fetchMembers();
    });

    return {
      loading,
      saving,
      valid,
      form,
      members,
      subscriptionForm,
      saveSubscription,
      statusOptions,
      goBack,
      handleMemberSelect
    };
  }
};
</script>

<style scoped>
.rtl-dialog {
  direction: rtl;
  text-align: right;
}
</style> 