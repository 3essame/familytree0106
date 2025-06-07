import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import axios from 'axios';

// Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import '@mdi/font/css/materialdesignicons.css';

// تضمين Font Awesome
import '@fortawesome/fontawesome-free/css/all.min.css';

// استيراد ملف CSS الخاص بالـ RTL
import '../css/rtl.css';
import '../css/table-rtl.css';

// تعيين URL الأساسي للـ API
//dr.abdrazaq
axios.defaults.baseURL = window.location.protocol +
    "//" +
    window.location.hostname +
    ":" +
    window.location.port +
    "/api";

// إعداد التوكن من التخزين المحلي إذا كان موجودًا
const token = localStorage.getItem('token');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Add request interceptor
axios.interceptors.request.use(
  (config) => {
    console.log('Request:', {
      method: config.method,
      url: config.url,
      headers: config.headers,
      data: config.data,
      params: config.params
    });
    return config;
  },
  (error) => {
    console.error('Request error:', error);
    return Promise.reject(error);
  }
);

// Add response interceptor
axios.interceptors.response.use(
  (response) => {
    console.log('Response2:', {
      status: response.status,
      data: response.data,
      headers: response.headers
    });
    return response;
  },
  (error) => {
    console.error('Response error3:', {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status
    });
    return Promise.reject(error);
  }
);

// إعداد Vuetify
const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#1976D2',
          secondary: '#424242',
          accent: '#82B1FF',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FFC107',
        },
      },
      dark: {
        colors: {
          primary: '#2196F3',
          secondary: '#616161',
          accent: '#82B1FF',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FFC107',
        },
      },
    },
  },
  rtl: true, // تفعيل الاتجاه من اليمين إلى اليسار للغة العربية
});

// إنشاء تطبيق Vue
const app = createApp(App);

// تسجيل Pinia
app.use(createPinia());

// تسجيل الموجه
app.use(router);

// تسجيل Vuetify
app.use(vuetify);

// تعيين اتجاه التطبيق
document.documentElement.dir = 'rtl';
document.body.dir = 'rtl';

// تحميل التطبيق
app.mount('#app');
