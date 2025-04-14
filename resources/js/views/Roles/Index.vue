<template>
  <v-container>
    <v-row>
      <v-col cols="12" class="d-flex justify-space-between align-center">
        <h1 class="text-h4">الأدوار والصلاحيات</h1>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openRoleDialog()"
        >
          إضافة دور جديد
        </v-btn>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-data-table
            v-bind="defaultTableProps"
            :items="roles"
            :loading="loading"
            class="elevation-1"
          >
            <template v-slot:item.permissions="{ item }">
              <v-chip-group>
                <v-chip
                  v-for="(permission, i) in item.permissions.slice(0, 3)"
                  :key="i"
                  size="small"
                  color="primary"
                  variant="outlined"
                  class="mr-1"
                >
                  {{ permission }}
                </v-chip>
                <v-chip
                  v-if="item.permissions.length > 3"
                  size="small"
                  color="grey"
                  variant="outlined"
                >
                  +{{ item.permissions.length - 3 }}
                </v-chip>
              </v-chip-group>
            </template>
            
            <template v-slot:item.actions="{ item }">
              <v-btn
                icon
                variant="text"
                size="small"
                color="primary"
                @click="openRoleDialog(item)"
              >
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                size="small"
                color="error"
                @click="confirmDeleteRole(item)"
                :disabled="item.name === 'admin'"
              >
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>
    
    <!-- حوار إضافة/تعديل الدور -->
    <v-dialog v-model="roleDialog" max-width="700px">
      <v-card>
        <v-card-title class="text-h5">
          {{ editedIndex === -1 ? 'إضافة دور جديد' : 'تعديل الدور' }}
        </v-card-title>
        
        <v-card-text>
          <v-form ref="roleForm" @submit.prevent="saveRole">
            <v-text-field
              v-model="editedItem.name"
              label="اسم الدور"
              variant="outlined"
              class="mb-3"
              :disabled="editedItem.name === 'admin'"
              :rules="[v => !!v || 'اسم الدور مطلوب']"
            ></v-text-field>
            
            <v-text-field
              v-model="editedItem.display_name"
              label="الاسم المعروض"
              variant="outlined"
              class="mb-3"
              :rules="[v => !!v || 'الاسم المعروض مطلوب']"
            ></v-text-field>
            
            <v-textarea
              v-model="editedItem.description"
              label="الوصف"
              variant="outlined"
              class="mb-3"
            ></v-textarea>
            
            <v-divider class="my-4"></v-divider>
            
            <div class="text-subtitle-1 font-weight-bold mb-3">الصلاحيات:</div>
            
            <v-row>
              <v-col
                v-for="(group, groupName) in permissionGroups"
                :key="groupName"
                cols="12"
                md="6"
              >
                <v-card variant="outlined" class="mb-4">
                  <v-card-title class="text-subtitle-2 py-2">
                    {{ groupName }}
                    <v-spacer></v-spacer>
                    <v-checkbox
                      v-model="groupSelectAll[groupName]"
                      label="تحديد الكل"
                      hide-details
                      density="compact"
                      @change="toggleGroupPermissions(groupName)"
                    ></v-checkbox>
                  </v-card-title>
                  
                  <v-divider></v-divider>
                  
                  <v-card-text>
                    <v-checkbox
                      v-for="permission in group"
                      :key="permission.name"
                      v-model="editedItem.permissions"
                      :value="permission.name"
                      :label="permission.display_name"
                      hide-details
                      class="my-1"
                      density="compact"
                    ></v-checkbox>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="closeRoleDialog"
          >
            إلغاء
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="saveRole"
            :loading="saving"
          >
            حفظ
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- حوار تأكيد الحذف -->
    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">تأكيد الحذف</v-card-title>
        <v-card-text>
          هل أنت متأكد من رغبتك في حذف هذا الدور؟ هذا الإجراء لا يمكن التراجع عنه.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="deleteDialog = false"
          >
            إلغاء
          </v-btn>
          <v-btn
            color="error"
            variant="flat"
            @click="deleteRole"
            :loading="deleting"
          >
            حذف
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRtlTable } from '@/composables/useRtlTable';

export default {
  name: 'RolesIndex',
  setup() {
    // حالة التحميل
    const loading = ref(false);
    const saving = ref(false);
    const deleting = ref(false);
    
    // جدول الأدوار
    const headers = [
      { title: 'اسم الدور', key: 'name', align: 'start', sortable: true },
      { title: 'الوصف', key: 'description', align: 'start', sortable: true },
      { title: 'عدد المستخدمين', key: 'users_count', align: 'center', sortable: true },
      { title: 'الصلاحيات', key: 'permissions', align: 'center', sortable: false },
      { title: 'الإجراءات', key: 'actions', align: 'center', sortable: false, width: '120px', fixed: true }
    ];
    
    // استخدام composable للجداول مع دعم RTL
    const { tableHeaders, defaultTableProps } = useRtlTable(headers, {
      tableProps: {
        hover: true,
        'fixed-header': true,
        'items-per-page': 10
      }
    });
    
    // قائمة الأدوار
    const roles = ref([
      {
        id: 1,
        name: 'admin',
        display_name: 'مدير النظام',
        description: 'لديه جميع الصلاحيات في النظام',
        permissions: ['view members', 'create members', 'edit members', 'delete members', 'view subscriptions', 'create subscriptions', 'edit subscriptions', 'delete subscriptions', 'view reports', 'manage settings', 'manage roles']
      },
      {
        id: 2,
        name: 'manager',
        display_name: 'مدير',
        description: 'يمكنه إدارة الأعضاء والاشتراكات',
        permissions: ['view members', 'create members', 'edit members', 'view subscriptions', 'create subscriptions', 'edit subscriptions', 'view reports']
      },
      {
        id: 3,
        name: 'accountant',
        display_name: 'محاسب',
        description: 'يمكنه إدارة الاشتراكات والتقارير',
        permissions: ['view members', 'view subscriptions', 'create subscriptions', 'edit subscriptions', 'view reports']
      },
      {
        id: 4,
        name: 'viewer',
        display_name: 'مشاهد',
        description: 'يمكنه فقط مشاهدة البيانات',
        permissions: ['view members', 'view subscriptions', 'view reports']
      }
    ]);
    
    // قائمة الصلاحيات
    const permissions = ref([
      { name: 'view members', display_name: 'عرض الأعضاء', group: 'الأعضاء' },
      { name: 'create members', display_name: 'إضافة الأعضاء', group: 'الأعضاء' },
      { name: 'edit members', display_name: 'تعديل الأعضاء', group: 'الأعضاء' },
      { name: 'delete members', display_name: 'حذف الأعضاء', group: 'الأعضاء' },
      
      { name: 'view subscriptions', display_name: 'عرض الاشتراكات', group: 'الاشتراكات' },
      { name: 'create subscriptions', display_name: 'إضافة الاشتراكات', group: 'الاشتراكات' },
      { name: 'edit subscriptions', display_name: 'تعديل الاشتراكات', group: 'الاشتراكات' },
      { name: 'delete subscriptions', display_name: 'حذف الاشتراكات', group: 'الاشتراكات' },
      
      { name: 'view reports', display_name: 'عرض التقارير', group: 'التقارير' },
      { name: 'export reports', display_name: 'تصدير التقارير', group: 'التقارير' },
      
      { name: 'manage settings', display_name: 'إدارة الإعدادات', group: 'النظام' },
      { name: 'manage roles', display_name: 'إدارة الأدوار والصلاحيات', group: 'النظام' }
    ]);
    
    // تجميع الصلاحيات حسب المجموعة
    const permissionGroups = computed(() => {
      const groups = {};
      
      permissions.value.forEach(permission => {
        if (!groups[permission.group]) {
          groups[permission.group] = [];
        }
        
        groups[permission.group].push(permission);
      });
      
      return groups;
    });
    
    // حالة تحديد الكل لكل مجموعة
    const groupSelectAll = reactive({});
    
    // حوار إضافة/تعديل الدور
    const roleDialog = ref(false);
    const editedIndex = ref(-1);
    const editedItem = ref({
      id: null,
      name: '',
      display_name: '',
      description: '',
      permissions: []
    });
    const defaultItem = {
      id: null,
      name: '',
      display_name: '',
      description: '',
      permissions: []
    };
    
    // حوار تأكيد الحذف
    const deleteDialog = ref(false);
    const roleToDelete = ref(null);
    
    // تحميل البيانات
    const fetchData = async () => {
      loading.value = true;
      
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: const response = await axios.get('/api/roles');
        // roles.value = response.data;
        
        // محاكاة تأخير الشبكة
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // البيانات مضبوطة مسبقًا
      } catch (error) {
        console.error('Fetch roles error:', error);
      } finally {
        loading.value = false;
      }
    };
    
    // فتح حوار إضافة/تعديل الدور
    const openRoleDialog = (item) => {
      if (item) {
        editedIndex.value = roles.value.findIndex(r => r.id === item.id);
        editedItem.value = Object.assign({}, item);
        
        // تحديث حالة تحديد الكل لكل مجموعة
        updateGroupSelectAllState();
      } else {
        editedIndex.value = -1;
        editedItem.value = Object.assign({}, defaultItem);
        
        // إعادة تعيين حالة تحديد الكل
        Object.keys(permissionGroups.value).forEach(group => {
          groupSelectAll[group] = false;
        });
      }
      
      roleDialog.value = true;
    };
    
    // إغلاق حوار إضافة/تعديل الدور
    const closeRoleDialog = () => {
      roleDialog.value = false;
      editedItem.value = Object.assign({}, defaultItem);
      editedIndex.value = -1;
    };
    
    // حفظ الدور
    const saveRole = async () => {
      saving.value = true;
      
      try {
        if (editedIndex.value > -1) {
          // تحديث دور موجود
          // هنا يمكن استبدال هذا بطلب API حقيقي
          // مثال: await axios.put(`/api/roles/${editedItem.value.id}`, editedItem.value);
          
          // محاكاة تأخير الشبكة
          await new Promise(resolve => setTimeout(resolve, 1000));
          
          // تحديث القائمة المحلية
          Object.assign(roles.value[editedIndex.value], editedItem.value);
        } else {
          // إضافة دور جديد
          // هنا يمكن استبدال هذا بطلب API حقيقي
          // مثال: const response = await axios.post('/api/roles', editedItem.value);
          
          // محاكاة تأخير الشبكة
          await new Promise(resolve => setTimeout(resolve, 1000));
          
          // إضافة عنصر وهمي بمعرف جديد
          const newItem = Object.assign({}, editedItem.value);
          newItem.id = roles.value.length + 1;
          roles.value.push(newItem);
        }
        
        closeRoleDialog();
      } catch (error) {
        console.error('Save role error:', error);
      } finally {
        saving.value = false;
      }
    };
    
    // تأكيد حذف الدور
    const confirmDeleteRole = (item) => {
      roleToDelete.value = item;
      deleteDialog.value = true;
    };
    
    // حذف الدور
    const deleteRole = async () => {
      if (!roleToDelete.value) return;
      
      deleting.value = true;
      
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: await axios.delete(`/api/roles/${roleToDelete.value.id}`);
        
        // محاكاة تأخير الشبكة
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // حذف من القائمة المحلية
        const index = roles.value.findIndex(r => r.id === roleToDelete.value.id);
        if (index > -1) {
          roles.value.splice(index, 1);
        }
        
        deleteDialog.value = false;
        roleToDelete.value = null;
      } catch (error) {
        console.error('Delete role error:', error);
      } finally {
        deleting.value = false;
      }
    };
    
    // تبديل حالة جميع صلاحيات المجموعة
    const toggleGroupPermissions = (groupName) => {
      const isSelected = groupSelectAll[groupName];
      const groupPermissions = permissionGroups.value[groupName].map(p => p.name);
      
      if (isSelected) {
        // إضافة جميع صلاحيات المجموعة
        groupPermissions.forEach(permission => {
          if (!editedItem.value.permissions.includes(permission)) {
            editedItem.value.permissions.push(permission);
          }
        });
      } else {
        // إزالة جميع صلاحيات المجموعة
        editedItem.value.permissions = editedItem.value.permissions.filter(
          permission => !groupPermissions.includes(permission)
        );
      }
    };
    
    // تحديث حالة تحديد الكل لكل مجموعة
    const updateGroupSelectAllState = () => {
      Object.keys(permissionGroups.value).forEach(group => {
        const groupPermissions = permissionGroups.value[group].map(p => p.name);
        const allSelected = groupPermissions.every(permission => 
          editedItem.value.permissions.includes(permission)
        );
        
        groupSelectAll[group] = allSelected;
      });
    };
    
    // تحميل البيانات عند تهيئة المكون
    onMounted(() => {
      fetchData();
    });
    
    return {
      loading,
      saving,
      deleting,
      headers: tableHeaders,
      roles,
      permissions,
      permissionGroups,
      groupSelectAll,
      roleDialog,
      editedIndex,
      editedItem,
      deleteDialog,
      openRoleDialog,
      closeRoleDialog,
      saveRole,
      confirmDeleteRole,
      deleteRole,
      toggleGroupPermissions,
      defaultTableProps
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
</style>
