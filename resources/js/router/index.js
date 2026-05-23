import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Login from '../views/Auth/Login.vue'
import Register from "../views/Auth/Register.vue";
import UsersIndex from "../views/Users/Index.vue";
import RolesIndex from "../views/Roles/Index.vue";

const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: () => import('../views/Dashboard/Index.vue'),
        meta: {
            requiresAuth: true,
            layout: 'app'
        }
    },
    {
        path: '/users',
        name: 'users.index',
        component: UsersIndex,
        meta: {
            requiresAuth: true,
            layout: 'app',
            permission: 'view-users'
        }
    },
    {
        path: '/roles',
        name: 'roles.index',
        component: RolesIndex,
        meta: {
            requiresAuth: true,
            layout: 'app',
            permission: 'view-roles'
        }
    },
    {
        path: '/dialogs',
        name: 'dialogs.index',
        component: () => import('../views/Dialogs/Index.vue'),
        meta: {
            requiresAuth: true,
            layout: 'app',
            permission: 'view-dialogs'
        }
    },
    {
        path: '/dialogs/create',
        name: 'dialogs.create',
        component: () => import('../views/Dialogs/Create.vue'),
        meta: {
            requiresAuth: true,
            layout: 'app',
            permission: 'manage-dialogs'
        }
    },
    {
        path: '/dialogs/:id',
        name: 'dialogs.show',
        component: () => import('../views/Dialogs/Show.vue'),
        meta: {
            requiresAuth: true,
            layout: 'app',
            permission: 'view-dialogs'
        }
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: {
            requiresAuth: false,
            layout: 'auth'
        }
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: {
            requiresAuth: false,
            layout: 'auth'
        }
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login')
    } else if (authStore.isAuthenticated && (to.name === 'login' || to.name === 'register')) {
        next('/')
    } else if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
        next('/') // Reindirizza alla dashboard se non ha il permesso
    } else {
        next()
    }
})

export default router
