<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
} from '@/components/ui/alert-dialog';
import { Card, CardContent } from '@/components/ui/card';
import IntegrationController from '@/actions/App/Http/Controllers/IntegrationController';
import { Plus, Trash2, Pencil } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrations', href: IntegrationController.index.url() },
        ],
    },
});

interface Integration {
    id: string;
    provider: string;
    label: string | null;
    is_active: boolean;
    has_key: boolean;
    created_at: string;
}

const props = defineProps<{
    integrations: Integration[];
    providers: Record<string, string>;
}>();

const confirmDeleteId = ref<string | null>(null);

function toggleActive(integration: Integration) {
    router.put(IntegrationController.update.url({ integration: integration.id }), {
        provider: integration.provider,
        label: integration.label ?? '',
        api_key: '',
        is_active: !integration.is_active,
    }, {
        preserveScroll: true,
    });
}

function performDelete() {
    if (!confirmDeleteId.value) return;
    router.delete(IntegrationController.destroy.url({ integration: confirmDeleteId.value }), {
        preserveScroll: true,
    });
    confirmDeleteId.value = null;
}

function providerLabel(key: string): string {
    return props.providers[key] ?? key;
}
</script>

<template>
    <Head title="Integrations" />

    <div class="flex h-full flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Integrations" description="Manage API keys for external services. These are shared across all servers." />
            <Link :href="IntegrationController.create.url()">
                <Button size="sm" class="cursor-pointer">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Integration
                </Button>
            </Link>
        </div>

        <!-- Integrations List -->
        <Card v-if="integrations.length > 0" class="py-0">
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Provider</TableHead>
                            <TableHead>Label</TableHead>
                            <TableHead class="w-20 text-center">Active</TableHead>
                            <TableHead>Added</TableHead>
                            <TableHead class="w-24"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="integration in integrations" :key="integration.id">
                            <TableCell class="font-medium">{{ providerLabel(integration.provider) }}</TableCell>
                            <TableCell class="text-muted-foreground">{{ integration.label || '—' }}</TableCell>
                            <TableCell class="text-center">
                                <Switch
                                    :model-value="integration.is_active"
                                    @update:model-value="toggleActive(integration)"
                                />
                            </TableCell>
                            <TableCell class="text-muted-foreground text-sm">{{ integration.created_at }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-1">
                                    <Link :href="IntegrationController.edit.url({ integration: integration.id })">
                                        <Button variant="ghost" size="sm" class="h-7 w-7 p-0 cursor-pointer">
                                            <Pencil class="h-3.5 w-3.5" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" class="h-7 w-7 p-0 text-destructive cursor-pointer" @click="confirmDeleteId = integration.id">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Empty state -->
        <div v-if="integrations.length === 0" class="flex flex-1 items-center justify-center rounded-lg border border-dashed p-8">
            <div class="text-center">
                <Plus class="mx-auto h-10 w-10 text-muted-foreground/50" />
                <h3 class="mt-4 text-lg font-semibold">No integrations configured</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Add API keys for CurseForge, OpenAI, Claude, or other services.
                </p>
                <Link :href="IntegrationController.create.url()">
                    <Button class="mt-4 cursor-pointer" size="sm">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Integration
                    </Button>
                </Link>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <AlertDialog :open="confirmDeleteId !== null" @update:open="confirmDeleteId = null">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete Integration?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will permanently remove this integration and its API key. This action cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="performDelete">Delete</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
