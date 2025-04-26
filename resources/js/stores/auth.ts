import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  roles: string[]
  permissions: string[]
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false
  }),

  actions: {
    async login(credentials: { email: string, password: string }) {
      // ... منطق تسجيل الدخول
    },
    
    logout(): void {
      this.user = null
      this.token = null
      this.isAuthenticated = false
    }
  }
})