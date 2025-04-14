import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import axios from 'axios';

// تعيين URL الأساسي للـ API
axios.defaults.baseURL = 'http://localhost:8000/api';

// إعداد التوكن من التخزين المحلي إذا كان موجودًا
const token = localStorage.getItem('token');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// إنشاء تطبيق Vue
const app = createApp(App);

// تسجيل Pinia
app.use(createPinia());

// تسجيل الموجه
app.use(router);

// تثبيت التطبيق
app.mount('#app');
