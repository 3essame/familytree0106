<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-6">الإعدادات</h1>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col cols="12" md="3">
        <v-card class="mb-4">
          <v-list density="compact" nav>
            <v-list-item
              v-for="(item, i) in settingsMenu"
              :key="i"
              :value="item.value"
              :title="item.title"
              :prepend-icon="item.icon"
              @click="activeTab = item.value"
              :active="activeTab === item.value"
              rounded="lg"
            ></v-list-item>
          </v-list>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="9">
        <v-card>
          <v-window v-model="activeTab">
            <!-- إعدادات عامة -->
            <v-window-item value="general">
              <v-card-title class="text-subtitle-1 font-weight-bold">
                إعدادات عامة
              </v-card-title>
              
              <v-card-text>
                <v-form @submit.prevent="saveGeneralSettings">
                  <v-alert
                    v-if="message"
                    :type="messageType"
                    variant="tonal"
                    class="mb-4"
                    density="compact"
                  >
                    {{ message }}
                  </v-alert>
                  
                  <v-text-field
                    v-model="generalSettings.site_name"
                    label="اسم الموقع"
                    variant="outlined"
                    class="mb-3"
                  ></v-text-field>
                  
                  <v-textarea
                    v-model="generalSettings.site_description"
                    label="وصف الموقع"
                    variant="outlined"
                    class="mb-3"
                  ></v-textarea>
                  
                  <v-text-field
                    v-model="generalSettings.contact_email"
                    label="البريد الإلكتروني للتواصل"
                    type="email"
                    variant="outlined"
                    class="mb-3"
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="generalSettings.contact_phone"
                    label="رقم الهاتف للتواصل"
                    variant="outlined"
                    class="mb-6"
                  ></v-text-field>
                  
                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="loading"
                    :disabled="loading"
                  >
                    حفظ الإعدادات
                  </v-btn>
                </v-form>
              </v-card-text>
            </v-window-item>
            
            <!-- إعدادات الاشتراكات -->
            <v-window-item value="subscription">
              <v-card-title class="text-subtitle-1 font-weight-bold">
                إعدادات الاشتراكات
              </v-card-title>
              
              <v-card-text>
                <v-form @submit.prevent="saveSubscriptionSettings">
                  <v-alert
                    v-if="message"
                    :type="messageType"
                    variant="tonal"
                    class="mb-4"
                    density="compact"
                  >
                    {{ message }}
                  </v-alert>
                  
                  <v-text-field
                    v-model="subscriptionSettings.subscription_amount"
                    label="قيمة الاشتراك الشهري"
                    type="number"
                    variant="outlined"
                    class="mb-3"
                  ></v-text-field>
                  
                  <v-select
                    v-model="subscriptionSettings.subscription_currency"
                    label="عملة الاشتراك"
                    :items="currencies"
                    variant="outlined"
                    class="mb-3"
                  ></v-select>
                  
                  <v-checkbox
                    v-model="subscriptionSettings.enable_automatic_reminders"
                    label="تفعيل التذكير التلقائي بالاشتراكات"
                    color="primary"
                    class="mb-3"
                  ></v-checkbox>
                  
                  <v-select
                    v-model="subscriptionSettings.reminder_days_before"
                    label="التذكير قبل الاستحقاق بـ"
                    :items="reminderDays"
                    variant="outlined"
                    class="mb-6"
                    :disabled="!subscriptionSettings.enable_automatic_reminders"
                  ></v-select>
                  
                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="loading"
                    :disabled="loading"
                  >
                    حفظ الإعدادات
                  </v-btn>
                </v-form>
              </v-card-text>
            </v-window-item>
            
            <!-- إعدادات النظام -->
            <v-window-item value="system">
              <v-card-title class="text-subtitle-1 font-weight-bold">
                إعدادات النظام
              </v-card-title>
              
              <v-card-text>
                <v-form @submit.prevent="saveSystemSettings">
                  <v-alert
                    v-if="message"
                    :type="messageType"
                    variant="tonal"
                    class="mb-4"
                    density="compact"
                  >
                    {{ message }}
                  </v-alert>
                  
                  <v-select
                    v-model="systemSettings.items_per_page"
                    label="عدد العناصر في الصفحة"
                    :items="itemsPerPageOptions"
                    variant="outlined"
                    class="mb-3"
                  ></v-select>
                  
                  <v-checkbox
                    v-model="systemSettings.enable_logs"
                    label="تفعيل سجلات النظام"
                    color="primary"
                    class="mb-3"
                  ></v-checkbox>
                  
                  <v-select
                    v-model="systemSettings.log_level"
                    label="مستوى السجلات"
                    :items="logLevels"
                    variant="outlined"
                    class="mb-6"
                    :disabled="!systemSettings.enable_logs"
                  ></v-select>
                  
                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="loading"
                    :disabled="loading"
                  >
                    حفظ الإعدادات
                  </v-btn>
                </v-form>
              </v-card-text>
            </v-window-item>
          </v-window>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';

export default {
  name: 'Settings',
  setup() {
    // القائمة الجانبية للإعدادات
    const settingsMenu = [
      { title: 'إعدادات عامة', value: 'general', icon: 'mdi-cog' },
      { title: 'إعدادات الاشتراكات', value: 'subscription', icon: 'mdi-credit-card' },
      { title: 'إعدادات النظام', value: 'system', icon: 'mdi-server' }
    ];
    
    // التبويب النشط
    const activeTab = ref('general');
    
    // حالة التحميل
    const loading = ref(false);
    
    // رسالة النجاح/الخطأ
    const message = ref('');
    const messageType = ref('success');
    
    // إعدادات عامة
    const generalSettings = ref({
      site_name: 'نظام  اشتراكات العاملين',
      site_description: 'نظام إدارة اشتراكات العاملين',
      contact_email: 'contact@example.com',
      contact_phone: '0123456789'
    });
    
    // إعدادات الاشتراكات
    const subscriptionSettings = ref({
      subscription_amount: 2,
      subscription_currency: 'KWD',
      enable_automatic_reminders: true,
      reminder_days_before: 7
    });
    
    // إعدادات النظام
    const systemSettings = ref({
      items_per_page: 10,
      enable_logs: true,
      log_level: 'info'
    });
    
    // خيارات العملات
    const currencies = ['SAR', 'USD', 'EUR', 'KWD'];
    
    // خيارات أيام التذكير
    const reminderDays = [3, 5, 7, 10, 15, 30];
    
    // خيارات عدد العناصر في الصفحة
    const itemsPerPageOptions = [5, 10, 15, 20, 25, 50, 100];
    
    // خيارات مستوى السجلات
    const logLevels = ['debug', 'info', 'warning', 'error', 'critical'];
    
    // حفظ الإعدادات العامة
    const saveGeneralSettings = async () => {
      loading.value = true;
      message.value = '';
      
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: const response = await axios.post('/api/settings/general', generalSettings.value);
        
        // محاكاة الاستجابة
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        message.value = 'تم حفظ الإعدادات العامة بنجاح';
        messageType.value = 'success';
      } catch (error) {
        console.error('Save settings error:', error);
        message.value = 'حدث خطأ أثناء حفظ الإعدادات';
        messageType.value = 'error';
      } finally {
        loading.value = false;
      }
    };
    
    // حفظ إعدادات الاشتراكات
    const saveSubscriptionSettings = async () => {
      loading.value = true;
      message.value = '';
      
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: const response = await axios.post('/api/settings/subscription', subscriptionSettings.value);
        
        // محاكاة الاستجابة
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        message.value = 'تم حفظ إعدادات الاشتراكات بنجاح';
        messageType.value = 'success';
      } catch (error) {
        console.error('Save settings error:', error);
        message.value = 'حدث خطأ أثناء حفظ الإعدادات';
        messageType.value = 'error';
      } finally {
        loading.value = false;
      }
    };
    
    // حفظ إعدادات النظام
    const saveSystemSettings = async () => {
      loading.value = true;
      message.value = '';
      
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: const response = await axios.post('/api/settings/system', systemSettings.value);
        
        // محاكاة الاستجابة
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        message.value = 'تم حفظ إعدادات النظام بنجاح';
        messageType.value = 'success';
      } catch (error) {
        console.error('Save settings error:', error);
        message.value = 'حدث خطأ أثناء حفظ الإعدادات';
        messageType.value = 'error';
      } finally {
        loading.value = false;
      }
    };
    
    return {
      settingsMenu,
      activeTab,
      loading,
      message,
      messageType,
      generalSettings,
      subscriptionSettings,
      systemSettings,
      currencies,
      reminderDays,
      itemsPerPageOptions,
      logLevels,
      saveGeneralSettings,
      saveSubscriptionSettings,
      saveSystemSettings
    };
  }
};
</script>

<style scoped>
/* تخصيصات إضافية إذا لزم الأمر */
</style>
