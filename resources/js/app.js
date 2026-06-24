import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import PrimeVue from 'primevue/config'
import { definePreset, palette } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'
import 'primeicons/primeicons.css'
import { useAuthStore } from './stores/auth'
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Tooltip from 'primevue/tooltip';

const MyPreset = definePreset(Aura, {
    semantic: {
        primary: palette('#38819B')
    }
});

const app = createApp(App)

app.use(createPinia())

const authStore = useAuthStore()
authStore.fetchUser().then(() => {
    app.use(router)
    app.use(PrimeVue, {
        theme: {
            preset: MyPreset,

        }
    })

    app.use(ToastService)
    app.use(ConfirmationService)
    app.directive('tooltip', Tooltip)

    app.mount('#app')
})
