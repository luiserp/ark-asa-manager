<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import TeamController from '@/actions/App/Http/Controllers/TeamController';
import TeamInvitationController from '@/actions/App/Http/Controllers/TeamInvitationController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Check, Crown, Loader2, LogOut, Pencil, Plus, Trash2, Users, X } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Teams',
                href: TeamController.index.url(),
            },
        ],
    },
});

interface TeamItem {
    id: string;
    name: string;
    personal_team: boolean;
    role: string;
    members_count: number;
    is_owner: boolean;
    is_creator: boolean;
    is_current: boolean;
}

interface PendingInvitation {
    id: string;
    email: string;
    role: string;
    team: {
        id: string;
        name: string;
    };
}

const props = defineProps<{
    teams: TeamItem[];
    pendingInvitations: PendingInvitation[];
}>();

const page = usePage();
const currentTeamId = computed(() => (page.props.currentTeam as { id: string } | null)?.id ?? '');

const createDialogOpen = ref(false);

const createForm = useForm({
    name: '',
});

function createTeam() {
    createForm.post(TeamController.store.url(), {
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
        },
    });
}

function switchTeam(teamId: string) {
    router.post(TeamController.switchTeam.url({ team: teamId }), {}, {
        preserveScroll: true,
    });
}

function leaveTeam(team: TeamItem) {
    router.post(TeamController.leave.url({ team: team.id }));
}

function deleteTeam(team: TeamItem) {
    router.delete(TeamController.destroy.url({ team: team.id }));
}

function acceptInvitation(invitation: PendingInvitation) {
    router.post(TeamInvitationController.accept.url({ invitation: invitation.id }));
}

function declineInvitation(invitation: PendingInvitation) {
    router.post(TeamInvitationController.decline.url({ invitation: invitation.id }));
}
</script>

<template>
    <Head title="Teams" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Teams" description="Manage your teams and collaborations" />

            <Dialog v-model:open="createDialogOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Create Team
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create Team</DialogTitle>
                        <DialogDescription>
                            Create a new team to collaborate with others.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="createTeam" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="team-name">Team Name</Label>
                            <Input
                                id="team-name"
                                v-model="createForm.name"
                                placeholder="My Team"
                                :class="{ 'border-destructive': createForm.errors.name }"
                            />
                            <p v-if="createForm.errors.name" class="text-sm text-destructive">
                                {{ createForm.errors.name }}
                            </p>
                        </div>
                        <DialogFooter>
                            <Button type="submit" :disabled="createForm.processing">
                                <Loader2 v-if="createForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Create
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card v-if="pendingInvitations.length > 0">
            <CardHeader>
                <CardTitle class="text-base">Pending Invitations</CardTitle>
                <CardDescription>You have been invited to join these teams.</CardDescription>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Team</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="invitation in pendingInvitations" :key="invitation.id">
                            <TableCell class="font-medium">{{ invitation.team.name }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" class="capitalize">{{ invitation.role }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" @click="acceptInvitation(invitation)">
                                        <Check class="mr-1 h-3.5 w-3.5" />
                                        Accept
                                    </Button>
                                    <Button size="sm" variant="outline" @click="declineInvitation(invitation)">
                                        <X class="mr-1 h-3.5 w-3.5" />
                                        Decline
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <RadioGroup :model-value="currentTeamId" @update:model-value="switchTeam($event as string)" class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Members</TableHead>
                        <TableHead>Current</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="team in teams" :key="team.id">
                        <TableCell class="font-medium">
                            <Link :href="TeamController.show.url({ team: team.id })" class="hover:underline">
                                <span class="flex items-center gap-2">
                                    {{ team.name }}
                                    <Badge v-if="team.personal_team" variant="secondary" class="text-xs">Personal</Badge>
                                </span>
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Badge variant="outline" class="capitalize">
                                <Crown v-if="team.is_owner" class="mr-1 h-3 w-3" />
                                {{ team.role }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <span class="flex items-center gap-1.5">
                                <Users class="h-3.5 w-3.5 text-muted-foreground" />
                                {{ team.members_count }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <RadioGroupItem :value="team.id" />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="ghost" size="icon" as-child title="Edit">
                                    <Link :href="TeamController.show.url({ team: team.id })">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </Button>

                                <AlertDialog v-if="!team.is_creator">
                                    <AlertDialogTrigger as-child>
                                        <Button variant="ghost" size="icon" title="Leave">
                                            <LogOut class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Leave team?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                You will lose access to "{{ team.name }}" and its servers.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="leaveTeam(team)">Leave</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>

                                <AlertDialog v-if="team.is_creator && !team.personal_team">
                                    <AlertDialogTrigger as-child>
                                        <Button variant="ghost" size="icon" title="Delete">
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Delete team?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will permanently delete "{{ team.name }}" and all its servers. This action cannot be undone.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="deleteTeam(team)">Delete</AlertDialogAction>
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
