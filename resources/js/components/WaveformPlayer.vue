<template>
  <div class="waveform-container" ref="containerRef">
    <!-- Placeholder per evitare il salto dello scroll quando il player diventa sticky -->
    <div :style="placeholderStyle"></div>
      <div :class="{ 'floating-player': isSticky }" :style="stickyStyle" class="transition-all border-none duration-300 ease-in-out">
      <div :class="['rounded-lg transition-all duration-300 ease-in-out',
        isSticky ? 'p-4 pt-6 bg-gray-100' : 'bg-white p-4 mb-4'
      ]">
        <div ref="waveformRef" class="w-full"></div>

        <div :class="['controls flex items-center justify-between', isSticky ? 'mt-2' : 'mt-6']">
          <div class="flex-1"></div>
          <div class="flex-1 flex justify-center">
            <Button
              :icon="isPlaying ? 'pi pi-pause' : 'pi pi-play'"
              @click="togglePlay"
              severity="primary"
              rounded
              :size="isSticky ? 'small' : undefined"
            />
          </div>

          <div class="flex-1 flex justify-end">
            <div v-if="duration > 0" :class="['font-medium text-gray-600', isSticky ? 'text-xs' : 'text-sm']">
              {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-show="elanUrl" ref="elanRef" class="elan-container mt-6 overflow-x-auto"></div>
    <div v-if="moves.length > 0 && showTranscript" class="mt-10">
      <TranscriptTable
        :moves="moves"
        :notes="notes"
        :currentTime="currentTime"
        @seek="onSeek"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import WaveSurfer from 'wavesurfer.js';
import TimelinePlugin from 'wavesurfer.js/dist/plugins/timeline.esm.js';
import RegionsPlugin from 'wavesurfer.js/dist/plugins/regions.esm.js';
import Button from 'primevue/button';
import ElanPlugin from '../plugins/wavesurfer.elan.js';
import TranscriptTable from './TranscriptTable.vue';

const props = defineProps({
  url: {
    type: String,
    required: true
  },
  elanUrl: {
    type: String,
    default: null
  },
  moves: {
    type: Array,
    default: () => []
  },
  notes: {
    type: Array,
    default: () => []
  },
  showTranscript: {
    type: Boolean,
    default: true
  },
  showRegions: {
    type: Boolean,
    default: true
  },
  options: {
    type: Object,
    default: () => ({})
  }
});

const containerRef = ref(null);
const waveformRef = ref(null);
const elanRef = ref(null);
const wavesurfer = ref(null);
const regions = ref(null);
const activeRegion = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const isSticky = ref(false);
const containerWidth = ref(0);
let resizeObserver = null;

const stickyStyle = computed(() => {
    if (!isSticky.value) return {};
    return {
        width: `${containerWidth.value}px`,
        position: 'fixed',
        top: '64px', // Altezza dell'header (h-16 = 4rem = 64px)
        zIndex: 100,
        borderRadius: '0 0 0.75rem 0.75rem',
        border: 'none',
    };
});

const handleScroll = () => {
    if (!containerRef.value) return;

    // Se l'elemento non ha dimensioni (es. tab nascosta), non facciamo nulla
    if (containerRef.value.offsetWidth === 0) {
        isSticky.value = false;
        return;
    }

    const rect = containerRef.value.getBoundingClientRect();
    const headerHeight = 64;

    // Aggiorna sempre la larghezza se l'elemento è visibile
    containerWidth.value = containerRef.value.offsetWidth;

    // Vediamo se l'elemento sta uscendo dalla parte superiore
    const wasSticky = isSticky.value;

    // rect.top è la distanza del container dall'inizio del viewport.
    // Se è minore dell'altezza dell'header, deve diventare sticky.
    // Aggiungiamo un controllo extra: se rect.top è 0 e rect.bottom è 0,
    // probabilmente l'elemento non è visibile o siamo in una fase di transizione delle tab.
    if (rect.top < headerHeight && rect.bottom > 0) {
        isSticky.value = true;
    } else {
        isSticky.value = false;
    }

    // Se lo stato sticky è cambiato, aggiorniamo l'altezza della waveform
    if (wasSticky !== isSticky.value && wavesurfer.value) {
        wavesurfer.value.setOptions({
            height: isSticky.value ? 64 : 128
        });
    }
};

const placeholderStyle = computed(() => {
    if (!isSticky.value) return { display: 'none' };

    // Altezza originale del player per evitare salti di scroll
    // p-4 (32px) + wavesurfer (128px) + mt-6 (24px) + controls (40px) + mb-4 (16px) = ~240px
    return {
        height: '240px',
        width: '100%',
        marginBottom: '1.5rem'
    };
});

const initWaveSurfer = () => {
  if (wavesurfer.value) {
    wavesurfer.value.destroy();
  }

  const plugins = [
    TimelinePlugin.create({
        timeInterval: 0.5
    })
  ];

  if (props.showRegions) {
    regions.value = RegionsPlugin.create();
    plugins.push(regions.value);
  }

  if (props.elanUrl) {
    plugins.push(ElanPlugin.create({
      url: props.elanUrl,
      container: elanRef.value,
      scrollToActive: true,
      tiers: {
            A: true,
            B: true,
            Y: true
        }
    }));
  }

  wavesurfer.value = WaveSurfer.create({
    container: waveformRef.value,
    waveColor: '#38819B',
    progressColor: '#ddd',
    cursorColor: '#C4E0E9',
    cursorWidth: 2,
    height: 128,
    mediaControls: false,
    backend: 'MediaElement',
    ...props.options,
    url: props.url,
    plugins: plugins
  });

  // Eventi WaveSurfer
  wavesurfer.value.on('play', () => {
    isPlaying.value = true;
  });

  wavesurfer.value.on('pause', () => {
    isPlaying.value = false;
  });

  wavesurfer.value.on('ready', () => {
    duration.value = wavesurfer.value.getDuration();
    // Se il player è già sticky all'inizializzazione (es. refresh pagina con scroll)
    // assicuriamoci che la larghezza sia corretta
    if (containerRef.value) {
        containerWidth.value = containerRef.value.offsetWidth;
    }

    // Create regions from micro_tasks
    if (props.showRegions && props.moves && props.moves.length > 0) {
        const microTasks = props.moves.reduce((acc, move) => {
            if (move.micro_task && move.micro_task.id) {
                const existing = acc.find(mt => mt.id === move.micro_task.id);
                if (!existing) {
                    acc.push({
                        id: move.micro_task.id,
                        name: move.micro_task.type?.name || 'Micro Task',
                        begin: move.begin,
                        end: move.end
                    });
                } else {
                    existing.begin = Math.min(existing.begin, move.begin);
                    existing.end = Math.max(existing.end, move.end);
                }
            }
            return acc;
        }, []);

        const colors = [
            'rgba(255, 99, 132, 0.2)',   // Red
            'rgba(54, 162, 235, 0.2)',   // Blue
            'rgba(255, 206, 86, 0.2)',   // Yellow
            'rgba(75, 192, 192, 0.2)',   // Green
            'rgba(153, 102, 255, 0.2)',  // Purple
            'rgba(255, 159, 64, 0.2)',   // Orange
            'rgba(199, 199, 199, 0.2)',  // Grey
            'rgba(83, 102, 255, 0.2)',   // Indigo
            'rgba(40, 167, 69, 0.2)',    // Success Green
            'rgba(23, 162, 184, 0.2)'    // Cyan
        ];

        microTasks.forEach((mt, index) => {
            regions.value.addRegion({
                start: mt.begin / 1000,
                end: mt.end / 1000,
                content: mt.name,
                color: colors[index % colors.length],
                drag: false,
                resize: false,
                loop: false
            });
        });
    }
  });

  wavesurfer.value.on('audioprocess', () => {
    currentTime.value = wavesurfer.value.getCurrentTime();
    updateElanHighlight(currentTime.value);
  });

  wavesurfer.value.on('interaction', () => {
    currentTime.value = wavesurfer.value.getCurrentTime();
    updateElanHighlight(currentTime.value);
  });

  wavesurfer.value.on('finish', () => {
    isPlaying.value = false;
  });

  if (props.showRegions && regions.value) {
    regions.value.on('region-clicked', (region, event) => {
        event.stopPropagation();

        activeRegion.value = null;

        wavesurfer.value.setTime(region.start);
        wavesurfer.value.play();

        setTimeout(() => {
            activeRegion.value = region;
        }, 10);
    });


    regions.value.on('region-out', (region) => {
        if (activeRegion.value === region) {
            wavesurfer.value.pause();
            activeRegion.value = null;
        }
    });
  }

  // Gestione Eventi Plugin ELAN
  const elan = wavesurfer.value.getActivePlugins().find(p => p instanceof ElanPlugin);
  if (elan) {
    elan.on('select', (start) => {
      wavesurfer.value.setTime(start);
      wavesurfer.value.play();
    });
  }
};

const onSeek = (time) => {
  if (wavesurfer.value) {
    wavesurfer.value.setTime(time);
    wavesurfer.value.play();
  }
};

const updateElanHighlight = (time) => {
  if (wavesurfer.value) {
    const elan = wavesurfer.value.getActivePlugins().find(p => p instanceof ElanPlugin);
    if (elan) {
      elan.highlight(time);
    }
  }
};

const togglePlay = () => {
  if (wavesurfer.value) {
    wavesurfer.value.playPause();
  }
};

const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = Math.floor(seconds % 60);
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
};

onMounted(() => {
  initWaveSurfer();

  // Inizializza la larghezza e lo stato sticky
  setTimeout(() => {
    handleScroll();
  }, 100);

  // Monitora i cambiamenti di dimensione del container (utile per le tab)
  if (window.ResizeObserver && containerRef.value) {
    resizeObserver = new ResizeObserver(() => {
        handleScroll();
    });
    resizeObserver.observe(containerRef.value);
  }

  window.addEventListener('scroll', handleScroll);
  window.addEventListener('resize', handleScroll);
});

onBeforeUnmount(() => {
  if (wavesurfer.value) {
    wavesurfer.value.destroy();
  }
  if (resizeObserver) {
    resizeObserver.disconnect();
  }
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', handleScroll);
});

// Watch per il cambio URL o mosse
watch(() => [props.url, props.elanUrl, props.moves], () => {
  initWaveSurfer();
});
</script>

<style scoped>
.waveform-container {
  width: 100%;
}

:deep(.wavesurfer-annotations) {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

:deep(.wavesurfer-annotations th),
:deep(.wavesurfer-annotations td) {
  border: 1px solid #e5e7eb;
  padding: 0.5rem;
  text-align: left;
}

:deep(.wavesurfer-annotations tr:hover) {
  background-color: #f9fafb;
  cursor: pointer;
}

:deep(.wavesurfer-time) {
  white-space: nowrap;
  color: #C4E0E9;
  width: 100px;
}
:deep(.wavesurfer-active-row) {
  background-color: #f3f4f6 !important;
  color: #38819B !important;
}
</style>
