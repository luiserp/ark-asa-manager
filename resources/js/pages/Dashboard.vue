<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

interface ServerStatus {
    id: string;
    name: string;
    host: string;
    is_current: boolean;
    connected: boolean;
    running: boolean;
    pid: number | null;
    uptime: string | null;
    error: string | null;
}

defineProps<{
    servers: ServerStatus[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div v-if="servers.length === 0" class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">No servers configured yet. Add a server to get started.</p>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="server in servers"
                :key="server.id"
                class="rounded-xl border p-5"
                :class="
                    server.connected && server.running
                        ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-950'
                        : server.connected
                            ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-950'
                            : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950'
                "
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-block h-2.5 w-2.5 rounded-full"
                            :class="
                                server.connected && server.running
                                    ? 'bg-green-500'
                                    : server.connected
                                        ? 'bg-yellow-500'
                                        : 'bg-red-500'
                            "
                        />
                        <h3 class="font-semibold">{{ server.name }}</h3>
                    </div>
                    <span
                        v-if="server.is_current"
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                    >
                        Current
                    </span>
                </div>

                <div class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <p><span class="font-medium">Host:</span> {{ server.host }}</p>
                    <p>
                        <span class="font-medium">Status:</span>
                        {{ server.connected && server.running ? 'Running' : server.connected ? 'Stopped' : 'Unreachable' }}
                    </p>
                    <p v-if="server.uptime"><span class="font-medium">Uptime:</span> {{ server.uptime }}</p>
                </div>

                <p v-if="server.error" class="mt-3 text-xs text-red-600 dark:text-red-400">
                    {{ server.error }}
                </p>
            </div>
        </div>
    </div>
</template>
