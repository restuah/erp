<script setup>
import Skeleton from '../Atoms/Skeleton.vue';

defineProps({
    columns: {
        type: Number,
        default: 5,
    },
    rows: {
        type: Number,
        default: 5,
    },
});
</script>

<template>
    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
        <tr
            v-for="r in rows"
            :key="r"
            class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
        >
            <td
                v-for="c in columns"
                :key="c"
                class="px-5 py-4"
            >
                <Skeleton
                    :width="c === 1 ? 'w-16' : c === 2 ? 'w-36' : c === columns ? 'w-20 ms-auto' : 'w-28'"
                    height="h-4"
                    rounded="rounded-md"
                />
            </td>
        </tr>
    </tbody>
</template>
