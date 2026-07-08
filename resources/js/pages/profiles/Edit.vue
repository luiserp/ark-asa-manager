<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GameServerProfileController from '@/actions/App/Http/Controllers/GameServerProfileController';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/profiles';
import ProfileForm from './ProfileForm.vue';

interface Profile {
    id: string;
    name: string;
    map: string;
    game_port: number;
    query_port: number;
    rcon_port: number;
    session_name: string;
    admin_password: string | null;
    server_password: string | null;
    max_players: number;
}

defineProps<{
    server: { id: string; name: string };
    profile: Profile;
    maps: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profiles', href: index() },
            { title: 'Edit Profile', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${profile.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <Heading :title="`Edit: ${profile.name}`" description="Update profile configuration" />

        <ProfileForm
            :form-action="GameServerProfileController.update.form({ profile: profile.id })"
            :values="profile"
            :maps="maps"
            submit-label="Save Changes"
        />
    </div>
</template>
