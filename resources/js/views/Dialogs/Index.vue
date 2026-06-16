<template>
    <div class="p-6">
        <DialogDataTable :title="pageTitle" :corpus-id="corpusId" />
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { dialogService } from '../../services/dialogService';
import DialogDataTable from '../../components/DialogDataTable.vue';

const props = defineProps({
    corpusId: {
        type: [String, Number],
        default: null
    }
});

const pageTitle = ref('Elenco Dialoghi');

const updateTitle = async () => {
    if (props.corpusId) {
        try {
            const response = await dialogService.getAll({ corpus_id: props.corpusId });
            const dialogs = response.data.data;
            if (dialogs.length > 0) {
                pageTitle.value = `Dialoghi - ${dialogs[0].corpus.project_reference}`;
            } else {
                pageTitle.value = 'Elenco Dialoghi';
            }
        } catch (error) {
            console.error('Errore durante il recupero del titolo:', error);
            pageTitle.value = 'Elenco Dialoghi';
        }
    } else {
        pageTitle.value = 'Elenco Dialoghi';
    }
};

watch(() => props.corpusId, () => {
    updateTitle();
}, { immediate: true });
</script>

<style scoped>
</style>
