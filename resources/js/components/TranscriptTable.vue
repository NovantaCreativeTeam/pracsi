<template>
    <div class="transcript-table-container max-w-full overflow-hidden">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Trascrizione</h3>
            <div class="flex gap-4 items-center">
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="filters['global'].value" placeholder="Cerca..." size="small" />
                </IconField>
                <MultiSelect
                    v-model="selectedColumns"
                    :options="columns"
                    optionLabel="header"
                    @update:modelValue="onColumnToggle"
                    placeholder="Colonne"
                    class="w-48"
                    :showToggleAll="false"
                    size="small"
                    filter
                />
            </div>
        </div>

        <DataTable
            :value="moves"
            v-model:filters="filters"
            :globalFilterFields="globalFilterFields"
            class="p-datatable-sm text-sm"
            filterDisplay="menu"
            dataKey="id"
            @row-click="onRowClick"
            :rowClass="rowClass"
            showGridlines
            scrollable
            scrollDirection="both"
            tableStyle="min-width: 60rem"
        >
            <template #header>
                <component is="style" v-html="dynamicStyles" />
            </template>
            <Column v-if="isColumnSelected('index')" field="index" header="#" class="w-12 text-gray-400">
                <template #body="slotProps">
                    {{ slotProps.index + 1 }}
                </template>
            </Column>
            <Column v-if="isColumnSelected('time')" field="begin" header="Time" class="whitespace-nowrap">
                <template #body="{ data }">
                    <span class="text-gray-500">
                        {{ (data.begin / 1000).toFixed(2) }}–{{ (data.end / 1000).toFixed(2) }}
                    </span>
                </template>
            </Column>
            <Column v-if="isColumnSelected('turn')" field="turn" header="Turno" class="whitespace-nowrap font-bold">
                <template #body="{ data }">
                    {{ data.turn || '' }}
                </template>
            </Column>

            <!-- Dynamic Participant Columns -->
            <template v-for="p in participants" :key="'p-' + p.id">
                <Column
                    v-if="isColumnSelected('participant_' + p.code)"
                    :header="p.code"
                    :pt="{
                        headerContent: {
                            style: { color: getParticipantColor(p.role) }
                        }
                    }"
                >
                    <template #body="{ data }">
                        <span v-if="data.participant && data.participant.id === p.id" :style="{ color: getParticipantColor(p.role) }">
                            {{ data.annotation }}
                        </span>
                    </template>
                </Column>
            </template>

            <Column v-if="isColumnSelected('nva')" field="non_verbal_action.name" header="Non Verbal Action" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.non_verbal_action?.name || data.nonVerbalAction?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra Non Verbal Action" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('notes')" header="Note">
                <template #body="{ data }">
                    {{ getNotesForMove(data) }}
                </template>
            </Column>

            <Column v-if="isColumnSelected('pause')" header="Pausa" class="italic text-gray-400">
                <template #body="{ data }">
                    <span v-if="!data.participant && ((data.end - data.begin) / 1000) >= 0.2">
                        ({{ ((data.end - data.begin) / 1000).toFixed(2) }})
                    </span>
                </template>
            </Column>

            <Column v-if="isColumnSelected('task')" field="micro_task.task.type.name" header="Task" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.micro_task?.task?.type?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra task" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('micro_task')" field="micro_task.type.name" header="Microtask" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.micro_task?.type?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra microtask" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('is')" field="sequence.interactional_segment_id" header="IS" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.sequence?.interactional_segment_id ? `IS ${data.sequence.interactional_segment_id}` : '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra IS" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('sequence')" field="sequence.type.name" header="Sequence" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.sequence?.type?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra sequence" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('transaction')" field="transaction.name" header="Transaction" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.transaction?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra transaction" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('ml1')" field="move_level1.name" header="ML 1" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.move_level1?.name || data.moveLevel1?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra ML 1" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('ml2')" field="move_level2.name" header="ML 2" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.move_level2?.name || data.moveLevel2?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra ML 2" />
                </template>
            </Column>

            <Column v-if="isColumnSelected('ml3')" field="move_level3.name" header="ML 3" :showFilterMatchModes="false">
                <template #body="{ data }">
                    {{ data.move_level3?.name || data.moveLevel3?.name || '-' }}
                </template>
                <template #filter="{ filterModel }">
                    <InputText v-model="filterModel.value" type="text" placeholder="Filtra ML 3" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from '@primevue/core/api';
import { getParticipantColor, getParticipantLightColor } from '../utils/colors';

const props = defineProps({
    moves: {
        type: Array,
        required: true
    },
    notes: {
        type: Array,
        default: () => []
    },
    currentTime: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['seek']);

const participants = computed(() => {
    const pMap = {};
    const result = [];
    props.moves.forEach(move => {
        if (move.participant && move.participant.id && !pMap[move.participant.id]) {
            pMap[move.participant.id] = move.participant;
            result.push(move.participant);
        }
    });
    return result.sort((a, b) => (a.code || '').localeCompare(b.code || ''));
});

const columns = computed(() => {
    const cols = [
        { field: 'index', header: '#' },
        { field: 'time', header: 'Time' },
        { field: 'turn', header: 'Turno' },
        ...participants.value.map(p => ({ field: 'participant_' + p.code, header: p.code })),
        { field: 'nva', header: 'Non Verbal Action' },
        { field: 'notes', header: 'Note' },
        { field: 'pause', header: 'Pausa' },
        { field: 'task', header: 'Task' },
        { field: 'micro_task', header: 'Microtask' },
        { field: 'is', header: 'IS' },
        { field: 'sequence', header: 'Sequence' },
        { field: 'transaction', header: 'Transaction' },
        { field: 'ml1', header: 'ML 1' },
        { field: 'ml2', header: 'ML 2' },
        { field: 'ml3', header: 'ML 3' }
    ];
    return cols;
});

const selectedColumns = ref([]);

// Initialize selectedColumns when participants are loaded
watch(participants, (newParticipants) => {
    if (newParticipants.length > 0 && selectedColumns.value.length === 0) {
        // Mostra inizialmente solo: #, Turno, Time, Parlanti, Pause, Task, Microtask, IS (Interactional Segment) e Sequence
        const initialFields = ['index', 'turn', 'time', 'pause', 'task', 'micro_task', 'is', 'sequence'];
        const participantFields = newParticipants.map(p => 'participant_' + p.code);

        selectedColumns.value = columns.value.filter(col =>
            initialFields.includes(col.field) || participantFields.includes(col.field)
        );
    }
}, { immediate: true });
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'micro_task.task.type.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'micro_task.type.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'sequence.interactional_segment_id': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'sequence.type.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'transaction.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'move_level1.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'move_level2.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'move_level3.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'non_verbal_action.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const globalFilterFields = [
    'annotation',
    'participant.code',
    'micro_task.task.type.name',
    'micro_task.type.name',
    'transaction.name',
    'move_level1.name',
    'move_level2.name',
    'move_level3.name',
    'non_verbal_action.name'
];


const onColumnToggle = (val) => {
    selectedColumns.value = columns.value.filter((col) => val.some(s => s.field === col.field));
};

const isColumnSelected = (field) => {
    return selectedColumns.value.some(c => c.field === field);
};

const getNotesForMove = (move) => {
    if (!props.notes || props.notes.length === 0) return '-';

    // Trova le note che si sovrappongono temporalmente alla mossa
    const relevantNotes = props.notes.filter(note => {
        return (note.begin < move.end && note.end > move.begin);
    });

    return relevantNotes.map(n => n.content).join('; ') || '-';
};

const onRowClick = (event) => {
    const data = event.data;
    emit('seek', data);
};

const activeGroup = computed(() => {
    const timeInMs = props.currentTime * 1000;
    const activeRow = props.moves.find(m => timeInMs >= m.begin && timeInMs <= m.end);
    if (!activeRow) return null;

    return {
        task: activeRow.micro_task?.task?.type?.name,
        microTask: activeRow.micro_task?.type?.name,
        sequence: activeRow.sequence?.type?.name
    };
});

const isTaskActive = (data) => activeGroup.value?.task === data.micro_task?.task?.type?.name;
const isMicroTaskActive = (data) => isTaskActive(data) && activeGroup.value?.microTask === data.micro_task?.type?.name;
const isSequenceActive = (data) => isTaskActive(data) && activeGroup.value?.sequence === data.sequence?.type?.name;

const rowClass = (data) => {
    const timeInMs = props.currentTime * 1000;
    const isActive = timeInMs >= data.begin && timeInMs <= data.end;
    const classes = {
        'wavesurfer-active-row': isActive
    };

    if (isActive && data.participant) {
        classes['active-participant-' + data.participant.id] = true;
    } else if (isActive) {
        classes['active-pause'] = true;
    }

    return classes;
};

const dynamicStyles = computed(() => {
    let styles = '';
    participants.value.forEach(p => {
        const color = getParticipantColor(p.role);
        const lightColor = getParticipantLightColor(p.role);
        styles += `
            .active-participant-${p.id}.wavesurfer-active-row {
                background-color: ${lightColor} !important;
                color: ${color} !important;
                border-top: 1px solid ${color} !important;
                border-bottom: 1px solid ${color} !important;
            }
        `;
    });
    styles += `
        .active-pause.wavesurfer-active-row {
            background-color: #f8fafc !important;
            color: #94a3b8 !important;
        }
    `;
    return styles;
});

// Scroll to active row
watch(() => props.currentTime, (newTime) => {
    const timeInMs = newTime * 1000;
    const activeRow = props.moves.find(m => timeInMs >= m.begin && timeInMs <= m.end);
    if (activeRow) {
        const rowElement = document.querySelector(`.wavesurfer-active-row`);
        if (rowElement) {
            rowElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
});
</script>

<style scoped>
:deep(.p-datatable-tbody > tr) {
    cursor: pointer;
}
</style>
