<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Shield outline -->
        <path
            fill="currentColor"
            d="M20 2 4 9v12c0 9.33 6.83 18.06 16 20 9.17-1.94 16-10.67 16-20V9L20 2Z"
            opacity="0.15"
        />
        <path
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linejoin="round"
            d="M20 3 5 9.5v11.5c0 8.73 6.4 16.9 15 18.75 8.6-1.85 15-10.02 15-18.75V9.5L20 3Z"
        />
        <!-- Mountain / server peak -->
        <path
            fill="currentColor"
            stroke="currentColor"
            stroke-width="0.5"
            stroke-linejoin="round"
            d="M20 10l-9 16h6l3-5.5 3 5.5h6L20 10Z"
        />
        <!-- Signal lines (server status) -->
        <line x1="17" y1="30" x2="23" y2="30" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <line x1="15" y1="33" x2="25" y2="33" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
</template>
