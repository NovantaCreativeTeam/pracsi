<template>
    <div class="space-y-6">
        <Card>
            <template #title>
                <div class="flex items-center justify-between px-2">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Gestione Ruoli e Permessi</h1>
                    <div class="flex items-center space-x-3" v-if="auth.hasPermission('manage-roles')">
                        <span class="text-sm font-normal text-slate-500 mr-4">Totale: {{ roles.length }} ruoli</span>
                        <Button label="Nuovo Ruolo" icon="pi pi-plus" size="small" @click="openNew" />
                    </div>
                </div>
            </template>
            <template #content>
                <div class="overflow-hidden">
                    <DataTable
                        :value="roles"
                        responsiveLayout="scroll"
                        :loading="loading"
                        stripedRows
                    >
                        <Column field="name" header="Nome Ruolo" class="font-medium text-slate-700"></Column>
                        <Column header="Permessi Associati">
                            <template #body="slotProps">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="permission in slotProps.data.permissions"
                                        :key="permission.id"
                                        class="px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-100"
                                    >
                                        {{ permission.name }}
                                    </span>
                                    <span v-if="!slotProps.data.permissions?.length" class="text-xs text-slate-400 italic">
                                        Nessun permesso
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Azioni" :exportable="false" style="min-width:10rem" v-if="auth.hasPermission('manage-roles')">
                            <template #body="slotProps">
                                <SplitButton
                                    label="Modifica"
                                    icon="pi pi-pencil"
                                    @click="editRole(slotProps.data)"
                                    :model="getActionItems(slotProps.data)"
                                    severity="primary"
                                    size="small"
                                />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-shield text-4xl text-slate-200 mb-4 block"></i>
                                <span class="text-slate-500">Nessun ruolo trovato</span>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </template>
        </Card>

        <Dialog v-model:visible="roleDialog" :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" :header="dialogTitle" :modal="true" class="p-fluid">

                <div class="col-12 field mb-4">
                    <label for="name" class="font-bold mb-1 block">Nome Ruolo</label>
                    <InputText id="name" v-model.trim="role.name" required="true" autofocus :class="{'p-invalid': submitted && !role.name}" class="w-full" />
                    <small class="p-error" v-if="submitted && !role.name">Il nome del ruolo è obbligatorio.</small>
                </div>

                <div class="col-12 field mb-4">
                    <label class="font-bold mb-2 block">Permessi</label>
                    <div v-for="permission in permissions" :key="permission.id" class="col-12 md:col-6 lg:col-4 mb-2">
                        <div class="flex items-center">
                            <Checkbox :id="'perm_' + permission.id" v-model="role.permissions" :value="permission.name" />
                            <label :for="'perm_' + permission.id" class="ml-2 text-sm">{{ permission.name }}</label>
                        </div>
                    </div>
                    <div v-if="!permissions.length" class="text-slate-400 italic text-sm">
                        Nessun permesso disponibile nel sistema.
                    </div>
                </div>

            <template #footer>
                <Button label="Annulla" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Salva" icon="pi pi-check" @click="saveRole" :loading="saving" />
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
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const toast = useToast()
const confirm = useConfirm()
const auth = useAuthStore()

const roles = ref([])
const permissions = ref([])
const loading = ref(true)
const saving = ref(false)
const roleDialog = ref(false)
const submitted = ref(false)

const role = ref({
    id: null,
    name: '',
    permissions: []
})

const dialogTitle = computed(() => {
    return role.value.id ? 'Modifica Ruolo' : 'Nuovo Ruolo'
})

const fetchRoles = async () => {
    loading.value = true
    try {
        const { data } = await axios.get('/roles')
        roles.value = data
    } catch (error) {
        console.error('Errore durante il caricamento dei ruoli:', error)
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile caricare i ruoli', life: 3000 })
    } finally {
        loading.value = false
    }
}

const fetchPermissions = async () => {
    try {
        const { data } = await axios.get('/permissions')
        permissions.value = data
    } catch (error) {
        console.error('Errore durante il caricamento dei permessi:', error)
    }
}

onMounted(() => {
    fetchRoles()
    fetchPermissions()
})

const openNew = () => {
    role.value = {
        id: null,
        name: '',
        permissions: []
    }
    submitted.value = false
    roleDialog.value = true
}

const hideDialog = () => {
    roleDialog.value = false
    submitted.value = false
}

const editRole = (roleData) => {
    role.value = {
        id: roleData.id,
        name: roleData.name,
        permissions: roleData.permissions.map(p => p.name)
    }
    roleDialog.value = true
}

const getActionItems = (data) => {
    return [
        {
            label: 'Elimina',
            icon: 'pi pi-trash',
            disabled: data.name === 'Amministratore',
            command: () => {
                confirmDeleteRole(data);
            }
        }
    ];
};

const saveRole = async () => {
    submitted.value = true

    if (!role.value.name) {
        return
    }

    saving.value = true
    try {
        if (role.value.id) {
            await axios.put(`/roles/${role.value.id}`, role.value)
            toast.add({ severity: 'success', summary: 'Successo', detail: 'Ruolo aggiornato', life: 3000 })
        } else {
            await axios.post('/roles', role.value)
            toast.add({ severity: 'success', summary: 'Successo', detail: 'Ruolo creato', life: 3000 })
        }
        roleDialog.value = false
        fetchRoles()
    } catch (error) {
        console.error('Errore durante il salvataggio:', error)
        const detail = error.response?.data?.message || 'Errore durante il salvataggio'
        toast.add({ severity: 'error', summary: 'Errore', detail, life: 3000 })
    } finally {
        saving.value = false
    }
}

const confirmDeleteRole = (roleData) => {
    confirm.require({
        message: `Sei sicuro di voler eliminare il ruolo ${roleData.name}?`,
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
            deleteRole(roleData.id)
        }
    })
}

const deleteRole = async (id) => {
    try {
        await axios.delete(`/roles/${id}`)
        toast.add({ severity: 'success', summary: 'Successo', detail: 'Ruolo eliminato', life: 3000 })
        fetchRoles()
    } catch (error) {
        console.error('Errore durante l\'eliminazione:', error)
        const detail = error.response?.data?.message || 'Errore durante l\'eliminazione'
        toast.add({ severity: 'error', summary: 'Errore', detail, life: 3000 })
    }
}
</script>
