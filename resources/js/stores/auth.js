import { defineStore } from 'pinia'
import axios from '../plugins/axios.js'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        hasPermission: (state) => (permission) => {
            if (state.user?.roles?.some(r => r.name === 'Amministratore')) {
                return true
            }
            return state.user?.permissions?.some(p => p.name === permission) || false
        },
        hasRole: (state) => (role) => {
            return state.user?.roles?.some(r => r.name === role) || false
        }
    },

    actions: {
        async login(credentials) {
            try {
                await axios.get('/sanctum/csrf-cookie', { baseURL: '/' })
                await axios.post('/login', credentials, { baseURL: '/' })
                await this.fetchUser()
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async register(user) {
            try {
                await axios.get('/sanctum/csrf-cookie', { baseURL: '/' })
                await axios.post('/register', user, { baseURL: '/' })
                await this.fetchUser()
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async fetchUser() {
            try {
                const { data } = await axios.get('/user')
                this.user = data
            } catch (error) {
                this.user = null
            }
        },

        async logout() {
            try {
                await axios.post('/logout', {}, { baseURL: '/' })
            } finally {
                this.user = null
            }
        }
    }
})
