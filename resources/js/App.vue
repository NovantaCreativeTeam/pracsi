<template>
    <Toast />
    <ConfirmDialog />
    <router-view v-slot="{ Component, route }">
        <component :is="getLayout(route)">
            <component :is="Component" />
        </component>
    </router-view>
</template>

<script setup>
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import AppLayout from './layouts/AppLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'

const layouts = {
    app: AppLayout,
    auth: AuthLayout,
}

const getLayout = (route) => {
    const layout = route.meta.layout || 'app'
    return layouts[layout] || AppLayout
}
</script>
