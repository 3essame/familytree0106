interface AppConfig {
  api: {
    baseURL: string
    timeout: number
    headers: Record<string, string>
  }
  rtl: boolean
  defaultLocale: string
  supportedLocales: string[]
}

export const appConfig: AppConfig = {
  api: {
    baseURL: 'http://localhost:8000/api',
    timeout: 30000,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
  },
  rtl: true,
  defaultLocale: 'ar',
  supportedLocales: ['ar', 'en']
}