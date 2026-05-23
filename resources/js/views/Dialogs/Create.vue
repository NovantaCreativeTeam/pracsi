<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { dialogService } from '@/services/dialogService';

import Toast from 'primevue/toast';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import Message from 'primevue/message';

const router = useRouter();
const toast = useToast();

const form = ref({
    corpus_id: null,
    reference: '',
    title: '',
    imdi_file: null,
    eaf_file: null,
    wav_file: null
});

const corpora = ref([]);
const loading = ref(false);
const submitting = ref(false);
const errors = ref({});

const fetchCorpora = async () => {
    loading.ref = true;
    try {
        const response = await dialogService.getCorpora();
        corpora.value = response.data;
    } catch (error) {
        console.error('Error fetching corpora:', error);
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile caricare i corpora', life: 3000 });
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchCorpora();
});

const onEafFileSelect = (event) => {
    form.value.eaf_file = event.files[0];
};

const onImdiFileSelect = (event) => {
    form.value.imdi_file = event.files[0];
};

const onWavFileSelect = (event) => {
    form.value.wav_file = event.files[0];
};

const onSubmit = async () => {
    submitting.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('corpus_id', form.value.corpus_id?.id || '');
    formData.append('reference', form.value.reference);
    formData.append('title', form.value.title);

    if (form.value.imdi_file) {
        formData.append('imdi_file', form.value.imdi_file);
    }

    if (form.value.eaf_file) {
        formData.append('eaf_file', form.value.eaf_file);
    }

    if (form.value.wav_file) {
        formData.append('wav_file', form.value.wav_file);
    }

    try {
        const response = await dialogService.create(formData);
        toast.add({ severity: 'success', summary: 'Successo', detail: 'Dialogo creato con successo', life: 3000 });

        // Reindirizza alla pagina di dettaglio del nuovo dialogo
        const newId = response.data.data.id;
        router.push({ name: 'dialogs.show', params: { id: newId } });
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Errore durante la creazione del dialogo';
            toast.add({
                severity: 'error',
                summary: 'Errore',
                detail: errorMessage,
                life: 5000
            });
        }
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Toast />
    <div class="card">
        <h2 class="text-2xl font-bold mb-4">Crea Nuovo Dialogo</h2>

        <form @submit.prevent="onSubmit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-2">
                <label for="corpus">Corpus</label>
                <Dropdown
                    id="corpus"
                    v-model="form.corpus_id"
                    :options="corpora"
                    optionLabel="project_reference"
                    placeholder="Seleziona un corpus"
                    :loading="loading"
                    :class="{ 'p-invalid': errors.corpus_id }"
                />
                <small class="p-error" v-if="errors.corpus_id">{{ errors.corpus_id[0] }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="reference">Codice Dialogo (Reference)</label>
                <InputText
                    id="reference"
                    v-model="form.reference"
                    placeholder="Es. IT_PSPR_PN29"
                    :class="{ 'p-invalid': errors.reference }"
                />
                <small class="p-error" v-if="errors.reference">{{ errors.reference[0] }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="title">Titolo</label>
                <InputText
                    id="title"
                    v-model="form.title"
                    placeholder="Titolo del dialogo"
                    :class="{ 'p-invalid': errors.title }"
                />
                <small class="p-error" v-if="errors.title">{{ errors.title[0] }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="imdi">File .imdi (Metadati)</label>
                <FileUpload
                    mode="basic"
                    name="imdi_file"
                    accept=".imdi"
                    :maxFileSize="1000000"
                    @select="onImdiFileSelect"
                    chooseLabel="Seleziona File IMDI"
                    :class="{ 'p-invalid': errors.imdi_file }"
                />
                <small class="p-error" v-if="errors.imdi_file">{{ errors.imdi_file[0] }}</small>
                <div v-if="form.imdi_file" class="mt-2">
                    <Message severity="info" :closable="false">File IMDI: {{ form.imdi_file.name }}</Message>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label for="eaf">File .eaf</label>
                <FileUpload
                    mode="basic"
                    name="eaf_file"
                    accept=".eaf"
                    :maxFileSize="10000000"
                    @select="onEafFileSelect"
                    chooseLabel="Seleziona File EAF"
                    :class="{ 'p-invalid': errors.eaf_file }"
                />
                <small class="p-error" v-if="errors.eaf_file">{{ errors.eaf_file[0] }}</small>
                <div v-if="form.eaf_file" class="mt-2">
                    <Message severity="info" :closable="false">File EAF: {{ form.eaf_file.name }}</Message>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label for="wav">File .wav</label>
                <FileUpload
                    mode="basic"
                    name="wav_file"
                    accept=".wav"
                    :maxFileSize="50000000"
                    @select="onWavFileSelect"
                    chooseLabel="Seleziona File Audio (WAV)"
                    :class="{ 'p-invalid': errors.wav_file }"
                />
                <small class="p-error" v-if="errors.wav_file">{{ errors.wav_file[0] }}</small>
                <div v-if="form.wav_file" class="mt-2">
                    <Message severity="info" :closable="false">File Audio: {{ form.wav_file.name }}</Message>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <Button
                    type="button"
                    label="Annulla"
                    icon="pi pi-times"
                    class="p-button-text mr-2"
                    @click="router.back()"
                />
                <Button
                    type="submit"
                    label="Crea e Importa"
                    icon="pi pi-check"
                    :loading="submitting"
                />
            </div>
        </form>
    </div>
</template>

<style scoped>
.p-error {
    color: var(--red-500);
    font-size: 0.875rem;
}
</style>
