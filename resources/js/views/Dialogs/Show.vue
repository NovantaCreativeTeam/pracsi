<template>
    <div class="p-6">
        <div v-if="loading" class="flex justify-center items-center h-64">
            <ProgressSpinner />
        </div>

        <div v-else-if="dialog">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <Button icon="pi pi-arrow-left" severity="secondary" rounded text @click="router.back()" />
                    <h1 class="text-2xl font-bold">
                        {{ dialog.title }} ({{ dialog.reference }})
                        <Tag v-if="dialog.is_published" value="Pubblicato" severity="success" class="ml-2" />
                        <Tag v-else value="Bozza" severity="secondary" class="ml-2" />
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <template v-if="navStore.isActive">
                        <span class="text-sm text-gray-500 mr-2">
                            {{ navStore.currentDisplayIndex }} di {{ navStore.total }}
                        </span>
                        <Button
                            icon="pi pi-chevron-left"
                            severity="secondary"
                            :disabled="!navStore.hasPrevious"
                            @click="navigate('previous')"
                            v-tooltip.bottom="'Precedente'"
                        />
                        <Button
                            icon="pi pi-chevron-right"
                            severity="secondary"
                            :disabled="!navStore.hasNext"
                            @click="navigate('next')"
                            v-tooltip.bottom="'Successivo'"
                        />
                        <div class="w-px h-6 bg-gray-300 mx-2"></div>
                    </template>
                    <div class="flex gap-2">
                        <Button
                            v-if="authStore.hasPermission('publish-dialogs')"
                            :label="dialog.is_published ? 'Ritira' : 'Pubblica'"
                            :icon="dialog.is_published ? 'pi pi-eye-slash' : 'pi pi-eye'"
                            :severity="dialog.is_published ? 'warning' : 'success'"
                            outlined
                            @click="togglePublish"
                        />
                        <Button label="Elimina" icon="pi pi-trash" severity="danger" outlined @click="confirmDelete" />
                    </div>
                </div>
            </div>

            <!-- Descrizione se presente -->
            <Card v-if="dialog.description" class="mb-8">
                <template #title>Descrizione</template>
                <template #content>
                    <p>{{ dialog.description }}</p>
                </template>
            </Card>

            <!-- Contenuto con Tab -->
            <Card>
                <template #content>
                    <Tabs :value="dialog.audio_path ? '1' : 'info'">
                        <TabList>
                            <Tab value="1" v-if="dialog.audio_path">Audio</Tab>
                            <Tab value="2" v-if="dialog.audio_path">Audio e Trascrizione</Tab>
                            <Tab value="0">Trascrizione per la stampa</Tab>
                            <Tab value="participants">Partecipanti</Tab>
                            <Tab value="info">Informazioni</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="1" v-if="dialog.audio_path">
                                <div class="mt-4">
                                    <WaveformPlayer
                                        :url="dialog.audio_path"
                                        :moves="moves"
                                        :notes="dialog.notes || []"
                                        :showTranscript="false"
                                        :showRegions="['micro_task', 'sequence.interactional_segment']"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="2" v-if="dialog.audio_path">
                                <div class="mt-4">
                                    <WaveformPlayer
                                        :url="dialog.audio_path"
                                        :moves="moves"
                                        :notes="dialog.notes || []"
                                        :showRegions="['micro_task', 'sequence.interactional_segment']"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="0">
                                <!-- Trascrizione per la stampa -->
                                <div class="overflow-hidden max-w-full">
                                    <div class="flex justify-between items-center mb-4 pt-4">
                                        <h2 class="text-xl font-bold">Trascrizione per la stampa</h2>
                                        <div class="flex gap-4 items-center">
                                            <Button icon="pi pi-external-link" label="Esporta CSV" severity="primary" @click="exportCSV" size="small" />
                                            <IconField>
                                                <InputIcon class="pi pi-search" />
                                                <InputText v-model="filters['global'].value" placeholder="Cerca ovunque..." size="small" />
                                            </IconField>
                                            <MultiSelect
                                                v-model="selectedColumns"
                                                :options="columns"
                                                optionLabel="header"
                                                @update:modelValue="onColumnToggle"
                                                placeholder="Seleziona Colonne"
                                                class="w-64"
                                                :showToggleAll="false"
                                                filter
                                                size="small"
                                            />
                                        </div>
                                    </div>

                                    <DataTable
                                        ref="dt"
                                        :value="moves"
                                        v-model:filters="filters"
                                        :exportFilename="dialog?.reference || 'trascrizione'"
                                        :globalFilterFields="[
                                            'participant.code',
                                            'annotation',
                                            'micro_task.task.type.name',
                                            'sequence.type.name',
                                            'micro_task.type.name',
                                            'transaction.name',
                                            'move_level1s',
                                            'move_level2s',
                                            'move_level3s',
                                            'non_verbal_actions'
                                        ]"
                                        class="p-datatable-sm text-sm"
                                        filterDisplay="menu"
                                        showGridlines
                                        rowGroupMode="rowspan"
                                        scrollable
                                        scrollDirection="both"
                                        tableStyle="min-width: 80rem"
                                        :groupRowsBy="['micro_task.task.type.name', 'sequence.type.name', 'micro_task.type.name']"
                                    >
                                        <Column v-if="selectedColumns.some(c => c.field === 'index')" field="index" header="#" class="w-12 text-gray-400">
                                            <template #body="slotProps">
                                                {{ slotProps.index + 1 }}
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'begin')" field="begin" header="Inizio">
                                            <template #body="{ data }">
                                                {{ formatTime(data.begin) }}
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'end')" field="end" header="Fine">
                                            <template #body="{ data }">
                                                {{ formatTime(data.end) }}
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'task')" field="micro_task.task.type.name" header="Task" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.micro_task?.task?.type?.name || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per task" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'interactional_segment')" field="sequence.interactional_segment_id" header="IS" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.sequence?.interactional_segment_id ? `IS ${data.sequence.interactional_segment_id}` : '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per IS" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'sequence')" field="sequence.type.name" header="Sequence" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.sequence?.type?.name || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per sequence" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'turn')" field="turn" header="Turno" class="font-bold">
                                            <template #body="{ data }">
                                                {{ data.turn || '' }}
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'participant')" field="participant.code" header="Parlante" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                <span v-if="data.participant">{{ data.participant.code }}</span>
                                                <span v-else class="text-gray-400 italic">Pausa</span>
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per parlante" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'non_verbal_action')" field="non_verbal_actions" header="Non Verbal Action" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.non_verbal_actions?.map(a => a.name).join(', ') || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per Non Verbal Action" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'annotation')" field="annotation" header="Trascrizione" :showFilterMatchModes="false">
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per trascrizione" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'notes')" field="notes" header="Note" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ getNotesForMove(data) }}
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'micro_task')" field="micro_task.type.name" header="Micro Task" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.micro_task?.type?.name || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per micro task" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'transaction')" field="transaction.name" header="Transaction" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.transaction?.name || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per transaction" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'move_level1')" field="move_level1s" header="ML 1" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.move_level1s?.map(l => l.name).join(', ') || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per ML 1" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'move_level2')" field="move_level2s" header="ML 2" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.move_level2s?.map(l => l.name).join(', ') || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per ML 2" />
                                            </template>
                                        </Column>

                                        <Column v-if="selectedColumns.some(c => c.field === 'move_level3')" field="move_level3s" header="ML 3" :showFilterMatchModes="false">
                                            <template #body="{ data }">
                                                {{ data.move_level3s?.map(l => l.name).join(', ') || '-' }}
                                            </template>
                                            <template #filter="{ filterModel }">
                                                <InputText v-model="filterModel.value" type="text" placeholder="Filtra per ML 3" />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <TabPanel value="participants">
                                <div class="mt-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                        <Card v-for="participant in dialog.participants" :key="participant.id"
                                              class="shadow-sm border-l-4"
                                              :style="{ borderLeftColor: getParticipantColor(participant.role), backgroundColor: getParticipantLightColor(participant.role) }">
                                           <template #title>
                                               <div class="flex items-center gap-2">
                                                   <i class="pi pi-user text-xl" :style="{ color: getParticipantColor(participant.role) }"></i>
                                                   <span :style="{ color: getParticipantColor(participant.role) }">{{ participant.code }}</span>
                                               </div>
                                           </template>
                                            <template #subtitle>
                                                {{ participant.role || '-' }}
                                            </template>
                                            <template #content>
                                                <div class="flex flex-col gap-1 text-sm">
                                                    <div v-if="participant.full_name">
                                                        <span class="font-semibold text-gray-500">Nome:</span> {{ participant.full_name }}
                                                    </div>
                                                    <div>
                                                        <span class="font-semibold text-gray-500">Genere:</span> {{ participant.gender || '-' }}
                                                    </div>
                                                    <div>
                                                        <span class="font-semibold text-gray-500">Età:</span> {{ participant.age_range || '-' }}
                                                    </div>
                                                    <div>
                                                        <span class="font-semibold text-gray-500">Occupazione:</span> {{ participant.occupation || '-' }}
                                                    </div>
                                                    <div v-if="participant.languages">
                                                        <span class="font-semibold text-gray-500">Lingue:</span> {{ participant.languages }}
                                                    </div>
                                                </div>
                                            </template>
                                        </Card>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel value="info">
                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full text-sm border-collapse">
                                        <tbody>
                                            <!-- Informazioni Generali -->
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500 w-1/3">Corpus:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.corpus?.project_reference }} - {{ dialog.corpus?.title }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Codice:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.reference }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Data:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.date || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Città/Regione:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.city || '-' }}, {{ dialog.region || '-' }} ({{ dialog.country || '-' }} - {{ dialog.continent || '-' }})</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Lingue Soggetto:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.subject_languages || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Lingue di Lavoro:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.working_languages || '-' }}</td>
                                            </tr>

                                            <!-- Dettagli Sessione -->
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Genere:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.genre || '-' }} ({{ dialog.subgenre || '-' }})</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Topic:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.topic || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Coinvolgimento Ricercatore:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.researcher_involvement || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Tipo Pianificazione:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.planning_type || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Contesto Sociale:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.social_context || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Clienti:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.customer_n }} (parlanti: {{ dialog.speaking_customer_n }})</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Tipo Cliente:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.customer_type || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Profilo Cliente:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.customer_profile || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Caratteristiche Parlanti:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.speakers_features || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Ristorante:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.restaurant_title || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Caratteristiche Ristorante:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.restaurant_features || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Tipo Menu:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.menu_type || '-' }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2 pr-4 font-semibold text-slate-500">Pasto:</td>
                                                <td class="py-2 text-slate-700">{{ dialog.meal || '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </template>
            </Card>
        </div>

        <div v-else class="text-center p-12">
            <h2 class="text-xl text-gray-500">Dialogo non trovato</h2>
            <Button label="Torna all'elenco" icon="pi pi-arrow-left" class="mt-4" @click="router.push({ name: 'dialogs.index' })" />
        </div>

        <Toast />
        <ConfirmDialog />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { dialogService } from '../../services/dialogService';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { getParticipantColor, getParticipantLightColor } from '../../utils/colors';

import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
// import Dialog from 'primevue/dialog'; // Rimosso
import WaveformPlayer from '../../components/WaveformPlayer.vue';

import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from '@primevue/core/api';

import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { useDialogNavigationStore } from '../../stores/dialogNavigation';
import { useTablePreferencesStore } from '../../stores/tablePreferences';
import { useAuthStore } from '../../stores/auth';
import { storeToRefs } from 'pinia';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const navStore = useDialogNavigationStore();
const tablePrefs = useTablePreferencesStore();
const authStore = useAuthStore();
const { transcriptSelectedColumns, transcriptFilters } = storeToRefs(tablePrefs);

const loading = ref(true);
const dialog = ref(null);
const moves = ref([]);
const dt = ref();
// const showPlayer = ref(false); // Rimosso perché non più necessario

const columns = ref([
    { field: 'index', header: '#' },
    { field: 'begin', header: 'Inizio' },
    { field: 'end', header: 'Fine' },
    { field: 'task', header: 'Task' },
    { field: 'interactional_segment', header: 'Interactional Segment' },
    { field: 'sequence', header: 'Sequence' },
    { field: 'turn', header: 'Turno' },
    { field: 'participant', header: 'Parlante' },
    { field: 'non_verbal_actions', header: 'Non Verbal Action' },
    { field: 'annotation', header: 'Trascrizione' },
    { field: 'notes', header: 'Note' },
    { field: 'micro_task', header: 'Micro Task' },
    { field: 'transaction', header: 'Transaction' },
    { field: 'move_level1s', header: 'ML 1' },
    { field: 'move_level2s', header: 'ML 2' },
    { field: 'move_level3s', header: 'ML 3' }
]);

const initialFields = ['index', 'turn', 'begin', 'end', 'task', 'sequence', 'participant', 'annotation', 'micro_task', 'move_level1s', 'notes'];
const selectedColumns = ref([]);

// Inizializza selectedColumns considerando le preferenze e aggiungendo sempre 'participant'
if (transcriptSelectedColumns.value) {
    selectedColumns.value = columns.value.filter(col =>
        transcriptSelectedColumns.value.some(pc => pc.field === col.field) || col.field === 'participant'
    );
} else {
    selectedColumns.value = columns.value.filter(col => initialFields.includes(col.field));
}

const filters = ref({});

// Inizializza i filtri in modo da non attivare immediatamente il watcher con valori di default se già presenti nello store
if (transcriptFilters.value) {
    filters.value = JSON.parse(JSON.stringify(transcriptFilters.value));
} else {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        'participant.code': { value: null, matchMode: FilterMatchMode.CONTAINS },
        annotation: { value: null, matchMode: FilterMatchMode.CONTAINS },
        'micro_task.task.type.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'sequence.interactional_segment_id': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'sequence.type.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'micro_task.type.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'transaction.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
        move_level1s: { value: null, matchMode: FilterMatchMode.CONTAINS },
        move_level2s: { value: null, matchMode: FilterMatchMode.CONTAINS },
        move_level3s: { value: null, matchMode: FilterMatchMode.CONTAINS },
        non_verbal_actions: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
}

watch(filters, (val) => {
    transcriptFilters.value = val;
}, { deep: true });

const onColumnToggle = (val) => {
    selectedColumns.value = columns.value.filter((col) => val.includes(col));
    // Persisti solo le colonne che non sono legate al partecipante generico
    transcriptSelectedColumns.value = JSON.parse(JSON.stringify(selectedColumns.value.filter(col => col.field !== 'participant')));
};

const formatTime = (milliseconds) => {
    if (milliseconds === null || milliseconds === undefined) return '-';
    const totalSeconds = milliseconds / 1000;
    const m = Math.floor(totalSeconds / 60);
    const s = (totalSeconds % 60).toFixed(2);
    return `${m}:${s.toString().padStart(5, '0')}`;
};

const getNotesForMove = (move) => {
    if (!dialog.value || !dialog.value.notes) return '-';

    // Trova le note che si sovrappongono temporalmente alla mossa
    const relevantNotes = dialog.value.notes.filter(note => {
        return (note.begin < move.end && note.end > move.begin);
    });

    return relevantNotes.map(n => n.content).join('; ') || '-';
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const navigate = (direction) => {
    const targetId = direction === 'next' ? navStore.nextId : navStore.previousId;
    if (targetId) {
        router.push({ name: 'dialogs.show', params: { id: targetId } });
    }
};

const loadDialog = async () => {
    loading.value = true;
    try {
        const response = await dialogService.get(route.params.id);
        dialog.value = response.data.data;
        moves.value = response.data.moves.map((move, index) => ({
            ...move,
            index: index + 1
        }));
    } catch (error) {
        console.error('Errore durante il caricamento del dialogo:', error);
        toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile caricare il dialogo', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const togglePublish = async () => {
    const newState = !dialog.value.is_published;
    const action = newState ? 'pubblicare' : 'ritirare';

    confirm.require({
        message: `Sei sicuro di voler ${action} il dialogo "${dialog.value.title}"?`,
        header: 'Conferma azione',
        icon: 'pi pi-exclamation-triangle',
        acceptProps: {
            label: newState ? 'Pubblica' : 'Ritira',
            severity: newState ? 'success' : 'warning'
        },
        rejectProps: {
            label: 'Annulla',
            severity: 'secondary',
            outlined: true
        },
        accept: async () => {
            try {
                await dialogService.update(dialog.value.id, { is_published: newState });
                dialog.value.is_published = newState;
                toast.add({
                    severity: 'success',
                    summary: 'Successo',
                    detail: `Dialogo ${newState ? 'pubblicato' : 'ritirato'} correttamente`,
                    life: 3000
                });
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Errore',
                    detail: `Impossibile ${action} il dialogo`,
                    life: 3000
                });
            }
        }
    });
};

const confirmDelete = () => {
    confirm.require({
        message: `Sei sicuro di voler eliminare il dialogo "${dialog.value.title}"?`,
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
        accept: async () => {
            try {
                await dialogService.delete(dialog.value.id);
                toast.add({ severity: 'success', summary: 'Successo', detail: 'Dialogo eliminato correttamente', life: 3000 });
                router.push({ name: 'dialogs.index' });
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Errore', detail: 'Impossibile eliminare il dialogo', life: 3000 });
            }
        }
    });
};

onMounted(() => {
    loadDialog();
    if (navStore.isActive) {
        navStore.setCurrentId(route.params.id);
    }
});

watch(() => route.params.id, (newId) => {
    if (newId) {
        loadDialog();
        if (navStore.isActive) {
            navStore.setCurrentId(newId);
        }
    }
});
</script>
