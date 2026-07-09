<script setup lang="ts">
import { computed, reactive, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import echo from '@/echo';
import GameServerController from '@/actions/App/Http/Controllers/GameServerController';
import ServerDetailController from '@/actions/App/Http/Controllers/ServerDetailController';
import ServerFilesController from '@/actions/App/Http/Controllers/ServerFilesController';
import ServerProcessController from '@/actions/App/Http/Controllers/ServerProcessController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
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
import { index, create } from '@/routes/servers';
import { Plus, Pencil, Trash2, Copy, Info, Loader2, FolderOpen, Activity } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Servers',
                href: index(),
            },
        ],
    },
});

interface GameServer {
    id: string;
    name: string;
    host: string;
    port: number;
    username: string;
    is_active: boolean;
}

interface ServerStatus {
    installed: boolean;
    running: boolean;
    error?: string | null;
    players?: number | null;
    maxPlayers?: number | null;
}

const props = defineProps<{
    servers: GameServer[];
}>();

const activeServerId = computed(() => props.servers.find(s => s.is_active)?.id ?? '');
const statuses = reactive<Record<string, ServerStatus>>({});

let csrfToken = '';

function notifyLeave(serverId: string) {
    fetch(ServerDetailController.leave.url({ server: serverId }), {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
    });
}

onMounted(() => {
    csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    for (const server of props.servers) {
        echo.join(`server.${server.id}`)
            .listen('.ServerStatusUpdated', (e: ServerStatus) => {
                statuses[server.id] = e;
            });
    }
});

onUnmounted(() => {
    for (const server of props.servers) {
        notifyLeave(server.id);
        echo.leave(`server.${server.id}`);
    }
});

function setActive(serverId: string) {
    router.patch(GameServerController.toggleActive.url({ server: serverId }), {}, {
        preserveScroll: true,
    });
}

function deleteServer(server: GameServer) {
    router.delete(GameServerController.destroy.url({ server: server.id }));
}

function replicateServer(server: GameServer) {
    router.post(GameServerController.replicate.url({ server: server.id }));
}
</script>

<template>
    <Head title="Servers" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Servers" description="Manage your ARK server instances" />
            <Button as-child>
                <Link :href="create()">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Server
                </Link>
            </Button>
        </div>

        <div v-if="servers.length === 0" class="rounded-xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">No servers configured yet.</p>
            <Button as-child class="mt-4">
                <Link :href="create()">Add your first server</Link>
            </Button>
        </div>

        <RadioGroup :model-value="activeServerId" @update:model-value="setActive($event as string)" class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Host</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Active</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="server in servers" :key="server.id">
                        <TableCell class="font-medium">
                            <Link :href="ServerDetailController.index.url({ server: server.id })" class="hover:underline">
                                {{ server.name }}
                            </Link>
                        </TableCell>
                        <TableCell>{{ server.host }}:{{ server.port }}</TableCell>
                        <TableCell>
                            <Loader2 v-if="!statuses[server.id]" class="h-4 w-4 animate-spin text-muted-foreground" />
                            <Badge v-else-if="statuses[server.id]?.error" variant="destructive">Unreachable</Badge>
                            <Badge v-else-if="statuses[server.id]?.running" variant="default" class="bg-green-600">
                                Running
                                <template v-if="statuses[server.id]?.players != null">
                                    · {{ statuses[server.id].players }}<span v-if="statuses[server.id]?.maxPlayers">/{{ statuses[server.id].maxPlayers }}</span>
                                </template>
                            </Badge>
                            <Badge v-else-if="statuses[server.id]?.installed" variant="secondary">Stopped</Badge>
                            <Badge v-else variant="outline">Not Installed</Badge>
                        </TableCell>
                        <TableCell>
                            <RadioGroupItem :value="server.id" />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="ghost" size="icon" as-child title="Details">
                                    <Link :href="ServerDetailController.index.url({ server: server.id })">
                                        <Info class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" as-child title="Files">
                                    <Link :href="ServerFilesController.index.url({ server: server.id })">
                                        <FolderOpen class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" as-child title="Processes">
                                    <Link :href="ServerProcessController.index.url({ server: server.id })">
                                        <Activity class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" @click="replicateServer(server)" title="Replicate">
                                    <Copy class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="GameServerController.edit.url({ server: server.id })">
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
                                            <AlertDialogTitle>Delete server?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will remove "{{ server.name }}" from the application. This action cannot be undone.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="deleteServer(server)">
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
</template>
