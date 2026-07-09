<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import TeamController from '@/actions/App/Http/Controllers/TeamController';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

interface TeamOption {
    id: string;
    name: string;
    personal_team: boolean;
}

const page = usePage();
const user = computed(() => page.props.auth.user);
const currentTeam = computed(() => page.props.currentTeam as (TeamOption & { is_owner: boolean }) | null);
const teams = computed(() => page.props.teams as TeamOption[]);

const { getInitials } = useInitials();
const showAvatar = computed(() => user.value.avatar && user.value.avatar !== '');

function switchTeam(value: unknown) {
    const teamId = String(value);
    if (currentTeam.value?.id === teamId) return;
    router.post(TeamController.switchTeam.url({ team: teamId }), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <button
                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    data-test="header-user-menu">
                    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                        <AvatarFallback class="rounded-lg text-black dark:text-white">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="hidden flex-col items-end leading-tight sm:flex">
                        <span class="truncate text-sm font-medium">{{ user.name }}</span>
                        <span class="truncate text-xs text-muted-foreground">{{ currentTeam?.name ?? 'No team' }}</span>
                    </div>

                    <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-64 rounded-lg" side="bottom" align="end" :side-offset="8">
                <UserMenuContent :user="user" />

                <template v-if="teams.length > 1">
                    <DropdownMenuSeparator />
                    <DropdownMenuLabel>Switch Team</DropdownMenuLabel>
                    <DropdownMenuRadioGroup :model-value="currentTeam?.id ?? ''" @update:model-value="switchTeam">
                        <DropdownMenuRadioItem v-for="t in teams" :key="t.id" :value="t.id">
                            {{ t.name }}
                        </DropdownMenuRadioItem>
                    </DropdownMenuRadioGroup>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </header>
</template>
