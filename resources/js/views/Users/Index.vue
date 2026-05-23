<template>
    <div class="space-y-6">
        <Card>
            <template #title>
                <div class="flex items-center justify-between px-2">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Lista Utenti</h1>
                    <div class="flex items-center space-x-3" v-if="auth.hasPermission('create-users')">
                        <span class="text-sm font-normal text-slate-500 mr-4">Totale: {{ users.length }} utenti</span>
                        <Button label="Nuovo Utente" icon="pi pi-plus" size="small" @click="openNew" />
                    </div>
                </div>
            </template>
            <template #content>
                <div class="overflow-hidden">
                    <DataTable
                        :value="users"
                        responsiveLayout="scroll"
                        :loading="loading"
                        stripedRows
                    >
                        <Column field="first_name" header="Nome" class="font-medium text-slate-700"></Column>
                        <Column field="last_name" header="Cognome" class="text-slate-600"></Column>
                        <Column field="email" header="Email">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-2 text-slate-600">
                                    <i class="pi pi-envelope text-xs text-slate-400"></i>
                                    <span>{{ slotProps.data.email }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Ruolo">
                            <template #body="slotProps">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="role in slotProps.data.roles"
                                        :key="role.id"
                                        class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full border border-indigo-100"
                                    >
                                        {{ role.name }}
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Azioni" :exportable="false" style="min-width:10rem" v-if="auth.hasPermission('edit-users') || auth.hasPermission('delete-users')">
                            <template #body="slotProps">
                                <SplitButton
                                    v-if="auth.hasPermission('edit-users')"
                                    label="Modifica"
                                    icon="pi pi-pencil"
                                    @click="editUser(slotProps.data)"
                                    :model="getActionItems(slotProps.data)"
                                    severity="primary"
                                    size="small"
                                />
                                <Button
                                    v-else-if="auth.hasPermission('delete-users')"
                                    icon="pi pi-trash"
                                    text
                                    rounded
                                    severity="danger"
                                    @click="confirmDeleteUser(slotProps.data)"
                                    size="small"
                                />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-users text-4xl text-slate-200 mb-4 block"></i>
                                <span class="text-slate-500">Nessun utente trovato</span>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </template>
        </Card>

        <Dialog v-model:visible="userDialog" :style="{ width: '60rem' }" :breakpoints="{ '1199px': '85vw', '575px': '95vw' }" :header="dialogTitle" :modal="true" class="p-fluid">

                <div class="col-12 field mb-4">
                    <label for="first_name" class="font-bold mb-1 block">Nome</label>
                    <InputText id="first_name" v-model.trim="user.first_name" required="true" autofocus :class="{'p-invalid': submitted && !user.first_name}" class="w-full" />
                    <small class="p-error" v-if="submitted && !user.first_name">Il nome è obbligatorio.</small>
                </div>
                <div class="col-12 field mb-4">
                    <label for="last_name" class="font-bold mb-1 block">Cognome</label>
                    <InputText id="last_name" v-model.trim="user.last_name" required="true" :class="{'p-invalid': submitted && !user.last_name}" class="w-full" />
                    <small class="p-error" v-if="submitted && !user.last_name">Il cognome è obbligatorio.</small>
                </div>
                <div class="col-12 field mb-4">
                    <label for="email" class="font-bold mb-1 block">Email</label>
                    <InputText id="email" v-model.trim="user.email" required="true" :class="{'p-invalid': submitted && !user.email}" class="w-full" />
                    <small class="p-error" v-if="submitted && !user.email">L'email è obbligatoria.</small>
                </div>

                <div class="col-12 field mb-4">
                    <label for="roles" class="font-bold mb-1 block">Ruoli</label>
                    <MultiSelect
                        id="roles"
                        v-model="user.roles"
                        :options="roles"
                        optionLabel="name"
                        optionValue="name"
                        placeholder="Seleziona Ruoli"
                        :maxSelectedLabels="3"
                        class="w-full"
                        filter
                    />
                </div>

                <div class="col-12 field mb-4">
                    <label for="password" class="font-bold mb-1 block">Password {{ user.id ? '(lascia vuoto per non cambiare)' : '' }}</label>
                    <Password id="password" v-model="user.password" :feedback="false" toggleMask :class="{'p-invalid': submitted && !user.id && !user.password}" class="w-full" inputClass="w-full" />
                    <small class="p-error" v-if="submitted && !user.id && !user.password">La password è obbligatoria per i nuovi utenti.</small>
                </div>

                <div class="col-12 field mb-4" v-if="user.password || !user.id">
                    <label for="password_confirmation" class="font-bold mb-1 block">Conferma Password</label>
                    <Password id="password_confirmation" v-model="user.password_confirmation" :feedback="false" toggleMask :class="{'p-invalid': submitted && user.password !== user.password_confirmation}" class="w-full" inputClass="w-full" />
                    <small class="p-error" v-if="submitted && user.password !== user.password_confirmation">Le password non corrispondono.</small>
                </div>

            <template #footer>
                <Button label="Annulla" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Salva" icon="pi pi-check" @click="saveUser" :loading="saving" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../../plugins/axios'
import { useAuthStore } from '../../stores/auth'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Card from 'primevue/card'
import Button from 'primevue/button'
import SplitButton from 'primevue/splitbutton'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import MultiSelect from 'primevue/multiselect'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const toast = useToast()
const confirm = useConfirm()
const auth = useAuthStore()

const users = ref([])
const roles = ref([])
const loading = ref(true)
const saving = ref(false)
const userDialog = ref(false)
const submitted = ref(false)

const user = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: []
})

const dialogTitle = computed(() => {
    return user.value.id ? 'Modifica Utente' : 'Nuovo Utente'
})

const fetchUsers = async () => {
    loading.value = true
    try {
        const { data } = await axios.get('/users')
        users.value = data
    } catch (error) {
        console.error('Errore durante il caricamento degli utenti:', error)
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile caricare gli utenti', life: 3000 })
    } finally {
        loading.value = false
    }
}

const fetchRoles = async () => {
    try {
        const { data } = await axios.get('/roles-list')
        roles.value = data
    } catch (error) {
        console.error('Errore durante il caricamento dei ruoli:', error)
    }
}

onMounted(() => {
    fetchUsers()
    fetchRoles()
})

const openNew = () => {
    user.value = {
        id: null,
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: []
    }
    submitted.value = false
    userDialog.value = true
}

const hideDialog = () => {
    userDialog.value = false
    submitted.value = false
}

const editUser = (userData) => {
    user.value = {
        ...userData,
        password: '',
        password_confirmation: '',
        roles: userData.roles.map(r => r.name)
    }
    userDialog.value = true
}

const getActionItems = (data) => {
    const items = [];

    if (auth.hasPermission('delete-users')) {
        items.push({
            label: 'Elimina',
            icon: 'pi pi-trash',
            command: () => {
                confirmDeleteUser(data);
            }
        });
    }

    return items;
};

const saveUser = async () => {
    submitted.value = true

    if (!user.value.first_name || !user.value.last_name || !user.value.email) {
        return
    }

    if (!user.value.id && !user.value.password) {
        return
    }

    if (user.value.password && user.value.password !== user.value.password_confirmation) {
        return
    }

    saving.value = true
    try {
        if (user.value.id) {
            await axios.put(`/users/${user.value.id}`, user.value)
            toast.add({ severity: 'success', summary: 'Successo', detail: 'Utente aggiornato', life: 3000 })
        } else {
            await axios.post('/users', user.value)
            toast.add({ severity: 'success', summary: 'Successo', detail: 'Utente creato', life: 3000 })
        }
        userDialog.value = false
        fetchUsers()
    } catch (error) {
        console.error('Errore durante il salvataggio:', error)
        const detail = error.response?.data?.message || 'Errore durante il salvataggio'
        toast.add({ severity: 'error', summary: 'Errore', detail, life: 3000 })
    } finally {
        saving.value = false
    }
}

const confirmDeleteUser = (userData) => {
    confirm.require({
        message: `Sei sicuro di voler eliminare l'utente ${userData.first_name} ${userData.last_name}?`,
        header: 'Conferma eliminazione',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: {
            label: 'Elimina',
            severity: 'danger'
        },
        rejectProps: {
            label: 'Annulla',
            severity: 'secondary',
            outlined: true
        },
        accept: () => {
            deleteUser(userData.id)
        }
    })
}

const deleteUser = async (id) => {
    try {
        await axios.delete(`/users/${id}`)
        toast.add({ severity: 'success', summary: 'Successo', detail: 'Utente eliminato', life: 3000 })
        fetchUsers()
    } catch (error) {
        console.error('Errore durante l\'eliminazione:', error)
        const detail = error.response?.data?.message || 'Errore durante l\'eliminazione'
        toast.add({ severity: 'error', summary: 'Errore', detail, life: 3000 })
    }
}
</script>
