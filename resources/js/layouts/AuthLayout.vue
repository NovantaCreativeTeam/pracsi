<template>
    <div class="flex items-center flex-col justify-center min-h-screen bg-grid-pattern relative overflow-hidden">
        <!-- Glow Alons -->
        <div class="bg-glow-blob top-24 left-15 animate-blob"></div>
        <div class="bg-glow-blob -bottom-24 -right-24 animate-blob" style="animation-delay: -5s;"></div>

        <div class="relative z-10 flex flex-col items-center justify-center w-full">
            <img src="/resources/images/logo-pracsi.png" alt="Pracsi Logo" class="w-30 mb-4" />
            <div class="w-full max-w-md p-8 bg-white/90 backdrop-blur-sm rounded-lg shadow-sm border border-slate-100">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
// Non serve logica qui, è solo un layout minimale
</script>
