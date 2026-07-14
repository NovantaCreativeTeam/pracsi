import { defineStore } from 'pinia';

export const useDialogNavigationStore = defineStore('dialogNavigation', {
    state: () => ({
        dialogIds: [],
        currentIndex: -1,
    }),

    getters: {
        hasNext: (state) => state.currentIndex < state.dialogIds.length - 1,
        hasPrevious: (state) => state.currentIndex > 0,
        nextId: (state) => state.hasNext ? state.dialogIds[state.currentIndex + 1] : null,
        previousId: (state) => state.hasPrevious ? state.dialogIds[state.currentIndex - 1] : null,
        total: (state) => state.dialogIds.length,
        currentDisplayIndex: (state) => state.currentIndex + 1,
        isActive: (state) => state.dialogIds.length > 0,
    },

    actions: {
        setNavigationList(ids, startIndex = 0) {
            this.dialogIds = ids;
            this.currentIndex = startIndex;
        },
        setCurrentId(id) {
            const index = this.dialogIds.indexOf(Number(id));
            if (index !== -1) {
                this.currentIndex = index;
            }
        },
        clearNavigation() {
            this.dialogIds = [];
            this.currentIndex = -1;
        }
    }
});
