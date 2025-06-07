import axios from 'axios';
window.axios = axios;

// تعيين الرأس الافتراضي للطلبات
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';

// تعيين URL الأساسي
//dr.abdrazaq
window.axios.defaults.baseURL = window.location.protocol +
    "//" +
    window.location.hostname +
    ":" +
    window.location.port +
    "/api"

// إضافة معترض للطلبات
axios.interceptors.request.use(
  config => {
    // يمكن إضافة منطق إضافي هنا قبل إرسال الطلب
    const token = localStorage.getItem('token');
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    
    // إضافة CSRF token إذا كان الطلب يذهب إلى نفس الأصل
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
      config.headers['X-CSRF-TOKEN'] = csrfToken.content;
    }
    
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

// إضافة معترض للاستجابات
axios.interceptors.response.use(
  response => {
    return response;
  },
  error => {
    // معالجة أخطاء الاستجابة
    if (error.response) {
      // استجابة من الخادم بكود خطأ
      if (error.response.status === 401) {
        // غير مصرح به - قم بتسجيل الخروج
        localStorage.removeItem('token');
        window.location.href = '/login';
      }
      
      console.error('Response error:', error.response.data);
    } else if (error.request) {
      // لم يتم استلام استجابة
      console.error('Request error:', error.request);
    } else {
      // حدث خطأ عند إعداد الطلب
      console.error('Error:', error.message);
    }
    
    return Promise.reject(error);
  }
);
