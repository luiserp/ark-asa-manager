<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GameServerController from '@/actions/App/Http/Controllers/GameServerController';
import Heading from '@/components/Heading.vue';
import { index, create } from '@/routes/servers';
import ServerForm from './ServerForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servers', href: index() },
            { title: 'Add Server', href: create() },
        ],
    },
});

defineProps<{
    defaults: {
        port: number;
        username: string;
        server_dir: string;
        backups_dir: string;
        logs_dir: string;
        binary_path: string;
        proton_path: string;
        steam_app_id: string;
    };
}>();
</script>

<template>
    <Head title="Add Server" />

    <div class="flex flex-col gap-6 p-12">
        <Heading title="Add Server" description="Configure a new ARK server instance" />

        <ServerForm
            :form-action="GameServerController.store.form()"
            :values="defaults"
            submit-label="Create Server"
        />
    </div>
</template>
