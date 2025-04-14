<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <h2>إدارة المستخدمين</h2>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="openCreateDialog"
            >
              إضافة مستخدم جديد
            </v-btn>
          </v-card-title>
          
          <v-card-text>
            <v-alert
              v-if="error"
              type="error"
              variant="tonal"
              class="mb-4"
              density="compact"
            >
              {{ error }}
            </v-alert>
            
            <v-text-field
              v-model="search"
              label="بحث"
              prepend-inner-icon="mdi-magnify"
              single-line
              hide-details
              variant="outlined"
              density="compact"
              class="mb-4"
            ></v-text-field>
            
            <v-data-table
              v-bind="defaultTableProps"
              :items="filteredUsers"
              :loading="loading"
              class="elevation-1"
            >
              <template v-slot:item.status="{ item }">
                <v-chip
                  :color="item.status === 'active' ? 'success' : 'error'"
                  size="small"
                  class="text-white"
                >
                  {{ item.status === 'active' ? 'نشط' : 'غير نشط' }}
                </v-chip>
              </template>
              
              <template v-slot:item.roles="{ item }">
                <v-chip
                  v-for="role in item.roles"
                  :key="role.name"
                  color="primary"
                  size="small"
                  class="ml-1"
                >
                  {{ role.name }}
                </v-chip>
              </template>
              
              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon
                  variant="text"
                  color="primary"
                  size="small"
                  @click="openEditDialog(item)"
                >
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                
                <v-btn
                  icon
                  variant="text"
                  color="error"
                  size="small"
                  @click="confirmDelete(item)"
                >
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </template>
              
              <template v-slot:no-data>
                <div class="text-center py-4">
                  <v-icon size="large" icon="mdi-account-off" class="mb-2"></v-icon>
                  <p>لا توجد بيانات للعرض</p>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    
    <!-- نافذة إضافة/تعديل مستخدم -->
    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ isEditing ? 'تعديل مستخدم' : 'إضافة مستخدم جديد' }}</span>
        </v-card-title>
        
        <v-card-text>
          <v-form ref="form" @submit.prevent="saveUser">
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="formData.name"
                  label="الاسم"
                  required
                  :rules="[v => !!v || 'الاسم مطلوب']"
                ></v-text-field>
              </v-col>
              
              <v-col cols="12">
                <v-text-field
                  v-model="formData.email"
                  label="البريد الإلكتروني"
                  type="email"
                  required
                  :rules="[
                    v => !!v || 'البريد الإلكتروني مطلوب',
                    v => /.+@.+\..+/.test(v) || 'البريد الإلكتروني غير صالح'
                  ]"
                ></v-text-field>
              </v-col>
              
              <v-col cols="12">
                <v-text-field
                  v-model="formData.password"
                  label="كلمة المرور"
                  type="password"
                  :rules="passwordRules"
                ></v-text-field>
                <div v-if="isEditing" class="text-caption text-grey">
                  اترك هذا الحقل فارغًا إذا لم ترغب في تغيير كلمة المرور
                </div>
              </v-col>
              
              <v-col cols="12">
                <v-select
                  v-model="formData.status"
                  label="الحالة"
                  :items="[
                    { title: 'نشط', value: 'active' },
                    { title: 'غير نشط', value: 'inactive' }
                  ]"
                  item-title="title"
                  item-value="value"
                  required
                  :rules="[v => !!v || 'الحالة مطلوبة']"
                ></v-select>
              </v-col>
              
              <v-col cols="12">
                <v-select
                  v-model="formData.roles"
                  label="الأدوار"
                  :items="availableRoles"
                  item-title="name"
                  item-value="name"
                  multiple
                  chips
                  hint="اختر دور واحد أو أكثر للمستخدم"
                  persistent-hint
                ></v-select>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="dialog = false">إلغاء</v-btn>
          <v-btn 
            color="primary" 
            variant="elevated" 
            @click="saveUser" 
            :loading="saving"
          >
            حفظ
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- نافذة تأكيد الحذف -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">تأكيد الحذف</v-card-title>
        <v-card-text>
          هل أنت متأكد من رغبتك في حذف المستخدم "{{ userToDelete?.name }}"؟
          <div class="text-subtitle-2 mt-2 text-error">
            هذا الإجراء لا يمكن التراجع عنه.
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">إلغاء</v-btn>
          <v-btn 
            color="error" 
            variant="elevated" 
            @click="deleteUser" 
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
import { ref, computed, onMounted, reactive } from 'vue';
import { useUsersStore } from '../../stores/users';
import { useRolesStore } from '../../stores/roles';
import axios from 'axios';
import { useRtlTable } from '@/composables/useRtlTable';

export default {
  name: 'UsersIndex',
  
  setup() {
    const usersStore = useUsersStore();
    const rolesStore = useRolesStore();
    const form = ref(null);
    const search = ref('');
    const dialog = ref(false);
    const deleteDialog = ref(false);
    const isEditing = ref(false);
    const saving = ref(false);
    const deleting = ref(false);
    const userToDelete = ref(null);
    const availableRoles = ref([]);
    
    const formData = reactive({
      id: null,
      name: '',
      email: '',
      password: '',
      status: 'active',
      roles: []
    });
    
    const headers = [
      { title: 'الاسم', key: 'name', align: 'start', sortable: true },
      { title: 'البريد الإلكتروني', key: 'email', align: 'start', sortable: true },
      { title: 'الحالة', key: 'status', align: 'center', sortable: true },
      { title: 'الأدوار', key: 'roles', align: 'center', sortable: false },
      { title: 'الإجراءات', key: 'actions', align: 'center', sortable: false, width: '120px' }
    ];
    
    const passwordRules = computed(() => {
      if (isEditing.value) {
        return [];
      } else {
        return [
          v => !!v || 'كلمة المرور مطلوبة',
          v => (v && v.length >= 8) || 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'
        ];
      }
    });
    
    const filteredUsers = computed(() => {
      if (!search.value) {
        return usersStore.users;
      }
      
      const searchTerm = search.value.toLowerCase();
      return usersStore.users.filter(user => {
        return (
          user.name.toLowerCase().includes(searchTerm) ||
          user.email.toLowerCase().includes(searchTerm) ||
          user.status.toLowerCase().includes(searchTerm)
        );
      });
    });
    
    const error = computed(() => usersStore.error);
    const loading = computed(() => usersStore.loading);
    
    const { tableHeaders, defaultTableProps } = useRtlTable(headers, {
      tableProps: {
        hover: true,
        'fixed-header': true,
        'items-per-page': 10
      }
    });
    
    onMounted(async () => {
      await usersStore.fetchUsers();
      await fetchRoles();
    });
    
    const fetchRoles = async () => {
      try {
        await rolesStore.fetchRoles();
        availableRoles.value = rolesStore.getRoles;
      } catch (error) {
        console.error('Error fetching roles:', error);
      }
    };
    
    const resetForm = () => {
      formData.id = null;
      formData.name = '';
      formData.email = '';
      formData.password = '';
      formData.status = 'active';
      formData.roles = [];
      
      if (form.value) {
        form.value.resetValidation();
      }
    };
    
    const openCreateDialog = () => {
      resetForm();
      isEditing.value = false;
      dialog.value = true;
    };
    
    const openEditDialog = (user) => {
      resetForm();
      isEditing.value = true;
      
      formData.id = user.id;
      formData.name = user.name;
      formData.email = user.email;
      formData.status = user.status;
      formData.roles = user.roles.map(role => role.name);
      
      dialog.value = true;
    };
    
    const saveUser = async () => {
      if (form.value) {
        const { valid } = await form.value.validate();
        if (!valid) {
          return;
        }
      }
      
      saving.value = true;
      
      try {
        const userData = {
          name: formData.name,
          email: formData.email,
          status: formData.status,
          roles: formData.roles
        };
        
        if (formData.password) {
          userData.password = formData.password;
        }
        
        let result;
        
        if (isEditing.value) {
          result = await usersStore.updateUser(formData.id, userData);
        } else {
          result = await usersStore.createUser(userData);
        }
        
        if (result && result.success) {
          dialog.value = false;
          resetForm();
          await usersStore.fetchUsers();
        } else {
          console.error('Failed to save user:', result?.error);
        }
      } catch (error) {
        console.error('Error saving user:', error);
      } finally {
        saving.value = false;
      }
    };
    
    const confirmDelete = (user) => {
      userToDelete.value = user;
      deleteDialog.value = true;
    };
    
    const deleteUser = async () => {
      if (!userToDelete.value) return;
      
      deleting.value = true;
      
      try {
        const result = await usersStore.deleteUser(userToDelete.value.id);
        
        if (result && result.success) {
          deleteDialog.value = false;
          userToDelete.value = null;
        }
      } catch (error) {
        console.error('Error deleting user:', error);
      } finally {
        deleting.value = false;
      }
    };
    
    return {
      headers: tableHeaders,
      search,
      dialog,
      deleteDialog,
      isEditing,
      formData,
      userToDelete,
      saving,
      deleting,
      form,
      passwordRules,
      filteredUsers,
      error,
      loading,
      availableRoles,
      openCreateDialog,
      openEditDialog,
      saveUser,
      confirmDelete,
      deleteUser,
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
