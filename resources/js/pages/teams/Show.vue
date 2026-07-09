<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import TeamController from '@/actions/App/Http/Controllers/TeamController';
import TeamInvitationController from '@/actions/App/Http/Controllers/TeamInvitationController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { Crown, Loader2, LogOut, Mail, Trash2, UserPlus, X } from '@lucide/vue';
import { ref } from 'vue';

interface TeamDetail {
    id: string;
    name: string;
    personal_team: boolean;
    is_owner: boolean;
    is_creator: boolean;
    owner_id: number;
}

interface Member {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface Invitation {
    id: string;
    email: string;
    role: string;
    created_at: string;
}

const props = defineProps<{
    team: TeamDetail;
    members: Member[];
    invitations: Invitation[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Teams',
                href: TeamController.index.url(),
            },
            {
                title: 'Team Details',
            },
        ],
    },
});

const inviteDialogOpen = ref(false);

const inviteForm = useForm({
    email: '',
    role: 'member',
});

const updateForm = useForm({
    name: props.team.name,
});

function invite() {
    inviteForm.post(TeamInvitationController.store.url({ team: props.team.id }), {
        onSuccess: () => {
            inviteDialogOpen.value = false;
            inviteForm.reset();
        },
    });
}

function updateTeam() {
    updateForm.put(TeamController.update.url({ team: props.team.id }));
}

function updateRole(member: Member, role: string) {
    if (role === member.role) return;
    router.patch(TeamController.updateMemberRole.url({ team: props.team.id, userId: member.id }), {
        role,
    }, { preserveScroll: true });
}

function removeMember(member: Member) {
    router.delete(TeamController.removeMember.url({ team: props.team.id, userId: member.id }));
}

function cancelInvitation(invitation: Invitation) {
    router.delete(TeamInvitationController.destroy.url({ invitation: invitation.id }));
}

function leaveTeam() {
    router.post(TeamController.leave.url({ team: props.team.id }));
}

function deleteTeam() {
    router.delete(TeamController.destroy.url({ team: props.team.id }));
}
</script>

<template>

    <Head :title="team.name" />

    <div class="flex flex-col gap-6 p-4">
        <Heading :title="team.name"
            :description="team.personal_team ? 'Your personal team' : 'Team settings and members'" />

        <Card v-if="team.is_owner && !team.personal_team">
            <CardHeader>
                <CardTitle class="text-base">Team Settings</CardTitle>
                <CardDescription>Update the team name.</CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="updateTeam" class="flex items-end gap-3">
                    <div class="flex-1 space-y-2">
                        <Label for="team-name">Team Name</Label>
                        <Input id="team-name" v-model="updateForm.name"
                            :class="{ 'border-destructive': updateForm.errors.name }" />
                        <p v-if="updateForm.errors.name" class="text-sm text-destructive">
                            {{ updateForm.errors.name }}
                        </p>
                    </div>
                    <Button type="submit" :disabled="updateForm.processing">
                        <Loader2 v-if="updateForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Save
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle class="text-base">Members</CardTitle>
                        <CardDescription>People who have access to this team.</CardDescription>
                    </div>
                    <Dialog v-if="team.is_owner" v-model:open="inviteDialogOpen">
                        <DialogTrigger as-child>
                            <Button size="sm">
                                <UserPlus class="mr-2 h-4 w-4" />
                                Invite
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Invite Member</DialogTitle>
                                <DialogDescription>
                                    Send an invitation to join this team.
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="invite" class="space-y-8">
                                <div class="space-y-2">
                                    <Label for="invite-email">Email Address</Label>
                                    <Input id="invite-email" v-model="inviteForm.email" type="email"
                                        placeholder="colleague@example.com"
                                        :class="{ 'border-destructive': inviteForm.errors.email }" />
                                    <p v-if="inviteForm.errors.email" class="text-sm text-destructive">
                                        {{ inviteForm.errors.email }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="invite-role">Role</Label>
                                    <Select v-model="inviteForm.role">
                                        <SelectTrigger class="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="member">Member</SelectItem>
                                            <SelectItem value="owner">Owner</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <DialogFooter>
                                    <Button type="submit" :disabled="inviteForm.processing">
                                        <Loader2 v-if="inviteForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                        Send Invitation
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead v-if="team.is_owner" class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="member in members" :key="member.id">
                            <TableCell class="font-medium">{{ member.name }}</TableCell>
                            <TableCell>{{ member.email }}</TableCell>
                            <TableCell>
                                <Select
                                    v-if="team.is_owner && member.id !== team.owner_id"
                                    :model-value="member.role"
                                    @update:model-value="updateRole(member, $event as string)"
                                >
                                    <SelectTrigger size="sm" class="w-28">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="member">Member</SelectItem>
                                        <SelectItem value="owner">Owner</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Badge v-else variant="outline" class="capitalize">
                                    <Crown v-if="member.role === 'owner'" class="mr-1 h-3 w-3" />
                                    {{ member.role }}
                                </Badge>
                            </TableCell>
                            <TableCell v-if="team.is_owner" class="text-right">
                                <AlertDialog v-if="member.id !== team.owner_id">
                                    <AlertDialogTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Remove member?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                "{{ member.name }}" will lose access to this team and its servers.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="removeMember(member)">
                                                Remove
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Card v-if="team.is_owner && invitations.length > 0">
            <CardHeader>
                <CardTitle class="text-base">Pending Invitations</CardTitle>
                <CardDescription>Invitations that have not yet been accepted.</CardDescription>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="invitation in invitations" :key="invitation.id">
                            <TableCell class="font-medium">
                                <span class="flex items-center gap-2">
                                    <Mail class="h-3.5 w-3.5 text-muted-foreground" />
                                    {{ invitation.email }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline" class="capitalize">{{ invitation.role }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" @click="cancelInvitation(invitation)">
                                    <X class="h-4 w-4 text-destructive" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-3">
            <AlertDialog v-if="!team.is_creator">
                <AlertDialogTrigger as-child>
                    <Button variant="outline" class="text-destructive">
                        <LogOut class="mr-2 h-4 w-4" />
                        Leave Team
                    </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Leave team?</AlertDialogTitle>
                        <AlertDialogDescription>
                            You will lose access to "{{ team.name }}" and its servers. You can be re-invited later.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="leaveTeam">Leave</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog v-if="team.is_creator && !team.personal_team">
                <AlertDialogTrigger as-child>
                    <Button variant="destructive">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Team
                    </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete team?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete "{{ team.name }}" and all its servers. This action cannot be
                            undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="deleteTeam">Delete</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </div>
</template>
