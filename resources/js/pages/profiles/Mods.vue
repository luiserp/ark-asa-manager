<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import ProfileModsController from '@/actions/App/Http/Controllers/ProfileModsController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { index as profilesIndex } from '@/routes/profiles';
import { Save, Loader2, Plus, Trash2, GripVertical, ArrowUp, ArrowDown, ExternalLink, HelpCircle, Search, Download, ChevronLeft, ChevronRight, ImageIcon, ArrowUpDown, ChevronsUpDown } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profiles', href: profilesIndex() },
            { title: 'Mods', href: '#' },
        ],
    },
});

interface Mod {
    mod_id: string;
    name: string;
    is_passive: boolean;
    is_enabled: boolean;
}

interface CurseForgeMod {
    id: number;
    name: string;
    slug: string;
    summary: string;
    downloadCount: number;
    thumbUrl: string | null;
    logoUrl: string | null;
    screenshots: { id: number; title: string; url: string; thumbnailUrl: string }[];
    authors: string[];
    dateReleased: string | null;
    links: { websiteUrl: string | null };
}

const props = defineProps<{
    profile: { id: string; name: string };
    server: { id: string; name: string };
    mods: Mod[];
    curseforgeUrl: string;
    hasCurseforgeIntegration: boolean;
}>();

const page = usePage();
const mods = ref<Mod[]>(
    JSON.parse(JSON.stringify(props.mods)).map((m: any) => ({
        ...m,
        is_enabled: m.is_enabled ?? true,
    })),
);
const saving = ref(false);
const dirty = ref(false);

const newModId = ref('');
const confirmSave = ref(false);
const confirmRemoveIndex = ref<number | null>(null);

const filterQuery = ref('');
const sortField = ref<'order' | 'name' | 'id'>('order');
const sortDir = ref<'asc' | 'desc'>('asc');
const selectedMods = ref<string[]>([]);

const searchQuery = ref('');
const searchResults = ref<CurseForgeMod[]>([]);
const searching = ref(false);
const searchPage = ref(0);
const searchTotal = ref(0);
const hasSearched = ref(false);

const modDetails = ref<Record<string, CurseForgeMod>>({});
const loadingDetails = ref(false);

const galleryMod = ref<CurseForgeMod | null>(null);
const galleryOpen = ref(false);

const csrfToken = (page.props as any)._token
    ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    ?? '';

const activeModIds = computed(() =>
    mods.value.filter(m => m.is_enabled && !m.is_passive).map(m => m.mod_id).join(','),
);

const passiveModIds = computed(() =>
    mods.value.filter(m => m.is_enabled && m.is_passive).map(m => m.mod_id).join(','),
);

const totalPages = computed(() => Math.ceil(searchTotal.value / 12));

const filteredMods = computed(() => {
    const q = filterQuery.value.trim().toLowerCase();
    let result = mods.value;

    if (q) {
        result = result.filter(m => {
            const name = (modDetails.value[m.mod_id]?.name || m.name).toLowerCase();
            return name.includes(q) || m.mod_id.includes(q);
        });
    }

    if (sortField.value !== 'order') {
        result = [...result].sort((a, b) => {
            let cmp = 0;
            if (sortField.value === 'name') {
                const nameA = (modDetails.value[a.mod_id]?.name || a.name).toLowerCase();
                const nameB = (modDetails.value[b.mod_id]?.name || b.name).toLowerCase();
                cmp = nameA.localeCompare(nameB);
            } else if (sortField.value === 'id') {
                cmp = a.mod_id.localeCompare(b.mod_id, undefined, { numeric: true });
            }
            return sortDir.value === 'desc' ? -cmp : cmp;
        });
    }

    return result;
});

const allSelected = computed(() =>
    filteredMods.value.length > 0 && filteredMods.value.every(m => selectedMods.value.includes(m.mod_id)),
);

function toggleSort(field: 'name' | 'id') {
    if (sortField.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDir.value = 'asc';
    }
}

function resetSort() {
    sortField.value = 'order';
    sortDir.value = 'asc';
}

function toggleSelectAll(checked: boolean | 'indeterminate') {
    if (checked === true) {
        selectedMods.value = filteredMods.value.map(m => m.mod_id);
    } else {
        selectedMods.value = [];
    }
}

function toggleSelect(modId: string, checked: boolean | 'indeterminate') {
    if (checked === true) {
        if (!selectedMods.value.includes(modId)) {
            selectedMods.value = [...selectedMods.value, modId];
        }
    } else {
        selectedMods.value = selectedMods.value.filter(id => id !== modId);
    }
}

function bulkDelete() {
    mods.value = mods.value.filter(m => !selectedMods.value.includes(m.mod_id));
    selectedMods.value = [];
    dirty.value = true;
}

function bulkDisable() {
    mods.value.forEach(m => {
        if (selectedMods.value.includes(m.mod_id)) m.is_enabled = false;
    });
    selectedMods.value = [];
    dirty.value = true;
}

function bulkEnable() {
    mods.value.forEach(m => {
        if (selectedMods.value.includes(m.mod_id)) m.is_enabled = true;
    });
    selectedMods.value = [];
    dirty.value = true;
}

function bulkPassive() {
    mods.value.forEach(m => {
        if (selectedMods.value.includes(m.mod_id)) m.is_passive = true;
    });
    selectedMods.value = [];
    dirty.value = true;
}

function bulkActive() {
    mods.value.forEach(m => {
        if (selectedMods.value.includes(m.mod_id)) m.is_passive = false;
    });
    selectedMods.value = [];
    dirty.value = true;
}

function isInstalled(modId: number): boolean {
    return mods.value.some(m => m.mod_id === String(modId));
}

function openGallery(modId: string) {
    const details = modDetails.value[modId];
    if (details) {
        galleryMod.value = details;
        galleryOpen.value = true;
    }
}

function addMod() {
    const raw = newModId.value.trim();

    if (!raw) {
        toast.error('Mod ID is required.');
        return;
    }

    const ids = raw.split(',').map(s => s.trim()).filter(Boolean);
    let added = 0;

    for (const id of ids) {
        if (mods.value.some(m => m.mod_id === id)) {
            if (ids.length === 1) {
                toast.error('This mod ID already exists in the list.');
            }
            continue;
        }

        mods.value.push({
            mod_id: id,
            name: `Mod ${id}`,
            is_passive: false,
            is_enabled: true,
        });
        added++;
    }

    if (added > 0) {
        newModId.value = '';
        dirty.value = true;
        if (added > 1) {
            toast.success(`${added} mods added to the list.`);
        }
    }
}

function installFromCurseForge(mod: CurseForgeMod) {
    const id = String(mod.id);

    if (mods.value.some(m => m.mod_id === id)) {
        toast.info(`${mod.name} is already installed.`);
        return;
    }

    mods.value.push({
        mod_id: id,
        name: mod.name,
        is_passive: false,
        is_enabled: true,
    });

    modDetails.value[id] = mod;
    dirty.value = true;
    toast.success(`${mod.name} added to mod list.`);
}

function removeMod(index: number) {
    mods.value.splice(index, 1);
    dirty.value = true;
    confirmRemoveIndex.value = null;
}

function moveUp(index: number) {
    if (index <= 0) return;
    const item = mods.value.splice(index, 1)[0];
    mods.value.splice(index - 1, 0, item);
    dirty.value = true;
}

function moveDown(index: number) {
    if (index >= mods.value.length - 1) return;
    const item = mods.value.splice(index, 1)[0];
    mods.value.splice(index + 1, 0, item);
    dirty.value = true;
}

function togglePassive(index: number) {
    mods.value[index].is_passive = !mods.value[index].is_passive;
    dirty.value = true;
}

function toggleEnabled(index: number) {
    mods.value[index].is_enabled = !mods.value[index].is_enabled;
    dirty.value = true;
}

async function searchCurseForge(page: number = 0) {
    searching.value = true;
    searchPage.value = page;
    hasSearched.value = true;

    try {
        const url = ProfileModsController.search.url({ profile: props.profile.id });
        const params = new URLSearchParams();
        if (searchQuery.value.trim()) {
            params.set('q', searchQuery.value.trim());
        }
        if (page > 0) {
            params.set('page', String(page));
        }

        const res = await fetch(`${url}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await res.json();

        if (!res.ok) {
            toast.error(data.error || 'Failed to search CurseForge');
            return;
        }

        searchResults.value = data.data ?? [];
        searchTotal.value = data.pagination?.totalCount ?? 0;
    } catch (e: any) {
        toast.error(e.message || 'Network error');
    } finally {
        searching.value = false;
    }
}

function formatDownloads(count: number): string {
    if (count >= 1_000_000) return `${(count / 1_000_000).toFixed(1)}M`;
    if (count >= 1_000) return `${(count / 1_000).toFixed(1)}K`;
    return String(count);
}

async function fetchModDetails() {
    if (!props.hasCurseforgeIntegration || mods.value.length === 0) return;

    loadingDetails.value = true;
    try {
        const url = ProfileModsController.details.url({ profile: props.profile.id });
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const data = await res.json();
        if (data.details) {
            modDetails.value = data.details;
        }
    } catch {
        // Silently fail - the table still works without CurseForge data
    } finally {
        loadingDetails.value = false;
    }
}

async function saveMods() {
    saving.value = true;
    try {
        const url = ProfileModsController.store.url({ profile: props.profile.id });
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ mods: mods.value }),
        });
        const data = await res.json();
        if (!res.ok) {
            toast.error(data.message || data.error || 'Failed to save mods');
        } else {
            dirty.value = false;
            toast.success('Mod list saved to profile, don\'t forget to restart the server.');
        }
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        saving.value = false;
        confirmSave.value = false;
    }
}

watch(() => props.profile.id, () => {
    mods.value = JSON.parse(JSON.stringify(props.mods)).map((m: any) => ({
        ...m,
        is_enabled: m.is_enabled ?? true,
    }));
    dirty.value = false;
    fetchModDetails();
});

onMounted(() => {
    fetchModDetails();
});
</script>

<template>
    <Head :title="`Mods - ${profile.name}`" />

    <div class="flex h-full flex-col gap-4 overflow-hidden p-4">
        <div class="flex items-center justify-between">
            <Heading :title="`Mods: ${profile.name}`" description="Manage CurseForge mods for this server profile. Mods are loaded in list order on server start.">
                <template #tooltip>
                    <TooltipProvider :delay-duration="300">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <HelpCircle class="h-4 w-4 text-muted-foreground cursor-help" />
                            </TooltipTrigger>
                            <TooltipContent class="max-w-sm text-left" side="bottom">
                                <p class="font-semibold mb-1">Mod Manager</p>
                                <p class="mb-1">Search and install mods from CurseForge. Drag to reorder — mods load in list order on server start. Toggle mods on/off without removing them.</p>
                                <p>Mods are added to the launch command via <code class="rounded bg-muted px-1 text-xs">-mods=</code> flags. The server downloads them automatically on start.</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </template>
            </Heading>
            <div class="flex items-center gap-2">
                <Badge v-if="dirty" variant="destructive">Unsaved</Badge>
                <TooltipProvider :delay-duration="500">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button size="sm" class="cursor-pointer" :disabled="saving || !dirty" @click="confirmSave = true">
                                <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                Save
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Save the mod list to this profile</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </div>

        <Card>
            <CardHeader class="flex flex-row items-start justify-between">
                <div>
                    <CardTitle>Add Mod</CardTitle>
                    <CardDescription>Enter one or more CurseForge mod IDs separated by commas.</CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                <form class="flex items-end gap-3" @submit.prevent="addMod">
                    <div class="flex flex-1 flex-col gap-1.5">
                        <Label for="mod-id">Mod IDs</Label>
                        <Input
                            id="mod-id"
                            v-model="newModId"
                            placeholder="e.g. 928793, 899987, 123456"
                        />
                    </div>
                    <Button type="submit" variant="outline" size="sm" class="cursor-pointer">
                        <Plus class="mr-2 h-4 w-4" />
                        Add
                    </Button>
                </form>
            </CardContent>
        </Card>

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- Installed Mods (Left) -->
            <Card v-if="mods.length > 0" class="flex min-h-0 flex-col overflow-hidden">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Mod List ({{ mods.length }})</CardTitle>
                            <CardDescription>
                                Mods load in this order. The <code>-mods</code> flag is built automatically on server start.
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <div v-if="loadingDetails" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                Loading...
                            </div>
                            <Input
                                v-model="filterQuery"
                                placeholder="Filter by name or ID..."
                                class="h-8 w-48 text-xs"
                            />
                        </div>
                    </div>
                </CardHeader>
                <!-- Bulk Actions Bar -->
                <div v-if="selectedMods.length > 0" class="flex items-center gap-2 border-b px-4 py-2">
                    <span class="text-xs text-muted-foreground">{{ selectedMods.length }} selected</span>
                    <Button variant="outline" size="sm" class="h-6 text-xs cursor-pointer" @click="bulkEnable">Enable</Button>
                    <Button variant="outline" size="sm" class="h-6 text-xs cursor-pointer" @click="bulkDisable">Disable</Button>
                    <Button variant="outline" size="sm" class="h-6 text-xs cursor-pointer" @click="bulkActive">Set Active</Button>
                    <Button variant="outline" size="sm" class="h-6 text-xs cursor-pointer" @click="bulkPassive">Set Passive</Button>
                    <Button variant="destructive" size="sm" class="h-6 text-xs cursor-pointer" @click="bulkDelete">Delete</Button>
                </div>

                <CardContent class="terminal-scroll min-h-0 flex-1 overflow-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-10">
                                    <Checkbox :model-value="allSelected" @update:model-value="(val: boolean | 'indeterminate') => toggleSelectAll(val)" />
                                </TableHead>
                                <TableHead class="w-10">
                                    <button class="cursor-pointer inline-flex items-center gap-0.5 hover:text-foreground" @click="resetSort" title="Reset to load order">
                                        #
                                        <ArrowUpDown v-if="sortField === 'order'" class="h-3 w-3 opacity-40" />
                                    </button>
                                </TableHead>
                                <TableHead class="w-14"></TableHead>
                                <TableHead>
                                    <div class="flex items-center gap-3">
                                        <button class="cursor-pointer inline-flex items-center gap-1 hover:text-foreground" @click="toggleSort('name')">
                                            Name
                                            <ChevronsUpDown v-if="sortField !== 'name'" class="h-3 w-3 opacity-40" />
                                            <ArrowUp v-else-if="sortDir === 'asc'" class="h-3 w-3" />
                                            <ArrowDown v-else class="h-3 w-3" />
                                        </button>
                                        <button class="cursor-pointer inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground" @click="toggleSort('id')">
                                            ID
                                            <ChevronsUpDown v-if="sortField !== 'id'" class="h-3 w-3 opacity-40" />
                                            <ArrowUp v-else-if="sortDir === 'asc'" class="h-3 w-3" />
                                            <ArrowDown v-else class="h-3 w-3" />
                                        </button>
                                    </div>
                                </TableHead>
                                <TableHead class="w-24 text-center">Enabled</TableHead>
                                <TableHead class="w-24 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        Passive
                                        <TooltipProvider :delay-duration="300">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <HelpCircle class="h-3.5 w-3.5 text-muted-foreground cursor-help" />
                                                </TooltipTrigger>
                                                <TooltipContent class="max-w-sm text-left" side="top">
                                                    <p class="font-semibold mb-1">What is a passive mod?</p>
                                                    <p class="mb-1">A passive mod (loaded via <code>-passivemods=</code>) loads the mod's data files (assets, meshes, textures) into memory but does not activate its gameplay functionality.</p>
                                                    <p class="mb-1">The main use case is shared asset mods — mods that only provide resources that other active mods depend on.</p>
                                                    <p>In contrast, a regular active mod (<code>-mods=</code>) both loads its data and runs its gameplay code.</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </TableHead>
                                <TableHead class="w-24 text-center">Order</TableHead>
                                <TableHead class="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="mod in filteredMods"
                                :key="mod.mod_id"
                                :class="{ 'opacity-50': !mod.is_enabled }"
                            >
                                <TableCell>
                                    <Checkbox :model-value="selectedMods.includes(mod.mod_id)" @update:model-value="(val: boolean | 'indeterminate') => toggleSelect(mod.mod_id, val)" />
                                </TableCell>
                                <TableCell class="text-muted-foreground text-xs">{{ mods.indexOf(mod) + 1 }}</TableCell>
                                <TableCell>
                                    <button
                                        v-if="modDetails[mod.mod_id]?.thumbUrl"
                                        class="cursor-pointer rounded-md overflow-hidden focus:outline-none focus:ring-2 focus:ring-ring"
                                        @click="openGallery(mod.mod_id)"
                                    >
                                        <img
                                            :src="modDetails[mod.mod_id].thumbUrl!"
                                            :alt="mod.name"
                                            class="h-10 w-10 rounded-md object-cover"
                                        />
                                    </button>
                                    <div v-else class="h-10 w-10 rounded-md bg-muted flex items-center justify-center">
                                        <ImageIcon class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <button
                                                class="text-sm font-medium truncate text-left cursor-pointer hover:underline"
                                                @click="openGallery(mod.mod_id)"
                                            >{{ modDetails[mod.mod_id]?.name || mod.name }}</button>
                                            <span class="text-xs text-muted-foreground font-mono flex-shrink-0">{{ mod.mod_id }}</span>
                                            <a
                                                v-if="modDetails[mod.mod_id]?.links?.websiteUrl"
                                                :href="modDetails[mod.mod_id].links.websiteUrl!"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex-shrink-0 text-muted-foreground hover:text-foreground transition-colors"
                                            >
                                                <ExternalLink class="h-3.5 w-3.5" />
                                            </a>
                                        </div>
                                        <p v-if="modDetails[mod.mod_id]?.summary" class="mt-0.5 text-xs text-muted-foreground truncate max-w-xs">
                                            {{ modDetails[mod.mod_id].summary }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Switch
                                        :model-value="mod.is_enabled"
                                        @update:model-value="toggleEnabled(mods.indexOf(mod))"
                                    />
                                </TableCell>
                                <TableCell class="text-center">
                                    <Switch
                                        :model-value="mod.is_passive"
                                        :disabled="!mod.is_enabled"
                                        @update:model-value="togglePassive(mods.indexOf(mod))"
                                    />
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 cursor-pointer"
                                            :disabled="mods.indexOf(mod) === 0"
                                            @click="moveUp(mods.indexOf(mod))"
                                        >
                                            <ArrowUp class="h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 cursor-pointer"
                                            :disabled="mods.indexOf(mod) === mods.length - 1"
                                            @click="moveDown(mods.indexOf(mod))"
                                        >
                                            <ArrowDown class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-7 w-7 p-0 text-destructive cursor-pointer"
                                        @click="confirmRemoveIndex = mods.indexOf(mod)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div v-else class="flex items-center justify-center rounded-lg border border-dashed p-8">
                <div class="text-center">
                    <GripVertical class="mx-auto h-10 w-10 text-muted-foreground/50" />
                    <h3 class="mt-4 text-lg font-semibold">No mods configured</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Add mods manually above or search CurseForge to get started.
                    </p>
                </div>
            </div>

            <!-- CurseForge Search (Right) -->
            <Card v-if="hasCurseforgeIntegration" class="flex min-h-0 flex-col overflow-hidden">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>CurseForge Search</CardTitle>
                            <CardDescription>Search and install mods directly from CurseForge.</CardDescription>
                        </div>
                        <a :href="curseforgeUrl" target="_blank" rel="noopener noreferrer">
                            <Button variant="outline" size="sm" class="cursor-pointer">
                                <ExternalLink class="mr-1.5 h-3.5 w-3.5" />
                                Browse
                            </Button>
                        </a>
                    </div>
                    <form class="mt-3 flex gap-2" @submit.prevent="searchCurseForge(0)">
                        <Input
                            v-model="searchQuery"
                            placeholder="Search mods..."
                            class="flex-1"
                        />
                        <Button type="submit" size="sm" :disabled="searching" class="cursor-pointer">
                            <Loader2 v-if="searching" class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                            <Search v-else class="mr-1.5 h-3.5 w-3.5" />
                            Search
                        </Button>
                    </form>
                </CardHeader>
                <CardContent class="terminal-scroll min-h-0 flex-1 overflow-auto">
                    <div v-if="searching && !searchResults.length" class="flex items-center justify-center py-12">
                        <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>

                    <div v-else-if="searchResults.length > 0" class="space-y-3">
                        <div
                            v-for="mod in searchResults"
                            :key="mod.id"
                            class="flex items-start gap-3 rounded-md border p-3 transition-colors hover:bg-muted/50"
                        >
                            <img
                                v-if="mod.thumbUrl"
                                :src="mod.thumbUrl"
                                :alt="mod.name"
                                class="h-12 w-12 rounded-md object-cover flex-shrink-0"
                            />
                            <div v-else class="h-12 w-12 rounded-md bg-muted flex-shrink-0" />

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-medium truncate">{{ mod.name }}</h4>
                                    <a
                                        v-if="mod.links?.websiteUrl"
                                        :href="mod.links.websiteUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex-shrink-0 text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        <ExternalLink class="h-3.5 w-3.5" />
                                    </a>
                                    <Badge v-if="isInstalled(mod.id)" variant="secondary" class="text-xs flex-shrink-0">Installed</Badge>
                                </div>
                                <p class="mt-0.5 text-xs text-muted-foreground line-clamp-1">{{ mod.summary }}</p>
                                <div class="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                                    <span>{{ formatDownloads(mod.downloadCount) }} downloads</span>
                                    <span v-if="mod.authors.length">by {{ mod.authors[0] }}</span>
                                </div>
                            </div>

                            <Button
                                v-if="!isInstalled(mod.id)"
                                variant="outline"
                                size="sm"
                                class="flex-shrink-0 cursor-pointer"
                                @click="installFromCurseForge(mod)"
                            >
                                <Download class="mr-1.5 h-3.5 w-3.5" />
                                Install
                            </Button>
                            <Badge v-else variant="outline" class="flex-shrink-0 text-xs">
                                Added
                            </Badge>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex items-center justify-between pt-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="searchPage === 0 || searching"
                                class="cursor-pointer"
                                @click="searchCurseForge(searchPage - 1)"
                            >
                                <ChevronLeft class="mr-1 h-3.5 w-3.5" />
                                Previous
                            </Button>
                            <span class="text-xs text-muted-foreground">
                                Page {{ searchPage + 1 }} of {{ totalPages }}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="searchPage >= totalPages - 1 || searching"
                                class="cursor-pointer"
                                @click="searchCurseForge(searchPage + 1)"
                            >
                                Next
                                <ChevronRight class="ml-1 h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>

                    <div v-else-if="hasSearched && !searching" class="flex flex-col items-center justify-center py-12 text-center">
                        <Search class="h-8 w-8 text-muted-foreground/50" />
                        <p class="mt-2 text-sm text-muted-foreground">No mods found. Try a different search term.</p>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                        <Search class="h-8 w-8 text-muted-foreground/50" />
                        <p class="mt-2 text-sm text-muted-foreground">Search for ARK mods by name or keyword.</p>
                    </div>
                </CardContent>
            </Card>

            <Card v-else class="flex flex-col items-center justify-center p-8">
                <div class="text-center">
                    <ExternalLink class="mx-auto h-10 w-10 text-muted-foreground/50" />
                    <h3 class="mt-4 text-lg font-semibold">CurseForge Not Connected</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Add a CurseForge API integration in Settings to enable mod browsing and one-click installs.
                    </p>
                </div>
            </Card>
        </div>

        <Card v-if="mods.length > 0" class="flex-shrink-0">
            <CardHeader>
                <CardTitle>Startup Flags Preview</CardTitle>
                <CardDescription>These flags will be appended to the server command line on start.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-2">
                <div v-if="activeModIds" class="rounded-md bg-muted px-3 py-2 font-mono text-sm">
                    -mods={{ activeModIds }}
                </div>
                <div v-if="passiveModIds" class="rounded-md bg-muted px-3 py-2 font-mono text-sm">
                    -passivemods={{ passiveModIds }}
                </div>
                <p v-if="!activeModIds && !passiveModIds" class="text-muted-foreground text-sm">
                    All mods are currently disabled or the list is empty.
                </p>
            </CardContent>
        </Card>
    </div>

    <!-- Screenshot Gallery Modal -->
    <Dialog v-model:open="galleryOpen">
        <DialogContent class="sm:max-w-5xl max-h-[85vh] flex flex-col">
            <DialogHeader>
                <DialogTitle>{{ galleryMod?.name }} - Screenshots</DialogTitle>
                <DialogDescription>{{ galleryMod?.summary }}</DialogDescription>
            </DialogHeader>
            <div v-if="galleryMod?.screenshots?.length" class="terminal-scroll flex-1 overflow-auto rounded-md border bg-black/50 p-4">
                <div class="grid grid-cols-2 gap-4">
                    <img
                        v-for="ss in galleryMod.screenshots"
                        :key="ss.id"
                        :src="ss.url"
                        :alt="ss.title || galleryMod.name"
                        class="w-full rounded-md object-cover"
                    />
                </div>
            </div>
            <div v-else class="flex items-center justify-center py-12 text-muted-foreground">
                <p>No screenshots available.</p>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Save Confirmation -->
    <AlertDialog v-model:open="confirmSave">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Save Mod List?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will save the current mod list ({{ mods.length }} mod{{ mods.length !== 1 ? 's' : '' }}) to this profile. The mods will be loaded next time the server starts with this profile.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="saveMods">Save</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- Remove Confirmation -->
    <AlertDialog :open="confirmRemoveIndex !== null">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Remove Mod?</AlertDialogTitle>
                <AlertDialogDescription>
                    <template v-if="confirmRemoveIndex !== null">
                        Remove <strong>{{ mods[confirmRemoveIndex]?.name }}</strong> ({{ mods[confirmRemoveIndex]?.mod_id }}) from the mod list? You'll need to save afterwards to persist this change.
                    </template>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="confirmRemoveIndex = null">Cancel</AlertDialogCancel>
                <AlertDialogAction v-if="confirmRemoveIndex !== null" @click="removeMod(confirmRemoveIndex)">Remove</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
