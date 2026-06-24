<template>
    <div class="min-h-screen bg-gray-bg flex relative overflow-hidden bg-grid-pattern">
        <!-- Mobile Overlay -->
        <div
            v-if="isMobileOpen"
            class="fixed inset-0 bg-slate-400/50 backdrop-blur-[2px] z-30 lg:hidden transition-opacity duration-300"
            @click="isMobileOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            class="bg-white/90 backdrop-blur-md border-r border-slate-200 flex flex-col fixed h-full z-40 transition-all duration-300 ease-in-out shrink-0"
            :class="[
                isCollapsed ? 'w-20 min-w-[5rem]' : 'w-72 min-w-[18rem]',
                isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Logo -->
            <div class="h-16 flex items-center gap-2 px-6 border-b border-slate-100 overflow-hidden shrink-0">
                <img src="/resources/images/logo-pracsi.png" alt="Pracsi Logo" class="w-10 min-w-[2.5rem]" />
                <span v-show="!isCollapsed" class="text-xl font-bold text-primary whitespace-nowrap transition-opacity duration-300">
                    Pracsi
                </span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1 custom-scrollbar">
                <div v-show="!isCollapsed" class="px-3 pb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider whitespace-nowrap">
                    Menu Principale
                </div>
                <div v-show="isCollapsed" class="h-6 flex items-center justify-center">
                    <div class="w-4 border-b border-slate-200"></div>
                </div>

                <RouterLink
                    to="/"
                    class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors group relative"
                    :class="$route.name === 'dashboard' ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                    v-tooltip.right="isCollapsed ? 'Dashboard' : null"
                >
                    <i class="pi pi-home text-lg" :class="$route.name === 'dashboard' ? 'text-primary' : 'text-slate-400 group-hover:text-slate-600'"></i>
                    <span v-show="!isCollapsed" class="font-medium transition-opacity duration-300">Dashboard</span>
                </RouterLink>

                <div v-if="corpora.length > 0">
                    <div v-show="!isCollapsed" class="pt-4 pb-2 px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Corpora
                    </div>
                    <div v-show="isCollapsed" class="h-8 flex items-center justify-center pt-2">
                        <div class="w-4 border-b border-slate-200"></div>
                    </div>
                    <RouterLink
                        v-for="corpus in corpora"
                        :key="corpus.id"
                        :to="{ name: 'corpora.dialogs', params: { corpusId: corpus.id } }"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors group relative"
                        :class="$route.name === 'corpora.dialogs' && $route.params.corpusId == corpus.id ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                        v-tooltip.right="isCollapsed ? corpus.title : null"
                    >
                        <i class="pi pi-folder-open text-lg" :class="$route.name === 'corpora.dialogs' && $route.params.corpusId == corpus.id ? 'text-primary' : 'text-slate-400 group-hover:text-slate-600'"></i>
                        <span v-show="!isCollapsed" class="font-medium transition-opacity duration-300">{{ corpus.title }}</span>
                    </RouterLink>
                </div>

                <div v-if="auth.hasPermission('view-users') || auth.hasPermission('view-roles')">
                    <div v-show="!isCollapsed" class="pt-4 pb-2 px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Amministrazione
                    </div>
                    <div v-show="isCollapsed" class="h-8 flex items-center justify-center pt-2">
                        <div class="w-4 border-b border-slate-200"></div>
                    </div>

                    <RouterLink
                        v-if="auth.hasPermission('view-users')"
                        to="/users"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors group relative"
                        :class="$route.name === 'users.index' ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                        v-tooltip.right="isCollapsed ? 'Utenti' : null"
                    >
                        <i class="pi pi-users text-lg" :class="$route.name === 'users.index' ? 'text-primary' : 'text-slate-400 group-hover:text-slate-600'"></i>
                        <span v-show="!isCollapsed" class="font-medium transition-opacity duration-300">Utenti</span>
                    </RouterLink>

                    <RouterLink
                        v-if="auth.hasPermission('view-roles')"
                        to="/roles"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors group relative"
                        :class="$route.name === 'roles.index' ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                        v-tooltip.right="isCollapsed ? 'Ruoli' : null"
                    >
                        <i class="pi pi-shield text-lg" :class="$route.name === 'roles.index' ? 'text-primary' : 'text-slate-400 group-hover:text-slate-600'"></i>
                        <span v-show="!isCollapsed" class="font-medium transition-opacity duration-300">Ruoli</span>
                    </RouterLink>

                    <RouterLink
                        v-if="auth.hasPermission('manage-corpora')"
                        to="/corpora-management"
                        class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors group relative"
                        :class="$route.name === 'corpora.index' ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                        v-tooltip.right="isCollapsed ? 'Gestione Corpora' : null"
                    >
                        <i class="pi pi-folder text-lg" :class="$route.name === 'corpora.index' ? 'text-primary' : 'text-slate-400 group-hover:text-slate-600'"></i>
                        <span v-show="!isCollapsed" class="font-medium transition-opacity duration-300">Corpora</span>
                    </RouterLink>
                </div>
            </nav>

            <!-- Bottom User Section (Mobile/Small) -->
            <div class="p-4 border-t border-slate-100 lg:hidden">
                <button @click="logout" class="w-full flex items-center justify-center space-x-2 px-4 py-2 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                    <i class="pi pi-sign-out text-sm"></i>
                    <span class="font-medium">Logout</span>
                </button>
            </div>

            <!-- Collapse Toggle Button (Desktop) -->
            <div class="hidden lg:flex p-4 border-t border-slate-100 items-center justify-center">
                <button
                    @click="isCollapsed = !isCollapsed"
                    class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:text-primary hover:bg-primary/5 transition-all duration-200"
                    :title="isCollapsed ? 'Espandi' : 'Comprimi'"
                >
                    <i class="pi" :class="isCollapsed ? 'pi-angle-double-right' : 'pi-angle-double-left'"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div
            class="flex-1 flex flex-col min-h-screen relative z-10 transition-all duration-300 ease-in-out min-w-0 overflow-x-hidden"
            :class="isCollapsed ? 'lg:ml-20' : 'lg:ml-72'"
        >
            <!-- Top Header -->
            <header class="h-16 bg-white/70 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 flex items-center justify-between px-4 lg:px-8">
                <!-- Mobile Toggle / Desktop Status -->
                <div class="flex items-center gap-4">
                    <button
                        @click="isMobileOpen = true"
                        class="lg:hidden h-10 w-10 flex items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"
                    >
                        <i class="pi pi-bars text-xl"></i>
                    </button>
                </div>

                <div class="flex items-center space-x-6">
                    <!-- User Info -->
                    <div class="flex items-center space-x-3 border-r border-slate-200 pr-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-slate-900 leading-none">{{ auth.user?.first_name }} {{ auth.user?.last_name }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ auth.user?.roles?.[0]?.name || 'Utente' }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold border border-primary/20">
                            {{ auth.user?.first_name?.charAt(0) }}{{ auth.user?.last_name?.charAt(0) }}
                        </div>
                    </div>

                    <!-- Logout Button -->
                    <button
                        @click="logout"
                        class="flex items-center space-x-2 text-slate-500 hover:text-red-600 transition-colors py-2 px-3 rounded-md hover:bg-red-50"
                        title="Logout"
                    >
                        <i class="pi pi-sign-out"></i>
                        <span class="hidden md:inline font-medium">Esci</span>
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-8 overflow-x-hidden">
                <div class="max-w-full">
                    <slot />
                </div>
            </main>

            <!-- Footer -->
            <footer class="py-4 px-8 border-t border-slate-200 text-center text-slate-400 text-xs">
                &copy; {{ new Date().getFullYear() }} Pracsi App. Tutti i diritti riservati.
            </footer>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { dialogService } from '../services/dialogService'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const corpora = ref([])
const isCollapsed = ref(false)
const isMobileOpen = ref(false)

// Close mobile sidebar on route change
router.afterEach(() => {
    isMobileOpen.value = false
})

const loadCorpora = async () => {
    try {
        const response = await dialogService.getCorpora()
        corpora.value = response.data.data
    } catch (error) {
        console.error('Errore durante il caricamento dei corpora:', error)
    }
}

const logout = async () => {
    await auth.logout()
    router.push('/login')
}

onMounted(() => {
    loadCorpora()
})
</script>
