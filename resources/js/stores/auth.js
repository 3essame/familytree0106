import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    roles: [],
    permissions: []
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    getUser: (state) => state.user,
    getRoles: (state) => state.roles,
    getPermissions: (state) => state.permissions
  },
  
  actions: {
    async login(credentials) {
      try {
        // جلب CSRF token أولاً
        await this.getCsrfToken();
        
        // محاولة تسجيل الدخول
        const response = await axios.post('/login', credentials);
        
        if (response.data.success) {
          this.setAuthData(response.data);
          return true;
        }
        
        return false;
      } catch (error) {
        console.error('Login error:', error);
        return false;
      }
    },
    
    async getCsrfToken() {
      try {
        const response = await axios.get('/csrf-token');
        if (response.data.csrf_token) {
          // تعيين CSRF token في رأس الطلبات
          axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token;
          
          // إضافة CSRF token كعنصر meta في الصفحة
          let metaTag = document.head.querySelector('meta[name="csrf-token"]');
          if (!metaTag) {
            metaTag = document.createElement('meta');
            metaTag.name = 'csrf-token';
            document.head.appendChild(metaTag);
          }
          metaTag.content = response.data.csrf_token;
        }
      } catch (error) {
        console.error('Failed to fetch CSRF token:', error);
      }
    },
    
    async logout() {
      try {
        if (this.token) {
          await axios.post('/logout', {}, {
            headers: {
              'Authorization': `Bearer ${this.token}`
            }
          });
        }
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        this.clearAuthData();
      }
    },
    
    async fetchUser() {
      try {
        const response = await axios.get('/user', {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        });
        
        if (response.data.success) {
          this.user = response.data.user;
          this.roles = response.data.user.roles;
          this.permissions = response.data.user.permissions;
        }
      } catch (error) {
        console.error('Fetch user error:', error);
        this.clearAuthData();
      }
    },
    
    setAuthData(data) {
      this.token = data.token;
      this.user = data.user;
      this.roles = data.user.roles;
      this.permissions = data.user.permissions;
      
      localStorage.setItem('token', data.token);
      
      // تعيين توكن المصادقة في الطلبات القادمة
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
    },
    
    clearAuthData() {
      this.user = null;
      this.token = null;
      this.roles = [];
      this.permissions = [];
      
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
    },
    
    // التحقق من وجود صلاحية معينة
    can(permission) {
      return this.permissions.includes(permission);
    },
    
    // التحقق من وجود دور معين
    hasRole(role) {
      return this.roles.includes(role);
    }
  }
});
