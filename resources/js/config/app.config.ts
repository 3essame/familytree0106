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


//dr.abdrazaq
export const appConfig: AppConfig = {
  api: {
    baseURL:  window.location.protocol +
    "//" +
    window.location.hostname +
    ":" +
    window.location.port +
    "/api",
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