<template>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Elenco Dialoghi</h1>
            <router-link :to="{ name: 'dialogs.create' }" v-if="auth.hasPermission('manage-dialogs')">
                <Button label="Nuovo Dialogo" icon="pi pi-plus" />
            </router-link>
        </div>

        <div>
            <DataTable :value="dialogs" :loading="loading" paginator :rows="10" tableStyle="min-width: 50rem">
                <Column field="reference" header="Codice" sortable></Column>
                <Column field="corpus.project_reference" header="Corpus" sortable></Column>
                <Column field="title" header="Titolo" sortable></Column>
                <Column header="Azioni" v-if="auth.hasPermission('view-dialogs') || auth.hasPermission('manage-dialogs')">
                    <template #body="slotProps">
                        <div class="flex gap-2">
                            <Button v-if="auth.hasPermission('view-dialogs')" icon="pi pi-eye" severity="info" text rounded @click="viewDetails(slotProps.data.id)" title="Visualizza dettaglio" />
                            <Button v-if="auth.hasPermission('manage-dialogs')" icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(slotProps.data)" title="Elimina dialogo" />
                        </div>
                    </template>
                </Column>
                <template #empty> Nessun dialogo trovato. </template>
            </DataTable>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { dialogService } from '../../services/dialogService';
import { useAuthStore } from '../../stores/auth';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const router = useRouter();
const confirm = useConfirm();
const toast = useToast();
const auth = useAuthStore();

const dialogs = ref([]);
const loading = ref(true);

const loadDialogs = async () => {
    loading.value = true;
    try {
        const response = await dialogService.getAll();
        dialogs.value = response.data.data;
    } catch (error) {
        console.error('Errore durante il caricamento dei dialoghi:', error);
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile caricare i dialoghi', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const viewDetails = (id) => {
    router.push({ name: 'dialogs.show', params: { id } });
};

const confirmDelete = (dialog) => {
    confirm.require({
        message: `Sei sicuro di voler eliminare il dialogo "${dialog.title}"?`,
        header: 'Conferma eliminazione',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Annulla',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Elimina',
            severity: 'danger'
        },
        accept: () => {
            deleteDialog(dialog.id);
        }
    });
};

const deleteDialog = async (id) => {
    try {
        await dialogService.delete(id);
        toast.add({ severity: 'success', summary: 'Successo', detail: 'Dialogo eliminato correttamente', life: 3000 });
        loadDialogs();
    } catch (error) {
        console.error('Errore durante l\'eliminazione del dialogo:', error);
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile eliminare il dialogo', life: 3000 });
    }
};

onMounted(() => {
    loadDialogs();
});
</script>

<style scoped>
</style>
