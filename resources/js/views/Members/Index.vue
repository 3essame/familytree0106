<template>
  <v-container>
    <v-row>
      <v-col cols="12" class="d-flex justify-space-between align-center">
        <h1 class="text-h4">الأعضاء</h1>
        <div class="d-flex justify-end mb-4">
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openAddMemberDialog"
          >
            إضافة عضو جديد
          </v-btn>
          <v-btn
            color="success"
            prepend-icon="mdi-file-export"
            class="mr-2"
            @click="exportMembers"
            :loading="exporting"
          >
            تصدير البيانات
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- Debug Section -->
    <!-- <v-card class="mb-4" v-if="true">
      <v-card-title>Debug Information</v-card-title>
      <v-card-text>
        <pre>{{ JSON.stringify(debugTableData, null, 2) }}</pre>
        <pre>{{ JSON.stringify(membersWithActions, null, 2) }}</pre>
      </v-card-text>
    </v-card> -->

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
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  variant="outlined"
                  density="comfortable"
                  class="w-100"
                >
                  <v-icon start>mdi-calendar</v-icon>
                  {{ dateRangeText }}
                </v-btn>
              </template>
              <v-date-picker
                v-model="filters.dateRange"
                range
                :first-day-of-week="6"
                :show-current="true"
                :max="new Date().toISOString().substr(0, 10)"
                locale="ar-SA"
                @update:model-value="handleDateRangeChange"
                :menu-props="{ closeOnContentClick: true }"
                :close-on-content-click="true"
                :auto-apply="true"
              ></v-date-picker>
            </v-menu>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- جدول الأعضاء -->
    <v-data-table
      v-model:page="filters.page"
      v-model:items-per-page="filters.per_page"
      :headers="tableDefinition.headers"
      :items="membersWithActions"
      :loading="loading"
      :items-length="tableDefinition.totalItems"
      @update:options="handleTableUpdate"
      class="elevation-1"
      v-bind="defaultTableProps"
      :no-data-text="loading ? 'جاري التحميل...' : 'لا توجد بيانات متاحة'"
      :loading-text="'جاري التحميل...'"
    >
      <!-- عمود الاسم -->
      <template v-slot:item.name="{ item }">
        <div class="text-truncate" style="max-width: 200px; direction: rtl; text-align: right;">
          {{ item.name }}
        </div>
      </template>
      
      <!-- عمود الإجراءات -->
      <template v-slot:item.actions="{ item }">
        <div class="d-flex justify-center">
          <v-btn
            icon
            variant="text"
            color="primary"
            size="small"
            @click="viewMember(item)"
            title="عرض التفاصيل"
          >
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            color="warning"
            size="small"
            @click="editMember(item)"
            title="تعديل"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            color="error"
            size="small"
            @click="confirmDeleteMember(item)"
            title="حذف"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </div>
      </template>
      
      <template v-slot:item.avatar="{ item }">
        <v-avatar size="40">
          <v-img :src="item.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.name) + '&background=random'"></v-img>
        </v-avatar>
      </template>
      
      <template v-slot:item.status="{ item }">
        <v-chip
          :color="getStatusColor(item.status)"
          size="small"
        >
          {{ getStatusText(item.status) }}
        </v-chip>
      </template>
      
      <!-- إضافة رسالة عندما لا توجد بيانات -->
      <template v-slot:no-data>
        <div class="text-center pa-4">
          <v-icon size="48" color="grey">mdi-database-off</v-icon>
          <div class="text-h6 mt-2">لا توجد بيانات متاحة</div>
          <div class="text-body-2 text-grey">لم يتم العثور على أعضاء مطابقين لمعايير البحث</div>
          <div class="text-body-2 text-grey mt-2" v-if="loading">جاري التحميل...</div>
          <div class="text-body-2 text-grey mt-2" v-else-if="!loading && tableDefinition.items.length === 0">
            عدد العناصر: {{ tableDefinition.items.length }}
          </div>
        </div>
      </template>
    </v-data-table>

    <!-- نافذة إضافة عضو جديد -->
    <v-dialog v-model="addDialog" max-width="800px" class="rtl-dialog">
      <v-card class="rtl-dialog">
        <v-card-title class="text-h5">إضافة عضو جديد</v-card-title>
        <v-card-text>
          <v-container class="rtl-dialog">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.name"
                  label="الاسم الكامل"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.phone"
                  label="رقم الهاتف"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.email"
                  label="البريد الإلكتروني"
                  type="email"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.national_id"
                  label="رقم الهوية"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.membership_number"
                  label="رقم العضوية"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="memberForm.status"
                  label="الحالة"
                  :items="statusOptions"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.job_title"
                  label="المسمى الوظيفي"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.workplace"
                  label="مكان العمل"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="memberForm.address"
                  label="العنوان"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="memberForm.notes"
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

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="addDialog = false"
          >
            إلغاء
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="saveMember"
            :loading="saving"
            class="ml-2"
          >
            حفظ
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- نافذة تعديل بيانات العضو -->
    <v-dialog v-model="editDialog" max-width="800px" class="rtl-dialog">
      <v-card>
        <v-card-title class="text-h5">
          تعديل بيانات العضو
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
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.phone"
                  label="رقم الهاتف"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.email"
                  label="البريد الإلكتروني"
                  type="email"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.national_id"
                  label="رقم الهوية"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.membership_number"
                  label="رقم العضوية"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  readonly
                  disabled
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editForm.status"
                  :items="statusOptions"
                  label="الحالة"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  required
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.job_title"
                  label="المسمى الوظيفي"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.workplace"
                  label="مكان العمل"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.address"
                  label="العنوان"
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
            @click="saveEditMember"
            :loading="saving"
          >
            حفظ التغييرات
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- نافذة تأكيد الحذف -->
    <v-dialog v-model="deleteDialog" max-width="500px" class="rtl-dialog">
      <v-card class="rtl-dialog">
        <v-card-title class="text-h5">تأكيد الحذف</v-card-title>
        <v-card-text>
          هل أنت متأكد من رغبتك في حذف هذا العضو؟ هذا الإجراء لا يمكن التراجع عنه.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            variant="flat"
            @click="deleteMember"
            :loading="deleting"
            class="ml-2"
          >
            حذف
          </v-btn>
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="deleteDialog = false"
          >
            إلغاء
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { useRtl } from '@/composables/useRtl';
import { useRtlTable } from '@/composables/useRtlTable';
import { useRouter } from 'vue-router';

const router = useRouter();
const { isRtl } = useRtl();
const loading = ref(false);
const deleting = ref(false);
const saving = ref(false);
const search = ref('');
const filters = reactive({
  status: '',
  dateRange: null,
  page: 1,
  per_page: 10
});
const itemsPerPage = ref(10);

// Status options
const statusOptions = [
  { title: 'نشط', value: 'active' },
  { title: 'غير نشط', value: 'inactive' }
];

// Member form
const memberForm = ref({
  name: '',
  phone: '',
  email: '',
  national_id: '',
  status: 'active',
  job_title: '',
  workplace: '',
  address: '',
  notes: '',
  membership_number: ''
});

// Dialogs
const addDialog = ref(false);
const editDialog = ref(false);
const deleteDialog = ref(false);

// Table definition
const tableDefinition = ref({
  headers: [
    { title: 'الاسم', key: 'name', sortable: true, width: '200px' },
    { title: 'رقم الهاتف', key: 'phone', sortable: true },
    { title: 'البريد الإلكتروني', key: 'email', sortable: true },
    { title: 'رقم الهوية', key: 'national_id', sortable: true },
    { title: 'الحالة', key: 'status', sortable: true },
    { title: 'المسمى الوظيفي', key: 'job_title', sortable: true },
    { title: 'مكان العمل', key: 'workplace', sortable: true },
    { title: 'العنوان', key: 'address', sortable: true },
    { title: 'ملاحظات', key: 'notes', sortable: true },
    { title: 'الإجراءات', key: 'actions', sortable: false, align: 'center' }
  ],
  items: [],
  totalItems: 0
});

// Debug computed
const debugTableData = computed(() => {
  console.log('Debug - Table Definition:', {
    items: tableDefinition.value.items,
    totalItems: tableDefinition.value.totalItems,
    headers: tableDefinition.value.headers
  });
  return tableDefinition.value;
});

// RTL table setup
const { defaultTableProps } = useRtlTable(tableDefinition.value.headers, {
  tableProps: {
    hover: true,
    'fixed-header': true,
    'items-per-page': itemsPerPage.value,
    'return-object': true
  }
});

const memberToDelete = ref(null);
const exporting = ref(false);
const editForm = ref({
  id: null,
  name: '',
  phone: '',
  email: '',
  national_id: '',
  status: 'active',
  job_title: '',
  workplace: '',
  address: '',
  notes: '',
  membership_number: ''
});

// Computed properties
const membersWithActions = computed(() => {
  console.log('Computing membersWithActions from:', tableDefinition.value.items);
  
  if (!Array.isArray(tableDefinition.value.items)) {
    console.warn('tableDefinition.items is not an array:', tableDefinition.value.items);
    return [];
  }
  
  return tableDefinition.value.items.map(item => ({
    ...item,
    actions: true,
    name: item.name || 'غير محدد',
    phone: item.phone || 'غير محدد',
    email: item.email || 'غير محدد',
    national_id: item.national_id || 'غير محدد',
    status: item.status || 'غير محدد',
    job_title: item.job_title || 'غير محدد',
    workplace: item.workplace || 'غير محدد',
    address: item.address || 'غير محدد',
    notes: item.notes || 'غير محدد'
  }));
});

const dateRangeText = computed(() => {
  if (!filters.dateRange) {
    return 'اختر نطاق التاريخ';
  }
  
  if (Array.isArray(filters.dateRange)) {
    const dates = filters.dateRange
      .filter(date => date)
      .map(date => {
        const [year, month, day] = date.split('-');
        return `${day}/${month}/${year}`;
      });
    
    if (dates.length === 0) return 'اختر نطاق التاريخ';
    if (dates.length === 1) return dates[0];
    return `${dates[0]} - ${dates[1]}`;
  }
  
  return 'اختر نطاق التاريخ';
});

// Update handleDateRangeChange function
const handleDateRangeChange = (newValue) => {
  console.log('Date range changed:', newValue);
  filters.dateRange = newValue;
  
  if (Array.isArray(newValue) && newValue.length === 2) {
    fetchMembers();
  }
};

// Methods
const fetchMembers = async () => {
  loading.value = true;
  tableDefinition.value.items = [];
  
  try {
    const params = {
      search: search.value,
      status: filters.status,
      page: filters.page,
      per_page: filters.per_page
    };

    if (Array.isArray(filters.dateRange) && filters.dateRange.length === 2) {
      params.start_date = filters.dateRange[0];
      params.end_date = filters.dateRange[1];
    }

    const response = await axios.get('/members', { params });
    
    if (response.data?.success && response.data?.data?.data) {
      tableDefinition.value.items = response.data.data.data;
      tableDefinition.value.totalItems = response.data.data.total;
    } else if (Array.isArray(response.data?.data)) {
      tableDefinition.value.items = response.data.data;
      tableDefinition.value.totalItems = response.data.total || response.data.data.length;
    } else if (Array.isArray(response.data)) {
      tableDefinition.value.items = response.data;
      tableDefinition.value.totalItems = response.data.length;
    } else {
      tableDefinition.value.items = [];
      tableDefinition.value.totalItems = 0;
    }
    
    tableDefinition.value = { ...tableDefinition.value };
  } catch (error) {
    console.error('Error fetching members:', error);
    if (error.response?.status === 401) {
      router.push('/login');
    }
    tableDefinition.value.items = [];
    tableDefinition.value.totalItems = 0;
  } finally {
    loading.value = false;
  }
};

const debouncedSearch = debounce(() => {
  fetchMembers();
}, 500);

const confirmDeleteMember = (item) => {
  memberToDelete.value = item;
  deleteDialog.value = true;
};

const deleteMember = async () => {
  if (!memberToDelete.value) return;
  
  deleting.value = true;
  try {
    await axios.delete(`/members/${memberToDelete.value.id}`);
    await fetchMembers();
    deleteDialog.value = false;
    memberToDelete.value = null;
  } catch (error) {
    console.error('Delete member error:', error);
  } finally {
    deleting.value = false;
  }
};

const getStatusColor = (status) => {
  switch (status) {
    case 'active': return 'success';
    case 'inactive': return 'error';
    default: return 'grey';
  }
};

const getStatusText = (status) => {
  switch (status) {
    case 'active': return 'نشط';
    case 'inactive': return 'غير نشط';
    default: return 'غير معروف';
  }
};

const openAddMemberDialog = () => {
  resetMemberForm();
  addDialog.value = true;
};

const resetMemberForm = () => {
  memberForm.value = {
    name: '',
    phone: '',
    email: '',
    national_id: '',
    status: 'active',
    job_title: '',
    workplace: '',
    address: '',
    notes: '',
    membership_number: ''
  };
};

const saveMember = async () => {
  saving.value = true;
  try {
    const memberData = { ...memberForm.value };
    const response = await axios.post('/members', memberData);
    
    if (response.data?.success) {
      alert('تم إضافة العضو بنجاح');
      await fetchMembers();
      addDialog.value = false;
      resetMemberForm();
    }
  } catch (error) {
    console.error('Save member error:', error);
    if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat();
      alert(`خطأ في التحقق من البيانات:\n${errorMessages.join('\n')}`);
    } else if (error.response?.data?.message) {
      alert(`خطأ: ${error.response.data.message}`);
    } else {
      alert('حدث خطأ أثناء إضافة العضو');
    }
  } finally {
    saving.value = false;
  }
};

const editMember = (item) => {
  editForm.value = {
    id: item.id,
    name: item.name,
    phone: item.phone,
    email: item.email,
    national_id: item.national_id,
    status: item.status,
    job_title: item.job_title || '',
    workplace: item.workplace || '',
    address: item.address || '',
    notes: item.notes || '',
    membership_number: item.membership_number || ''
  };
  editDialog.value = true;
};

const saveEditMember = async () => {
  saving.value = true;
  try {
    const editData = { ...editForm.value };
    const response = await axios.put(`/members/${editForm.value.id}`, editData);
    
    if (response.data?.success) {
      alert('تم تحديث بيانات العضو بنجاح');
      await fetchMembers();
      editDialog.value = false;
    }
  } catch (error) {
    console.error('Save member edit error:', error);
    if (error.response?.data?.message) {
      alert(`خطأ: ${error.response.data.message}`);
    } else {
      alert('حدث خطأ أثناء تحديث بيانات العضو');
    }
  } finally {
    saving.value = false;
  }
};

const viewMember = (item) => {
  console.log('View member:', item);
};

const handleTableUpdate = (options) => {
  filters.page = options.page;
  filters.per_page = options.itemsPerPage;
  fetchMembers();
};

const exportMembers = async () => {
  exporting.value = true;
  try {
    const params = {
      search: search.value,
      status: filters.status,
      export: true,
      ...(filters.dateRange && Array.isArray(filters.dateRange) && filters.dateRange.length === 2 && {
        start_date: filters.dateRange[0],
        end_date: filters.dateRange[1]
      })
    };

    const response = await axios.get('/members/export', {
      params,
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `members-${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Error exporting members:', error);
  } finally {
    exporting.value = false;
  }
};

// Watchers
watch([() => filters.status], () => {
  fetchMembers();
});

watch(search, () => {
  debouncedSearch();
});

// Add watcher for dateRange changes
watch(() => filters.dateRange, (newValue) => {
  if (newValue && newValue.length === 2) {
    fetchMembers();
  }
}, { deep: true });

// Lifecycle hooks
onMounted(() => {
  const token = localStorage.getItem('token');
  console.log('Auth token present:', !!token);
  console.log('Axios headers:', axios.defaults.headers.common);
  fetchMembers();
});
</script>

<style scoped>
.rtl-dialog {
  direction: rtl;
}
</style>