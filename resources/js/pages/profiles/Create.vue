<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GameServerProfileController from '@/actions/App/Http/Controllers/GameServerProfileController';
import Heading from '@/components/Heading.vue';
import { index, create } from '@/routes/profiles';
import ProfileForm from './ProfileForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profiles', href: index() },
            { title: 'Add Profile', href: create() },
        ],
    },
});

defineProps<{
    server: { id: string; name: string };
    defaults: {
        map: string;
        game_port: number;
        query_port: number;
        rcon_port: number;
        session_name: string;
        admin_password: string | null;
        server_password: string | null;
        max_players: number;
    };
    maps: Record<string, string>;
}>();
</script>

<template>
    <Head title="Add Profile" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Add Profile" :description="`New launch profile for ${server.name}`" />

        <ProfileForm
            :form-action="GameServerProfileController.store.form()"
            :values="defaults"
            :maps="maps"
            submit-label="Create Profile"
        />
    </div>
</template>
