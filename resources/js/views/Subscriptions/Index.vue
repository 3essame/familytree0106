<template>
  <v-container>
    <v-row>
      <v-col cols="12" class="d-flex justify-space-between align-center">
        <h1 class="text-h4">الاشتراكات</h1>
        <div class="d-flex justify-end mb-4">
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openAddSubscriptionDialog"
          >
            إضافة اشتراك جديد
          </v-btn>
          <v-btn
            color="success"
            prepend-icon="mdi-file-import"
            class="mr-2"
            @click="goToImport"
          >
            استيراد من Excel
          </v-btn>
          <v-btn
            color="info"
            prepend-icon="mdi-file-export"
            class="mr-2"
            @click="exportSubscriptions"
            :loading="exporting"
          >
            تصدير البيانات
          </v-btn>
          <v-btn
            color="primary"
            prepend-icon="mdi-file-pdf-box"
            class="mr-2"
            @click="downloadSubscriptionReport"
          >
            تحميل التقرير
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- فلاتر البحث -->
    <v-card class="mb-4">
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="search"
              label="بحث"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
              clearable
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.status"
              :items="statusOptions"
              label="الحالة"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
              clearable
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.subscription_status"
              :items="subscriptionOptions"
              label="حالة الاشتراك"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
              clearable
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.start_date"
              label="من تاريخ"
              type="date"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
              @update:model-value="handleDateChange"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.end_date"
              label="إلى تاريخ"
              type="date"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
              @update:model-value="handleDateChange"
            ></v-text-field>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- جدول الاشتراكات -->
    <v-data-table
      v-model:page="filters.page"
      v-model:items-per-page="filters.per_page"
      :headers="headers"
      :items="subscriptionsWithActions"
      :loading="loading"
      :items-length="totalItems"
      @update:options="handleTableOptionsUpdate"
      class="elevation-1"
    >
      <!-- عمود الإجراءات -->
      <template v-slot:item.actions_column="{ item }">
        <div class="d-flex justify-center">
          <v-btn
            icon
            variant="text"
            color="primary"
            size="small"
            @click="viewSubscription(item)"
            title="عرض التفاصيل"
          >
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            color="warning"
            size="small"
            @click="editSubscription(item)"
            title="تعديل"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            color="success"
            size="small"
            @click="markAsPaid(item)"
            title="تحديد كمدفوع"
            v-if="item.subscription_status !== 'paid'"
          >
            <v-icon>mdi-check-circle</v-icon>
          </v-btn>
        </div>
      </template>
      
      <!-- عمود العضو -->
      <template v-slot:item.member="{ item }">
        <div class="font-weight-medium">{{ item.member.name }}</div>
      </template>
      
      <template v-slot:item.status="{ item }">
        <v-chip
          :color="getStatusColor(item.status)"
          size="small"
        >
          {{ getStatusText(item.status) }}
        </v-chip>
      </template>
      
      <template v-slot:item.subscription_status="{ item }">
        <v-chip
          :color="getSubscriptionStatusColor(item.subscription_status)"
          size="small"
        >
          {{ getSubscriptionStatusText(item.subscription_status) }}
        </v-chip>
      </template>

      <!-- تنسيق تاريخ الدفع -->
      <template v-slot:item.payment_date="{ item }">
        {{ formatDate(item.payment_date) }}
      </template>

      <!-- تنسيق تاريخ الإنشاء -->
      <template v-slot:item.created_at="{ item }">
        {{ formatDate(item.created_at) }}
      </template>

      <!-- تنسيق تاريخ التحديث -->
      <template v-slot:item.updated_at="{ item }">
        {{ formatDate(item.updated_at) }}
      </template>
    </v-data-table>

    <!-- نافذة تعديل بيانات الاشتراك -->
    <v-dialog v-model="editDialog" max-width="800px" class="rtl-dialog">
      <v-card>
        <v-card-title class="text-h5">
          تعديل بيانات الاشتراك
        </v-card-title>

        <v-card-text>
          <v-container>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.name"
                  label="الاسم الكامل"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                  disabled
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.membership_number"
                  label="رقم العضوية"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                  disabled
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editForm.subscription_status"
                  :items="subscriptionOptions"
                  label="حالة الاشتراك"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.payment_date"
                  label="تاريخ الدفع"
                  type="date"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.amount"
                  label="المبلغ"
                  type="number"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.payment_method"
                  label="طريقة الدفع"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editForm.notes"
                  label="ملاحظات"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  rows="3"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>

        <v-card-actions class="d-flex justify-end">
          <v-btn
            color="grey"
            variant="text"
            @click="editDialog = false"
            class="ml-2"
          >
            إلغاء
          </v-btn>
          <v-btn
            color="primary"
            @click="saveEditSubscription"
            :loading="saving"
          >
            حفظ التغييرات
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- نافذة تأكيد تحديد الاشتراك كمدفوع -->
    <v-dialog v-model="markPaidDialog" max-width="500px" class="rtl-dialog">
      <v-card class="rtl-dialog">
        <v-card-title class="text-h5">تأكيد الدفع</v-card-title>
        <v-card-text>
          هل أنت متأكد من تحديد هذا الاشتراك كمدفوع؟
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="success"
            variant="flat"
            @click="confirmMarkAsPaid"
            :loading="saving"
            class="ml-2"
          >
            تأكيد
          </v-btn>
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="markPaidDialog = false"
          >
            إلغاء
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { useRtl } from '@/composables/useRtl';
import { useRtlTable } from '@/composables/useRtlTable';
import { useRouter } from 'vue-router';

export default {
  name: 'SubscriptionsIndex',
  setup() {
    const { isRtl } = useRtl();
    const router = useRouter();
    const loading = ref(false);
    const saving = ref(false);
    const search = ref('');
    const filters = reactive({
      status: '',
      subscription_status: '',
      start_date: '',
      end_date: '',
      page: 1,
      per_page: 10
    });
    const itemsPerPage = ref(10);
    
    // خيارات التصفية
    const statusOptions = [
      { title: 'نشط', value: 'active' },
      { title: 'غير نشط', value: 'inactive' }
    ];
    
    const subscriptionOptions = [
      { title: 'مدفوع', value: 'paid' },
      { title: 'متأخر', value: 'overdue' },
      { title: 'معلق', value: 'pending' }
    ];
    
    // تنسيق التاريخ
    const formatDate = (date) => {
      if (!date) return '-';
      const d = new Date(date);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear();
      return `${day}-${month}-${year}`;
    };

    // جدول الاشتراكات
    const tableDefinition = [  
      { title: 'الإجراءات', key: 'actions_column', align: 'center', sortable: false, width: '120px', class: 'actions-column' },
      { title: 'حالة الاشتراك', key: 'subscription_status', align: 'center', sortable: true },
      { title: 'تاريخ الدفع', key: 'payment_date', align: 'center', sortable: true },
      { title: 'المبلغ', key: 'amount', align: 'center', sortable: true },
      { title: 'طريقة الدفع', key: 'payment_method', align: 'start', sortable: true },
      { title: 'ملاحظات', key: 'notes', align: 'start', sortable: true },
      { title: 'العضو', key: 'member', align: 'start', sortable: true },
      { title: 'تاريخ الإنشاء', key: 'created_at', align: 'center', sortable: true },
      { title: 'تاريخ التحديث', key: 'updated_at', align: 'center', sortable: true }
    ];

    // استخدام composable للجداول مع دعم RTL
    const { tableHeaders, defaultTableProps } = useRtlTable(tableDefinition, {
      tableProps: {
        hover: true,
        'fixed-header': true,
        'items-per-page': itemsPerPage.value
      }
    });
    
    // قائمة الاشتراكات
    const subscriptions = ref([]);
    
    // إضافة عمود الإجراءات لكل عنصر
    const subscriptionsWithActions = computed(() => {
      return subscriptions.value.map(subscription => {
        const memberData = subscription.member || {};
        return {
          ...subscription,
          actions_column: true,
          member: {
            name: memberData.name || 'غير معروف',
            membership_number: memberData.membership_number || 'غير معروف'
          }
        };
      });
    });
    
    // حالة التصدير
    const exporting = ref(false);
    const totalItems = ref(0);
    
    // حالة نافذة التعديل
    const editDialog = ref(false);
    const editForm = reactive({
      id: null,
      name: '',
      membership_number: '',
      subscription_status: 'pending',
      payment_date: '',
      amount: '',
      payment_method: '',
      notes: ''
    });
    
    // حالة نافذة تأكيد تحديد الاشتراك كمدفوع
    const markPaidDialog = ref(false);
    const subscriptionToMarkPaid = ref(null);
    
    // تحميل البيانات
    const fetchSubscriptions = async () => {
      loading.value = true;
      subscriptions.value = [];
      
      try {
        const params = {
          search: search.value,
          status: filters.status,
          subscription_status: filters.subscription_status,
          page: filters.page,
          per_page: filters.per_page
        };

        if (filters.start_date && filters.end_date) {
          params.start_date = filters.start_date;
          params.end_date = filters.end_date;
        }

        const response = await axios.get('/subscriptions', { params });
        console.log('API Response:', response.data);
        
        if (response.data?.success && response.data?.data?.data) {
          subscriptions.value = response.data.data.data;
          totalItems.value = response.data.data.total;
        } else {
          console.warn('Unexpected API response format:', response.data);
          subscriptions.value = [];
          totalItems.value = 0;
        }
      } catch (error) {
        console.error('Error fetching subscriptions:', error);
        subscriptions.value = [];
        totalItems.value = 0;
      } finally {
        loading.value = false;
      }
    };
    
    // دالة البحث مع تأخير
    const debouncedSearch = debounce(() => {
      fetchSubscriptions();
    }, 500);
    
    // Watch for filter changes
    watch([() => filters.status, () => filters.subscription_status], () => {
      fetchSubscriptions();
    });
    
    // الحصول على لون حالة العضو
    const getStatusColor = (status) => {
      switch (status) {
        case 'active':
          return 'success';
        case 'inactive':
          return 'error';
        default:
          return 'grey';
      }
    };
    
    // الحصول على نص حالة العضو
    const getStatusText = (status) => {
      switch (status) {
        case 'active':
          return 'نشط';
        case 'inactive':
          return 'غير نشط';
        default:
          return 'غير معروف';
      }
    };
    
    // الحصول على لون حالة الاشتراك
    const getSubscriptionStatusColor = (status) => {
      switch (status) {
        case 'paid':
          return 'success';
        case 'overdue':
          return 'error';
        case 'pending':
          return 'warning';
        default:
          return 'grey';
      }
    };
    
    // الحصول على نص حالة الاشتراك
    const getSubscriptionStatusText = (status) => {
      switch (status) {
        case 'paid':
          return 'مدفوع';
        case 'overdue':
          return 'متأخر';
        case 'pending':
          return 'معلق';
        default:
          return 'غير معروف';
      }
    };
    
    // تحميل البيانات عند تهيئة المكون
    onMounted(() => {
      fetchSubscriptions();
    });
    
    // عرض تفاصيل الاشتراك
    const viewSubscription = (item) => {
      router.push(`/subscriptions/${item.id}`);
    };
    
    // تعديل الاشتراك
    const editSubscription = (item) => {
      router.push(`/subscriptions/${item.id}/edit`);
    };
    
    // الانتقال إلى صفحة إضافة اشتراك جديد
    const openAddSubscriptionDialog = () => {
      router.push('/subscriptions/create');
    };
    
    // تحديد الاشتراك كمدفوع
    const markAsPaid = (item) => {
      subscriptionToMarkPaid.value = item;
      markPaidDialog.value = true;
    };
    
    // تأكيد تحديد الاشتراك كمدفوع
    const confirmMarkAsPaid = async () => {
      if (!subscriptionToMarkPaid.value) return;
      
      saving.value = true;
      
      try {
        const response = await axios.put(`/subscriptions/${subscriptionToMarkPaid.value.id}`, {
          subscription_status: 'paid',
          payment_date: new Date().toISOString().split('T')[0]
        });
        
        if (response.data && response.data.success) {
          // إظهار رسالة نجاح
          alert('تم تحديد الاشتراك كمدفوع بنجاح');
          await fetchSubscriptions();
          markPaidDialog.value = false;
          subscriptionToMarkPaid.value = null;
        }
      } catch (error) {
        console.error('Mark as paid error:', error);
        
        // إظهار رسالة خطأ
        if (error.response && error.response.data && error.response.data.message) {
          alert(`خطأ: ${error.response.data.message}`);
        } else {
          alert('حدث خطأ أثناء تحديد الاشتراك كمدفوع');
        }
      } finally {
        saving.value = false;
      }
    };
    
    // معالجة تحديث خيارات الجدول
    const handleTableOptionsUpdate = (options) => {
      filters.page = options.page;
      filters.per_page = options.itemsPerPage;
      fetchSubscriptions();
    };
    
    // تصدير بيانات الاشتراكات
    const exportSubscriptions = async () => {
      exporting.value = true;
      try {
        const params = {
          search: search.value,
          status: filters.status,
          subscription_status: filters.subscription_status,
          export: true
        };

        if (filters.start_date && filters.end_date) {
          params.start_date = filters.start_date;
          params.end_date = filters.end_date;
        }

        const response = await axios.get('/subscriptions/export', {
          params,
          responseType: 'blob'
        });

        // إنشاء رابط تحميل
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `subscriptions-${new Date().toISOString().split('T')[0]}.xlsx`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Error exporting subscriptions:', error);
      } finally {
        exporting.value = false;
      }
    };
    
    // تحميل تقرير الاشتراكات
    const downloadSubscriptionReport = async () => {
      try {
        const params = {};
        if (filters.start_date) {
          params.start_date = filters.start_date;
        }
        if (filters.end_date) {
          params.end_date = filters.end_date;
        }

        const response = await axios.get('/reports/subscriptions', {
          params,
          responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `subscription-report-${new Date().toISOString().split('T')[0]}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Error downloading report:', error);
      }
    };
    
    // معالجة تغيير نطاق التاريخ
    const handleDateChange = () => {
      fetchSubscriptions();
    };

    // Add to the script section
    const goToImport = () => {
      router.push('/subscriptions/import');
    };
    
    return {
      loading,
      saving,
      search,
      filters,
      statusOptions,
      subscriptionOptions,
      headers: tableHeaders,
      subscriptions,
      subscriptionsWithActions,
      editDialog,
      editForm,
      editSubscription,
      viewSubscription,
      markPaidDialog,
      markAsPaid,
      confirmMarkAsPaid,
      exporting,
      totalItems,
      handleTableOptionsUpdate,
      exportSubscriptions,
      downloadSubscriptionReport,
      getStatusColor,
      getStatusText,
      getSubscriptionStatusColor,
      getSubscriptionStatusText,
      formatDate,
      handleDateChange,
      openAddSubscriptionDialog,
      goToImport
    };
  }
};
</script>

<style scoped>
:deep(.v-data-table) {
  /* Ensure RTL layout */
  direction: rtl;
}

:deep(.v-data-table--rtl) {
  /* Additional RTL-specific styles if needed */
  .v-data-table__wrapper {
    direction: rtl;
  }
}

/* RTL Dialog Styles */
.rtl-dialog {
  direction: rtl;
  text-align: right;
}

:deep(.rtl-dialog) {
  .v-card-title {
    justify-content: flex-start;
  }
  
  .v-card-text {
    text-align: right;
  }
  
  .v-label {
    right: 0;
    left: auto;
    transform-origin: right;
  }
  
  .v-text-field__suffix {
    padding-right: 8px;
    padding-left: 0;
  }
  
  .v-select__selection {
    margin-right: 0;
    margin-left: 8px;
  }
  
  .v-list-item__prepend {
    margin-left: 16px;
    margin-right: 0;
  }
  
  .v-card-actions {
    justify-content: flex-start;
    flex-direction: row-reverse;
  }
  
  .v-btn--icon {
    margin-left: 0;
    margin-right: 8px;
  }
}

/* Actions column styles */
:deep(.v-data-table__td) {
  &.actions-column {
    width: 120px;
    min-width: 120px;
    max-width: 120px;
  }
}

:deep(.v-data-table__th) {
  &.actions-column {
    width: 120px;
    min-width: 120px;
    max-width: 120px;
  }
}

/* Force display of actions column */
:deep(.v-data-table__td[data-column-key="actions_column"]) {
  display: table-cell !important;
  width: 120px !important;
}

:deep(.v-data-table__th[data-column-key="actions_column"]) {
  display: table-cell !important;
  width: 120px !important;
}
</style> 