import { defineStore } from 'pinia';
import axios from 'axios';

export const useUsersStore = defineStore('users', {
  state: () => ({
    users: [],
    currentUser: null,
    loading: false,
    error: null
  }),
  
  getters: {
    getUsers: (state) => state.users,
    getCurrentUser: (state) => state.currentUser,
    isLoading: (state) => state.loading
  },
  
  actions: {
    async fetchUsers() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/users');
        
        if (response.data.success) {
          this.users = response.data.users;
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء جلب بيانات المستخدمين';
          console.error('Error response:', response.data);
        }
      } catch (error) {
        console.error('Error fetching users:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء جلب بيانات المستخدمين';
      } finally {
        this.loading = false;
      }
    },
    
    async fetchUser(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/users/${id}`);
        
        if (response.data.success) {
          this.currentUser = response.data.user;
          return response.data.user;
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء جلب بيانات المستخدم';
          return null;
        }
      } catch (error) {
        console.error('Error fetching user:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء جلب بيانات المستخدم';
        return null;
      } finally {
        this.loading = false;
      }
    },
    
    async createUser(userData) {
      this.loading = true;
      this.error = null;
      
      try {
        console.log('Creating user with data:', userData);
        const response = await axios.post('/users', userData);
        console.log('Create user response:', response.data);
        
        if (response.data.success) {
          this.users.push(response.data.user);
          return { success: true, user: response.data.user };
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء إنشاء المستخدم';
          return { success: false, error: this.error };
        }
      } catch (error) {
        console.error('Error creating user:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء إنشاء المستخدم';
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async updateUser(id, userData) {
      this.loading = true;
      this.error = null;
      
      try {
        console.log('Updating user with data:', userData);
        const response = await axios.put(`/users/${id}`, userData);
        console.log('Update user response:', response.data);
        
        if (response.data.success) {
          const index = this.users.findIndex(user => user.id === id);
          if (index !== -1) {
            this.users[index] = response.data.user;
          }
          return { success: true, user: response.data.user };
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء تحديث المستخدم';
          return { success: false, error: this.error };
        }
      } catch (error) {
        console.error('Error updating user:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء تحديث المستخدم';
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async deleteUser(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.delete(`/users/${id}`);
        
        if (response.data.success) {
          this.users = this.users.filter(user => user.id !== id);
          return { success: true };
        } else {
          this.error = response.data.message || 'حدث خطأ أثناء حذف المستخدم';
          return { success: false, error: this.error };
        }
      } catch (error) {
        console.error('Error deleting user:', error);
        this.error = error.response?.data?.message || 'حدث خطأ أثناء حذف المستخدم';
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    }
  }
});
