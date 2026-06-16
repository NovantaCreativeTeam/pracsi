<template>
    <div class="space-y-6">
        <Card>
            <template #title>
                <div class="flex items-center justify-between px-2">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Gestione Corpora</h1>
                    <div class="flex items-center space-x-3">
                        <span class="text-sm font-normal text-slate-500 mr-4">Totale: {{ corpora.length }} corpora</span>
                        <Button label="Nuovo Corpus" icon="pi pi-plus" size="small" @click="openNew" />
                    </div>
                </div>
            </template>
            <template #content>
                <div class="overflow-hidden">
                    <DataTable
                        :value="corpora"
                        responsiveLayout="scroll"
                        :loading="loading"
                        stripedRows
                        paginator
                        :rows="10"
                        class="p-datatable-sm"
                    >
                        <Column field="project_reference" header="Riferimento Progetto" sortable class="font-medium text-slate-700"></Column>
                        <Column field="title" header="Titolo" sortable class="text-slate-600"></Column>
                        <Column field="subject_language" header="Lingua Soggetto" sortable class="text-slate-600"></Column>
                        <Column field="location" header="Località" sortable class="text-slate-600"></Column>
                        <Column header="Azioni" :exportable="false" style="min-width:10rem">
                            <template #body="slotProps">
                                <SplitButton
                                    label="Modifica"
                                    icon="pi pi-pencil"
                                    @click="editCorpus(slotProps.data)"
                                    :model="getActionItems(slotProps.data)"
                                    severity="primary"
                                    size="small"
                                />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-folder text-4xl text-slate-200 mb-4 block"></i>
                                <span class="text-slate-500">Nessun corpus trovato</span>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </template>
        </Card>

        <!-- Dialog Creazione/Modifica -->
        <Dialog v-model:visible="corpusDialog" :style="{ width: '35vw' }" :breakpoints="{ '1199px': '85vw', '575px': '95vw' }" :header="dialogTitle" :modal="true" class="p-fluid">
            <div class="flex flex-col gap-4 mt-2">
                <div class="field">
                    <label for="project_reference" class="mb-1 block">Riferimento Progetto *</label>
                    <InputText id="project_reference" v-model.trim="corpus.project_reference" required="true" autofocus :class="{'p-invalid': submitted && !corpus.project_reference}" class="w-full" />
                    <small class="p-error" v-if="submitted && !corpus.project_reference">Il riferimento progetto è obbligatorio.</small>
                </div>
                <div class="field">
                    <label for="title" class="mb-1 block">Titolo *</label>
                    <InputText id="title" v-model.trim="corpus.title" required="true" :class="{'p-invalid': submitted && !corpus.title}" class="w-full" />
                    <small class="p-error" v-if="submitted && !corpus.title">Il titolo è obbligatorio.</small>
                </div>
                <div class="field">
                    <label for="subject_language" class="mb-1 block">Lingua Soggetto</label>
                    <InputText id="subject_language" v-model.trim="corpus.subject_language" class="w-full" />
                </div>
                <div class="field">
                    <label for="working_language" class="mb-1 block">Lingua di Lavoro</label>
                    <InputText id="working_language" v-model.trim="corpus.working_language" class="w-full" />
                </div>
                <div class="field">
                    <label for="location" class="mb-1 block">Località</label>
                    <InputText id="location" v-model.trim="corpus.location" class="w-full" />
                </div>
                <div class="field">
                    <label for="region" class="mb-1 block">Regione</label>
                    <InputText id="region" v-model.trim="corpus.region" class="w-full" />
                </div>
                <div class="field">
                    <label for="country" class="mb-1 block">Nazione</label>
                    <InputText id="country" v-model.trim="corpus.country" class="w-full" />
                </div>
                <div class="field">
                    <label for="continent" class="mb-1 block">Continente</label>
                    <InputText id="continent" v-model.trim="corpus.continent" class="w-full" />
                </div>
                <div class="field">
                    <label for="depositor" class="mb-1 block">Depositante</label>
                    <InputText id="depositor" v-model.trim="corpus.depositor" class="w-full" />
                </div>
                <div class="field">
                    <label for="contact" class="mb-1 block">Contatto</label>
                    <InputText id="contact" v-model.trim="corpus.contact" class="w-full" />
                </div>
                <div class="field">
                    <label for="description" class="mb-1 block">Descrizione</label>
                    <Textarea id="description" v-model="corpus.description" rows="3" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Annulla" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Salva" icon="pi pi-check" @click="saveCorpus" :loading="saving" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { corpusService } from '../../services/corpusService'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import SplitButton from 'primevue/splitbutton'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'

const toast = useToast()
const confirm = useConfirm()

const corpora = ref([])
const loading = ref(true)
const corpusDialog = ref(false)
const corpus = ref({})
const submitted = ref(false)
const saving = ref(false)

const dialogTitle = computed(() => corpus.value.id ? 'Modifica Corpus' : 'Nuovo Corpus')

const loadCorpora = async () => {
    loading.value = true
    try {
        const response = await corpusService.getAll()
        corpora.value = response.data.data
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile caricare i corpora', life: 3000 })
    } finally {
        loading.value = false
    }
}

const openNew = () => {
    corpus.value = {
        project_reference: '',
        title: '',
        subject_language: '',
        working_language: '',
        location: '',
        region: '',
        country: '',
        continent: '',
        depositor: '',
        contact: '',
        description: ''
    }
    submitted.value = false
    corpusDialog.value = true
}

const hideDialog = () => {
    corpusDialog.value = false
    submitted.value = false
}

const editCorpus = (data) => {
    corpus.value = { ...data }
    corpusDialog.value = true
}

const getActionItems = (data) => {
    return [
        {
            label: 'Elimina',
            icon: 'pi pi-trash',
            command: () => {
                confirmDeleteCorpus(data);
            }
        }
    ];
};

const confirmDeleteCorpus = (data) => {
    confirm.require({
        message: `Sei sicuro di voler eliminare il corpus ${data.title}?`,
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
            deleteCorpus(data.id)
        }
    })
}

const saveCorpus = async () => {
    submitted.value = true

    if (corpus.value.project_reference?.trim() && corpus.value.title?.trim()) {
        saving.value = true
        try {
            if (corpus.value.id) {
                await corpusService.update(corpus.value.id, corpus.value)
                toast.add({ severity: 'success', summary: 'Successo', detail: 'Corpus aggiornato', life: 3000 })
            } else {
                await corpusService.create(corpus.value)
                toast.add({ severity: 'success', summary: 'Successo', detail: 'Corpus creato', life: 3000 })
            }
            corpusDialog.value = false
            corpus.value = {}
            loadCorpora()
        } catch (error) {
            toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile salvare il corpus', life: 3000 })
        } finally {
            saving.value = false
        }
    }
}

const deleteCorpus = async (id) => {
    try {
        await corpusService.delete(id)
        toast.add({ severity: 'success', summary: 'Successo', detail: 'Corpus eliminato', life: 3000 })
        loadCorpora()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile eliminare il corpus', life: 3000 })
    }
}

onMounted(() => {
    loadCorpora()
})
</script>
