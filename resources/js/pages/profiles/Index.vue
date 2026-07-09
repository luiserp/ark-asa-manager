<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GameServerProfileController from '@/actions/App/Http/Controllers/GameServerProfileController';
import ProfileConfigController from '@/actions/App/Http/Controllers/ProfileConfigController';
import ProfileModsController from '@/actions/App/Http/Controllers/ProfileModsController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { index, create } from '@/routes/profiles';
import { Plus, Pencil, Trash2, Copy, FileCode, Blocks } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profiles', href: index() },
        ],
    },
});

interface Profile {
    id: string;
    name: string;
    map: string;
    session_name: string;
    max_players: number;
    is_active: boolean;
}

interface GameServer {
    id: string;
    name: string;
}

const props = defineProps<{
    server: GameServer;
    profiles: Profile[];
    servers: GameServer[];
}>();

const activeProfileId = computed(() => props.profiles.find(p => p.is_active)?.id ?? '');

const replicateDialogOpen = ref(false);
const replicateProfile = ref<Profile | null>(null);
const targetServerId = ref('');

function setActive(profileId: string) {
    router.patch(GameServerProfileController.toggleActive.url({ profile: profileId }), {}, {
        preserveScroll: true,
    });
}

function deleteProfile(profile: Profile) {
    router.delete(GameServerProfileController.destroy.url({ profile: profile.id }));
}

function openReplicateDialog(profile: Profile) {
    replicateProfile.value = profile;
    targetServerId.value = props.server.id;
    replicateDialogOpen.value = true;
}

function confirmReplicate() {
    if (!replicateProfile.value) return;

    router.post(
        GameServerProfileController.replicate.url({ profile: replicateProfile.value.id }),
        { game_server_id: targetServerId.value },
        { onSuccess: () => { replicateDialogOpen.value = false; } },
    );
}
</script>

<template>
    <Head title="Profiles" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Profiles" :description="`Manage launch profiles for ${server.name}`" />
            <Button as-child>
                <Link :href="create()">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Profile
                </Link>
            </Button>
        </div>

        <div v-if="profiles.length === 0" class="rounded-xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">No profiles yet.</p>
            <Button as-child class="mt-4">
                <Link :href="create()">Create your first profile</Link>
            </Button>
        </div>

        <RadioGroup :model-value="activeProfileId" @update:model-value="setActive($event as string)" class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Map</TableHead>
                        <TableHead>Session</TableHead>
                        <TableHead>Players</TableHead>
                        <TableHead>Active</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="profile in profiles" :key="profile.id">
                        <TableCell class="font-medium">
                            <Link :href="ProfileConfigController.index.url({ profile: profile.id })" class="hover:underline">
                                {{ profile.name }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Badge variant="secondary">{{ profile.map }}</Badge>
                        </TableCell>
                        <TableCell>{{ profile.session_name }}</TableCell>
                        <TableCell>{{ profile.max_players }}</TableCell>
                        <TableCell>
                            <RadioGroupItem :value="profile.id" />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="ghost" size="icon" as-child title="Config">
                                    <Link :href="ProfileConfigController.index.url({ profile: profile.id })">
                                        <FileCode class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" as-child title="Mods">
                                    <Link :href="ProfileModsController.index.url({ profile: profile.id })">
                                        <Blocks class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" title="Replicate" @click="openReplicateDialog(profile)">
                                    <Copy class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="GameServerProfileController.edit.url({ profile: profile.id })">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </Button>

                                <AlertDialog>
                                    <AlertDialogTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Delete profile?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will remove "{{ profile.name }}". This action cannot be undone.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="deleteProfile(profile)">
                                                Delete
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </RadioGroup>
    </div>

    <Dialog v-model:open="replicateDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Replicate Profile</DialogTitle>
                <DialogDescription>
                    Clone "{{ replicateProfile?.name }}" to a server.
                </DialogDescription>
            </DialogHeader>

            <div class="grid w-full gap-2 py-4">
                <Label for="target-server">Target Server</Label>
                <Select v-model="targetServerId">
                    <SelectTrigger id="target-server" class="w-full">
                        <SelectValue placeholder="Select a server" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in servers" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="replicateDialogOpen = false">Cancel</Button>
                <Button @click="confirmReplicate">Replicate</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
