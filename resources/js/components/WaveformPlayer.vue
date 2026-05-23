<template>
  <div class="waveform-container">
    <div ref="waveformRef" class="w-full"></div>

    <div class="controls mt-6 flex items-center gap-3">
      <Button
        :icon="isPlaying ? 'pi pi-pause' : 'pi pi-play'"
        @click="togglePlay"
        :label="isPlaying ? 'Pause' : 'Play'"
        severity="primary"
        rounded
      />

      <div v-if="duration > 0" class="text-sm font-medium text-gray-600">
        {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
      </div>
    </div>

    <div v-show="elanUrl" ref="elanRef" class="elan-container mt-6 overflow-x-auto"></div>
    <div v-if="moves.length > 0" class="mt-10">
      <TranscriptTable
        :moves="moves"
        :currentTime="currentTime"
        @seek="onSeek"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import WaveSurfer from 'wavesurfer.js';
import TimelinePlugin from 'wavesurfer.js/dist/plugins/timeline.esm.js';
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
  options: {
    type: Object,
    default: () => ({})
  }
});

const waveformRef = ref(null);
const elanRef = ref(null);
const wavesurfer = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);

const initWaveSurfer = () => {
  if (wavesurfer.value) {
    wavesurfer.value.destroy();
  }

  const plugins = [
    TimelinePlugin.create({
        timeInterval: 0.5
    })
  ];

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
    height: 80,
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
});

onBeforeUnmount(() => {
  if (wavesurfer.value) {
    wavesurfer.value.destroy();
  }
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
