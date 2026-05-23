<template>
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <h1 class="text-2xl font-bold">Dialoghi</h1>
            <div class="flex items-center gap-2">
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="filters['global'].value" placeholder="Cerca..." size="small"/>
                </IconField>
                <MultiSelect :modelValue="selectedColumns" :options="columns" optionLabel="header" placeholder="Seleziona Colonne"
                             :maxSelectedLabels="3" class="w-64" @update:modelValue="onColumnToggle" size="small"/>
                <router-link :to="{ name: 'dialogs.create' }">
                    <Button label="Nuovo Dialogo" icon="pi pi-plus" class="whitespace-nowrap" size="small"/>
                </router-link>
            </div>
        </div>

        <div>
            <Card class="mb-4">
                <template #content>
                    <DataTable :value="dialogs" :loading="loading" v-model:filters="filters"
                               :globalFilterFields="['reference', 'corpus.project_reference', 'title', 'genre', 'subgenre', 'topic', 'subject_languages', 'city', 'region', 'country', 'restaurant_title']"
                               paginator :rows="10" tableStyle="min-width: 50rem" removableSort
                               class="p-datatable-sm">
                        <Column v-for="col of selectedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable"></Column>

                        <Column header="Azioni" :exportable="false" style="min-width:8rem">
                            <template #body="slotProps">
                                <SplitButton
                                    label="Visualizza"
                                    icon="pi pi-eye"
                                    @click="viewDetails(slotProps.data.id)"
                                    :model="getActionItems(slotProps.data)"
                                    severity="primary"
                                    size="small"
                                />
                            </template>
                        </Column>
                        <template #empty> Nessun dialogo trovato. </template>
                    </DataTable>
                </template>
            </Card>

        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { dialogService } from '../../services/dialogService';
import Button from 'primevue/button';
import SplitButton from 'primevue/splitbutton';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from "primevue/usetoast";
import {useConfirm} from "primevue/useconfirm";

const router = useRouter();
const toast = useToast();

const dialogs = ref([]);
const loading = ref(true);
const confirm = useConfirm();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const columns = ref([
    { field: 'reference', header: 'Codice', sortable: true },
    { field: 'corpus.project_reference', header: 'Corpus', sortable: true },
    { field: 'title', header: 'Titolo', sortable: true },
    { field: 'genre', header: 'Genere', sortable: true },
    { field: 'subgenre', header: 'Sottogenere', sortable: true },
    { field: 'topic', header: 'Argomento', sortable: true },
    { field: 'subject_languages', header: 'Lingue Soggetto', sortable: true },
    { field: 'working_languages', header: 'Lingue di Lavoro', sortable: true },
    { field: 'city', header: 'Città', sortable: true },
    { field: 'region', header: 'Regione', sortable: true },
    { field: 'country', header: 'Paese', sortable: true },
    { field: 'continent', header: 'Continente', sortable: true },
    { field: 'customer_type', header: 'Tipo Cliente', sortable: true },
    { field: 'customer_profile', header: 'Profilo Cliente', sortable: true },
    { field: 'customer_n', header: 'N. Clienti', sortable: true },
    { field: 'speaking_customer_n', header: 'N. Clienti Parlanti', sortable: true },
    { field: 'speakers_features', header: 'Caratteristiche Parlanti', sortable: true },
    { field: 'restaurant_title', header: 'Nome Ristorante', sortable: true },
    { field: 'restaurant_features', header: 'Caratteristiche Ristorante', sortable: true },
    { field: 'menu_type', header: 'Tipo Menu', sortable: true }
]);

const selectedColumns = ref(columns.value.filter(col =>
    ['reference', 'corpus.project_reference', 'title', 'subject_languages', 'topic', 'speaking_customer_n'].includes(col.field)
));

const onColumnToggle = (val) => {
    selectedColumns.value = columns.value.filter((col) => val.some((sCol) => sCol.field === col.field));
};

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

const getActionItems = (data) => {
    return [
        {
            label: 'Elimina',
            icon: 'pi pi-trash',
            command: () => {
                confirmDelete(data);
            }
        }
    ];
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
