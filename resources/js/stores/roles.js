import { defineStore } from 'pinia';
import axios from 'axios';

export const useRolesStore = defineStore('roles', {
  state: () => ({
    roles: [],
    permissions: [],
    currentRole: null,
    loading: false,
    error: null
  }),
  
  getters: {
    getRoles: (state) => state.roles,
    getPermissions: (state) => state.permissions,
    getCurrentRole: (state) => state.currentRole,
    isLoading: (state) => state.loading
  },
  
  actions: {
    async fetchRoles() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/roles');
        
        if (response.data.success) {
          this.roles = response.data.roles;
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء جلب بيانات الأدوار';
          console.error('Error response:', response.data);
        }
      } catch (error) {
        console.error('Error fetching roles:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء جلب بيانات الأدوار';
      } finally {
        this.loading = false;
      }
    },
    
    async fetchPermissions() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/roles/permissions');
        
        if (response.data.success) {
          this.permissions = response.data.permissions;
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء جلب بيانات الصلاحيات';
          console.error('Error response:', response.data);
        }
      } catch (error) {
        console.error('Error fetching permissions:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء جلب بيانات الصلاحيات';
      } finally {
        this.loading = false;
      }
    },
    
    async fetchRole(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/roles/${id}`);
        
        if (response.data.success) {
          this.currentRole = response.data.role;
          return response.data.role;
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء جلب بيانات الدور';
          return null;
        }
      } catch (error) {
        console.error('Error fetching role:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء جلب بيانات الدور';
        return null;
      } finally {
        this.loading = false;
      }
    },
    
    async createRole(roleData) {
      this.loading = true;
      this.error = null;
      
      try {
        console.log('Creating role with data:', roleData);
        const response = await axios.post('/roles', roleData);
        console.log('Create role response:', response.data);
        
        if (response.data.success) {
          this.roles.push(response.data.role);
          return { success: true, role: response.data.role };
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء إنشاء الدور';
          return { success: false, error: this.error };
        }
      } catch (error) {
        console.error('Error creating role:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء إنشاء الدور';
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async updateRole(id, roleData) {
      this.loading = true;
      this.error = null;
      
      try {
        console.log('Updating role with data:', roleData);
        const response = await axios.put(`/roles/${id}`, roleData);
        console.log('Update role response:', response.data);
        
        if (response.data.success) {
          const index = this.roles.findIndex(role => role.id === id);
          if (index !== -1) {
            this.roles[index] = response.data.role;
          }
          return { success: true, role: response.data.role };
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء تحديث الدور';
          return { success: false, error: this.error };
        }
      } catch (error) {
        console.error('Error updating role:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء تحديث الدور';
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async deleteRole(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.delete(`/roles/${id}`);
        
        if (response.data.success) {
          this.roles = this.roles.filter(role => role.id !== id);
          return { success: true };
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء حذف الدور';
          return { success: false, error: this.error };
        }
      } catch (error) {
        console.error('Error deleting role:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء حذف الدور';
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    }
  }
});
