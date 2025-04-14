<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-6">تعديل العضو</h1>
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
    
    <v-row v-else>
      <v-col cols="12">
        <v-card>
          <v-card-text>
            <v-form ref="form" @submit.prevent="updateMember">
              <v-alert
                v-if="message"
                :type="messageType"
                variant="tonal"
                class="mb-4"
                density="compact"
              >
                {{ message }}
              </v-alert>
              
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.name"
                    label="الاسم الكامل"
                    variant="outlined"
                    :rules="[v => !!v || 'الاسم مطلوب']"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.phone"
                    label="رقم الهاتف"
                    variant="outlined"
                    :rules="[v => !!v || 'رقم الهاتف مطلوب']"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.email"
                    label="البريد الإلكتروني"
                    type="email"
                    variant="outlined"
                    :rules="[
                      v => !!v || 'البريد الإلكتروني مطلوب',
                      v => /.+@.+\..+/.test(v) || 'البريد الإلكتروني غير صالح'
                    ]"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.national_id"
                    label="رقم الهوية"
                    variant="outlined"
                    :rules="[v => !!v || 'رقم الهوية مطلوب']"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.status"
                    label="الحالة"
                    :items="statusOptions"
                    variant="outlined"
                    :rules="[v => !!v || 'الحالة مطلوبة']"
                    required
                  ></v-select>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.job_title"
                    label="المسمى الوظيفي"
                    variant="outlined"
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.workplace"
                    label="مكان العمل"
                    variant="outlined"
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.address"
                    label="العنوان"
                    variant="outlined"
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.subscription_status"
                    label="حالة الاشتراك"
                    :items="subscriptionOptions"
                    variant="outlined"
                    :rules="[v => !!v || 'حالة الاشتراك مطلوبة']"
                    required
                  ></v-select>
                </v-col>
                
                <v-col cols="12">
                  <v-textarea
                    v-model="form.notes"
                    label="ملاحظات"
                    variant="outlined"
                    rows="3"
                  ></v-textarea>
                </v-col>
              </v-row>
              
              <v-row>
                <v-col cols="12" class="d-flex justify-end">
                  <v-btn
                    color="grey-darken-1"
                    variant="text"
                    class="me-4"
                    to="/members"
                  >
                    إلغاء
                  </v-btn>
                  <v-btn
                    type="submit"
                    color="primary"
                    :loading="saving"
                    :disabled="saving"
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

<script>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'MemberEdit',
  setup() {
    const router = useRouter();
    const route = useRoute();
    
    // معرف العضو
    const memberId = route.params.id;
    
    // نموذج العضو
    const form = ref({
      id: null,
      name: '',
      phone: '',
      email: '',
      national_id: '',
      status: 'active',
      job_title: '',
      workplace: '',
      address: '',
      subscription_status: 'pending',
      notes: ''
    });
    
    // مرجع النموذج
    const formRef = ref(null);
    
    // حالة التحميل
    const loading = ref(true);
    const saving = ref(false);
    
    // رسالة النجاح/الخطأ
    const message = ref('');
    const messageType = ref('success');
    
    // خيارات الحالة
    const statusOptions = [
      { title: 'نشط', value: 'active' },
      { title: 'غير نشط', value: 'inactive' }
    ];
    
    // خيارات حالة الاشتراك
    const subscriptionOptions = [
      { title: 'مدفوع', value: 'paid' },
      { title: 'متأخر', value: 'overdue' },
      { title: 'معلق', value: 'pending' }
    ];
    
    // تحميل بيانات العضو
    const fetchMember = async () => {
      loading.value = true;
      
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: const response = await axios.get(`/api/members/${memberId}`);
        
        // محاكاة تأخير الشبكة
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // محاكاة البيانات
        const memberData = {
          id: parseInt(memberId),
          name: 'أحمد محمد',
          phone: '0501234567',
          email: 'ahmed@example.com',
          national_id: '1234567890',
          status: 'active',
          job_title: 'مهندس',
          workplace: 'شركة التطوير',
          address: 'الرياض، السعودية',
          subscription_status: 'paid',
          notes: 'ملاحظات عن العضو'
        };
        
        // تعيين البيانات في النموذج
        Object.assign(form.value, memberData);
      } catch (error) {
        console.error('Fetch member error:', error);
        message.value = 'حدث خطأ أثناء تحميل بيانات العضو';
        messageType.value = 'error';
      } finally {
        loading.value = false;
      }
    };
    
    // تحديث بيانات العضو
    const updateMember = async () => {
      // التحقق من صحة النموذج
      const { valid } = await formRef.value.validate();
      
      if (!valid) {
        return;
      }
      
      saving.value = true;
      message.value = '';
      
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: const response = await axios.put(`/api/members/${memberId}`, form.value);
        
        // محاكاة تأخير الشبكة
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        message.value = 'تم تحديث بيانات العضو بنجاح';
        messageType.value = 'success';
        
        // الانتقال إلى صفحة الأعضاء بعد فترة قصيرة
        setTimeout(() => {
          router.push('/members');
        }, 1500);
      } catch (error) {
        console.error('Update member error:', error);
        message.value = error.response?.data?.message || 'حدث خطأ أثناء تحديث بيانات العضو';
        messageType.value = 'error';
      } finally {
        saving.value = false;
      }
    };
    
    // تحميل البيانات عند تهيئة المكون
    onMounted(() => {
      fetchMember();
    });
    
    return {
      form,
      formRef,
      loading,
      saving,
      message,
      messageType,
      statusOptions,
      subscriptionOptions,
      updateMember
    };
  }
};
</script>

<style scoped>
/* تخصيصات إضافية إذا لزم الأمر */
</style>
