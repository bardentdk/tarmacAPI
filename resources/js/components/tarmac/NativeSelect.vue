<script setup lang="ts">
import type { Option } from '@/types/tarmac';

defineProps<{
    options: Option[];
    placeholder?: string;
    id?: string;
    name?: string;
}>();

const model = defineModel<string | null>();
</script>

<template>
    <select
        :id="id"
        v-model="model"
        :name="name"
        class="h-11 w-full min-w-0 rounded-xl border border-input bg-card px-3.5 text-base text-foreground outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
    >
        <option v-if="placeholder !== undefined" :value="null">
            {{ placeholder }}
        </option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
        >
            {{ option.label }}
        </option>
    </select>
</template>
