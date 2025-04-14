<template>
  <v-container class="dashboard">
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-6">لوحة التحكم</h1>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col v-if="can('view members')" cols="12" sm="6" md="3">
        <v-card class="mx-auto" elevation="2">
          <v-card-item>
            <template v-slot:prepend>
              <v-icon size="large" color="primary" icon="mdi-account-group"></v-icon>
            </template>
            <v-card-title>الأعضاء</v-card-title>
            <v-card-subtitle class="text-h4 font-weight-bold">{{ stats.members }}</v-card-subtitle>
          </v-card-item>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="primary" :to="'/members'">
              عرض التفاصيل
              <v-icon end icon="mdi-arrow-left"></v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
      
      <v-col v-if="can('view subscriptions')" cols="12" sm="6" md="3">
        <v-card class="mx-auto" elevation="2">
          <v-card-item>
            <template v-slot:prepend>
              <v-icon size="large" color="success" icon="mdi-credit-card"></v-icon>
            </template>
            <v-card-title>الاشتراكات</v-card-title>
            <v-card-subtitle class="text-h4 font-weight-bold">{{ stats.subscriptions }}</v-card-subtitle>
          </v-card-item>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="success" :to="'/subscriptions'">
              عرض التفاصيل
              <v-icon end icon="mdi-arrow-left"></v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
      
      <v-col v-if="can('view reports')" cols="12" sm="6" md="3">
        <v-card class="mx-auto" elevation="2">
          <v-card-item>
            <template v-slot:prepend>
              <v-icon size="large" color="info" icon="mdi-chart-bar"></v-icon>
            </template>
            <v-card-title>التقارير</v-card-title>
            <v-card-subtitle class="text-h4 font-weight-bold">{{ stats.reports }}</v-card-subtitle>
          </v-card-item>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="info" :to="'/reports'">
              عرض التفاصيل
              <v-icon end icon="mdi-arrow-left"></v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
      
      <v-col v-if="hasRole('admin')" cols="12" sm="6" md="3">
        <v-card class="mx-auto" elevation="2">
          <v-card-item>
            <template v-slot:prepend>
              <v-icon size="large" color="warning" icon="mdi-account-key"></v-icon>
            </template>
            <v-card-title>المستخدمين</v-card-title>
            <v-card-subtitle class="text-h4 font-weight-bold">{{ stats.users }}</v-card-subtitle>
          </v-card-item>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="warning" :to="'/users'">
              عرض التفاصيل
              <v-icon end icon="mdi-arrow-left"></v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
    
    <v-row class="mt-6">
      <v-col v-if="can('view members')" cols="12" md="6">
        <v-card>
          <v-card-title class="text-subtitle-1 font-weight-bold">
            آخر الأعضاء المنضمين
          </v-card-title>
          <v-card-text>
            <v-table>
              <thead>
                <tr>
                  <th class="text-right">الاسم</th>
                  <th class="text-right">البريد الإلكتروني</th>
                  <th class="text-right">تاريخ الانضمام</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(member, index) in recentMembers" :key="index">
                  <td>{{ member.name }}</td>
                  <td>{{ member.email }}</td>
                  <td>{{ member.date }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="primary" :to="'/members'">
              عرض جميع الأعضاء
              <v-icon end icon="mdi-arrow-left"></v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
      
      <v-col v-if="can('view subscriptions')" cols="12" md="6">
        <v-card>
          <v-card-title class="text-subtitle-1 font-weight-bold">
            آخر الاشتراكات
          </v-card-title>
          <v-card-text>
            <v-table>
              <thead>
                <tr>
                  <th class="text-right">العضو</th>
                  <th class="text-right">المبلغ</th>
                  <th class="text-right">التاريخ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(subscription, index) in recentSubscriptions" :key="index">
                  <td>{{ subscription.member }}</td>
                  <td>{{ subscription.amount }}</td>
                  <td>{{ subscription.date }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="success" :to="'/subscriptions'">
              عرض جميع الاشتراكات
              <v-icon end icon="mdi-arrow-left"></v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

export default {
  name: 'Dashboard',
  setup() {
    const authStore = useAuthStore();
    
    // إحصائيات
    const stats = ref({
      members: 0,
      subscriptions: 0,
      reports: 0,
      users: 0
    });
    
    // آخر الأعضاء المنضمين
    const recentMembers = ref([]);
    
    // آخر الاشتراكات
    const recentSubscriptions = ref([]);
    
    // التحقق من الصلاحيات
    const can = (permission) => {
      return authStore.can(permission);
    };
    
    // التحقق من الأدوار
    const hasRole = (role) => {
      return authStore.hasRole(role);
    };
    
    // جلب البيانات
    const fetchDashboardData = async () => {
      try {
        // هنا يمكن استبدال هذا بطلب API حقيقي
        // مثال: const response = await axios.get('/dashboard/stats');
        
        // بيانات تجريبية
        stats.value = {
          members: 125,
          subscriptions: 87,
          reports: 15,
          users: 8
        };
        
        recentMembers.value = [
          { name: 'أحمد محمد', email: 'ahmed@example.com', date: '2025-03-01' },
          { name: 'محمد علي', email: 'mohamed@example.com', date: '2025-02-28' },
          { name: 'سارة أحمد', email: 'sara@example.com', date: '2025-02-25' },
          { name: 'خالد إبراهيم', email: 'khaled@example.com', date: '2025-02-20' }
        ];
        
        recentSubscriptions.value = [
          { member: 'أحمد محمد', amount: '500 ريال', date: '2025-03-01' },
          { member: 'محمد علي', amount: '500 ريال', date: '2025-02-28' },
          { member: 'سارة أحمد', amount: '500 ريال', date: '2025-02-25' },
          { member: 'خالد إبراهيم', amount: '500 ريال', date: '2025-02-20' }
        ];
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
      }
    };
    
    onMounted(() => {
      fetchDashboardData();
    });
    
    return {
      stats,
      recentMembers,
      recentSubscriptions,
      can,
      hasRole
    };
  }
};
</script>

<style scoped>
/* تم استبدال جميع الأنماط بمكونات Vuetify */
</style>
