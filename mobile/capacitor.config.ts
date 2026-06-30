import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.wevlra.vapegeh',
  appName: 'VapeGeh',
  webDir: 'www',
  server: {
    url: 'https://vapegeh.wevlra.dev',
    androidScheme: 'https',
    allowNavigation: ['*'],
  },
  android: {
    allowMixedContent: true,
  },
};

export default config;
