<script setup>
import Icon from '../Atoms/Icon.vue';
import TextInput from '../Atoms/TextInput.vue';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Cari data...',
    },
    size: {
        type: String,
        default: 'md',
    },
});

defineEmits(['update:modelValue', 'clear']);
</script>

<template>
    <div class="relative w-full">
        <TextInput
            :model-value="modelValue"
            @update:model-value="$emit('update:modelValue', $event)"
            :placeholder="placeholder"
            :size="size"
        >
            <template #prefix>
                <Icon
                    name="search"
                    class="h-4 w-4 text-slate-400 dark:text-slate-500"
                />
            </template>

            <template v-if="modelValue" #suffix>
                <button
                    type="button"
                    @click="
                        $emit('update:modelValue', '');
                        $emit('clear');
                    "
                    class="rounded-full p-1 text-slate-400 hover:text-slate-600 focus:outline-none dark:hover:text-slate-200"
                    title="Hapus pencarian"
                >
                    <Icon name="close" class="h-3.5 w-3.5" />
                </button>
            </template>
        </TextInput>
    </div>
</template>
