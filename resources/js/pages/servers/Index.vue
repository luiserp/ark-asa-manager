<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import GameServerController from '@/actions/App/Http/Controllers/GameServerController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
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
import { Plus, Pencil, Trash2, Copy } from '@lucide/vue';

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

defineProps<{
    servers: GameServer[];
}>();

function toggleActive(server: GameServer) {
    router.patch(GameServerController.toggleActive.url({ server: server.id }), {}, {
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

        <div v-else class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Host</TableHead>
                        <TableHead>Active</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="server in servers" :key="server.id">
                        <TableCell class="font-medium">
                            {{ server.name }}
                        </TableCell>
                        <TableCell>{{ server.host }}:{{ server.port }}</TableCell>
                        <TableCell>
                            <Switch
                                :model-value="server.is_active"
                                @update:model-value="toggleActive(server)"
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-2">
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
        </div>
    </div>
</template>
