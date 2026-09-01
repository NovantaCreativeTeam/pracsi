import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export const useTablePreferencesStore = defineStore('tablePreferences', () => {
    // Stato per la tabella dell'elenco dialoghi
    const dialogsSelectedColumns = ref(JSON.parse(localStorage.getItem('table-prefs-dialogs-cols')) || null);
    const dialogsFilters = ref(JSON.parse(localStorage.getItem('table-prefs-dialogs-filters')) || null);

    // Stato per la tabella della trascrizione
    const transcriptSelectedColumns = ref(JSON.parse(localStorage.getItem('table-prefs-transcript-cols')) || null);
    const transcriptFilters = ref(JSON.parse(localStorage.getItem('table-prefs-transcript-filters')) || null);

    // Watchers per la persistenza manuale
    watch(dialogsSelectedColumns, (val) => {
        localStorage.setItem('table-prefs-dialogs-cols', JSON.stringify(val));
    }, { deep: true });

    watch(dialogsFilters, (val) => {
        localStorage.setItem('table-prefs-dialogs-filters', JSON.stringify(val));
    }, { deep: true });

    watch(transcriptSelectedColumns, (val) => {
        localStorage.setItem('table-prefs-transcript-cols', JSON.stringify(val));
    }, { deep: true });

    watch(transcriptFilters, (val) => {
        localStorage.setItem('table-prefs-transcript-filters', JSON.stringify(val));
    }, { deep: true });

    return {
        dialogsSelectedColumns,
        dialogsFilters,
        transcriptSelectedColumns,
        transcriptFilters
    };
});
