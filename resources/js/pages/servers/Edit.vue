<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GameServerController from '@/actions/App/Http/Controllers/GameServerController';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/servers';
import ServerForm from './ServerForm.vue';

interface GameServer {
    id: string;
    name: string;
    host: string;
    port: number;
    username: string;
    ssh_key_path: string;
    server_dir: string;
    backups_dir: string;
    logs_dir: string;
    binary_path: string;
    proton_path: string;
    steam_app_id: string;
    is_active: boolean;
}

const props = defineProps<{
    server: GameServer;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servers', href: index() },
            { title: 'Edit Server', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${server.name}`" />

    <div class="flex flex-col gap-6 p-12">
        <Heading :title="`Edit: ${server.name}`" description="Update server configuration" />

        <ServerForm
            :form-action="GameServerController.update.form({ server: server.id })"
            :values="server"
            submit-label="Save Changes"
            is-edit
        />
    </div>
</template>
