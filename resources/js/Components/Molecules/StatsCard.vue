<script setup>
import { computed } from 'vue';
import Badge from '../Atoms/Badge.vue';
import Icon from '../Atoms/Icon.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: 0,
    },
    unit: {
        type: String,
        default: null,
    },
    subtitle: {
        type: String,
        default: '',
    },
    iconName: {
        type: String,
        default: null,
    },
    iconColor: {
        type: String,
        default: 'brand', // 'brand' | 'emerald' | 'amber' | 'blue' | 'rose' | 'slate'
    },
    badgeText: {
        type: String,
        default: null,
    },
    badgeVariant: {
        type: String,
        default: 'primary',
    },
    trend: {
        type: String,
        default: null, // e.g. '+12.5%' or '-4.2%'
    },
    trendType: {
        type: String,
        default: null, // 'up' | 'down' | 'neutral'
    },
    trendLabel: {
        type: String,
        default: 'vs bln lalu',
    },
    progress: {
        type: Number,
        default: null, // 0 - 100
    },
    clickable: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);

const computedTrendType = computed(() => {
    if (props.trendType) return props.trendType;
    if (!props.trend) return null;
    if (props.trend.startsWith('+')) return 'up';
    if (props.trend.startsWith('-')) return 'down';
    return 'neutral';
});

const iconContainerClasses = computed(() => {
    switch (props.iconColor) {
        case 'brand':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40';
        case 'emerald':
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40';
        case 'amber':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-100 dark:border-amber-900/40';
        case 'rose':
            return 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-100 dark:border-rose-900/40';
        case 'blue':
            return 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-400 border border-sky-100 dark:border-sky-900/40';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
    }
});
</script>

<template>
    <div
        @click="clickable && $emit('click')"
        :class="[
            'p-4.5 shadow-xs relative flex flex-col justify-between overflow-hidden rounded-xl border border-slate-200/90 bg-white transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 sm:p-5',
            clickable
                ? 'cursor-pointer hover:border-blue-300 hover:shadow-md dark:hover:border-blue-500/50'
                : '',
        ]"
    >
        <!-- Top: Label & Icon -->
        <div>
            <div class="flex items-start justify-between gap-3">
                <span
                    class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                >
                    {{ title }}
                </span>

                <div
                    v-if="iconName || $slots.icon"
                    :class="[
                        'shadow-xs flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors',
                        iconContainerClasses,
                    ]"
                >
                    <slot name="icon">
                        <Icon :name="iconName" class="h-4.5 w-4.5" />
                    </slot>
                </div>
            </div>

            <!-- Value & Optional Badge / Unit -->
            <div class="mt-2.5 flex items-baseline gap-2">
                <span
                    class="font-mono text-2xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-white sm:text-3xl"
                >
                    {{ value }}
                </span>
                <span
                    v-if="unit"
                    class="text-xs font-semibold text-slate-400 dark:text-slate-500"
                >
                    {{ unit }}
                </span>
                <Badge
                    v-if="badgeText"
                    :variant="badgeVariant"
                    size="sm"
                    class="ms-auto"
                >
                    {{ badgeText }}
                </Badge>
            </div>
        </div>

        <!-- Optional Progress Bar -->
        <div v-if="progress !== null" class="mt-3">
            <div
                class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400"
            >
                <span>Realisasi</span>
                <span class="font-mono font-semibold">{{ progress }}%</span>
            </div>
            <div
                class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
            >
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="[
                        progress >= 90
                            ? 'bg-rose-500'
                            : progress >= 75
                              ? 'bg-amber-500'
                              : 'bg-blue-600',
                    ]"
                    :style="{ width: `${Math.min(progress, 100)}%` }"
                />
            </div>
        </div>

        <!-- Bottom: Subtitle & Trend -->
        <div
            v-if="subtitle || trend"
            class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5 text-xs dark:border-slate-800/80"
        >
            <p
                v-if="subtitle"
                class="truncate text-[11px] text-slate-500 dark:text-slate-400"
            >
                {{ subtitle }}
            </p>

            <!-- Trend Indicator -->
            <div
                v-if="trend"
                class="ms-auto flex items-center gap-1 font-medium"
            >
                <span
                    :class="[
                        'inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 font-mono text-[11px] font-bold',
                        computedTrendType === 'up'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                            : computedTrendType === 'down'
                              ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                              : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                    ]"
                >
                    <svg
                        v-if="computedTrendType === 'up'"
                        class="h-3 w-3"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M12 7a1 1 0 011 1v5a1 1 0 11-2 0V9.414l-4.293 4.293a1 1 0 01-1.414-1.414L9.586 8H7a1 1 0 010-2h5z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg
                        v-else-if="computedTrendType === 'down'"
                        class="h-3 w-3"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M12 13a1 1 0 01-1 1H6a1 1 0 010-2h2.586l-4.293-4.293a1 1 0 011.414-1.414L10 10.586V8a1 1 0 112 0v5z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ trend }}
                </span>
                <span
                    v-if="trendLabel"
                    class="text-[10px] text-slate-400 dark:text-slate-500"
                >
                    {{ trendLabel }}
                </span>
            </div>
        </div>
    </div>
</template>
