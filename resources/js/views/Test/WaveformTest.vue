<template>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Waveform Player Test</h1>
        </div>

        <div class="max-w-4xl">
            <h2 class="text-lg font-semibold mb-3">Esempio Audio Player con ELAN</h2>
            <WaveformPlayer
                url="/elan/IT_PSPR_PN29.wav"
                elan-url="/elan/IT_PSPR_PN29.eaf"
            />
        </div>
    </div>
</template>

<script setup>
import WaveformPlayer from '../../components/WaveformPlayer.vue';
</script>

<style scoped>
</style>
