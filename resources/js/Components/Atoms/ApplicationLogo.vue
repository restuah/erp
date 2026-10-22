<script setup>
import { computed } from 'vue';
import logoWebp from '@images/logos/logo.webp';
import logoWhiteWebp from '@images/logos/logowhite.webp';
import logoBlackWebp from '@images/logos/logoblack.webp';
import logoFullWebp from '@images/logos/logo_with_name_hiruta.webp';
import logoIsoWebp from '@images/logos/logo_iso.webp';

const props = defineProps({
    variant: {
        type: String,
        default: 'white', // 'white' | 'color' | 'default' | 'black' | 'full' | 'iso'
    },
    alt: {
        type: String,
        default: 'PT. Hiruta Kogyo Indonesia',
    },
});

const currentSrc = computed(() => {
    switch (props.variant) {
        case 'default':
        case 'color':
            return logoWebp;
        case 'black':
            return logoBlackWebp;
        case 'full':
            return logoFullWebp;
        case 'iso':
            return logoIsoWebp;
        case 'white':
        default:
            return logoWhiteWebp;
    }
});
</script>

<template>
    <img
        :src="currentSrc"
        :alt="alt"
        loading="lazy"
        decoding="async"
        class="object-contain"
    />
</template>
