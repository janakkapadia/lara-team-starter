<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm, Head, router, usePage } from '@inertiajs/vue3';
import type { Team, TeamMember, BreadcrumbItem, TeamInvitation, SharedData, User } from '@/types';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { computed } from 'vue';

interface Props {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Team Settings',
        href: route('teams.edit', { team: props.team.id }),
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const isAdmin = computed(() => props.team.user_id === user.id || props.members.some((member) => member.user.id === user.id && member.role === 'admin'));

const updateTeamForm = useForm({
    name: props.team.name,
    photo: null as File | null,
});

const inviteMemberForm = useForm({
    email: '',
    role: 'member',
});

const updateTeam = () => {
    updateTeamForm.put(route('teams.update', { team: props.team.id }), {
        preserveScroll: true,
    });
};

const inviteMember = () => {
    inviteMemberForm.post(route('team-members.invite', { team: props.team.id }), {
        preserveScroll: true,
        onSuccess: () => inviteMemberForm.reset(),
    });
};

const cancelInvitation = (invitationId: number) => {
    router.delete(route('team-members.cancel-invitation', {
        team: props.team.id,
        invitation: invitationId
    }));
};

const leaveTeam = (memberId: number) => {
    router.delete(route('team-members.remove', {
        team: props.team.id,
        user: memberId
    }));
};

const leaveCurrentTeam = () => {
    router.delete(route('team-members.remove', {
        team: props.team.id,
        user: props.team.user_id
    }));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Team Settings" />
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Team Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Team Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Team Information</CardTitle>
                        <CardDescription>Update your team's name and photo.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="updateTeam" class="space-y-4">
                            <div>
                                <Label for="name">Team Name</Label>
                                <Input id="name" v-model="updateTeamForm.name" type="text" />
                            </div>
                            <div>
                                <Label for="photo">Team Photo</Label>
                                <Input
                                    id="photo"
                                    type="file"
                                    @input="(e: Event) => updateTeamForm.photo = (e.target as HTMLInputElement).files?.[0] ?? null"
                                    accept="image/*"
                                />
                            </div>
                            <Button type="submit" :disabled="updateTeamForm.processing" v-if="isAdmin">
                                Save Changes
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Invite New Members -->
                <Card v-if="isAdmin">
                    <CardHeader>
                        <CardTitle>Invite New Members</CardTitle>
                        <CardDescription>Invite new members to your team.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="inviteMember" class="space-y-4">
                            <div>
                                <Label for="email">Email Address</Label>
                                <Input id="email" v-model="inviteMemberForm.email" type="email" />
                                <InputError :message="inviteMemberForm.errors.email" />
                            </div>
                            <div>
                                <Label for="role">Role</Label>
                                <Select v-model="inviteMemberForm.role">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="member">Member</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" :disabled="inviteMemberForm.processing">
                                Invite Member
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Current Team Members -->
                <Card>
                    <CardHeader>
                        <CardTitle>Current Team Members</CardTitle>
                        <CardDescription>View and manage your current team members.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="members?.length > 0" class="space-y-4">
                            <div class="divide-y">
                                <div v-for="member in members" :key="member.id" class="flex items-center justify-between py-4">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <div class="font-medium">{{ member.user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ member.user.email }}</div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full capitalize"
                                            :class="{
                                                'bg-blue-100 text-blue-700': member.role === 'admin',
                                                'bg-gray-100 text-gray-700': member.role === 'member'
                                            }"
                                        >
                                            {{ member.role }}
                                        </span>
                                    </div>
                                    <div class="flex gap-2">
                                        <!-- Show Remove Member button only for admins and only for non-admin members -->
                                        <Button
                                            v-if="isAdmin"
                                            variant="ghost"
                                            size="sm"
                                            @click="leaveTeam(member.user.id)"
                                            class="text-red-500 hover:text-red-600 hover:bg-red-50"
                                        >
                                            Remove Member
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500">
                            No team members yet.
                        </div>
                    </CardContent>
                </Card>

                <!-- Pending Invitations -->
                <Card v-if="isAdmin">
                    <CardHeader>
                        <CardTitle>Pending Invitations</CardTitle>
                        <CardDescription>Manage your pending team invitations.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="invitations?.length > 0" class="space-y-4">
                            <div class="divide-y">
                                <div v-for="invitation in invitations" :key="invitation.id" class="flex items-center justify-between py-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <div class="font-medium">{{ invitation.email }}</div>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full capitalize"
                                                :class="{
                                                    'bg-blue-100 text-blue-700': invitation.role === 'admin',
                                                    'bg-gray-100 text-gray-700': invitation.role === 'member'
                                                }"
                                            >
                                                {{ invitation.role }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            Invited {{ new Date(invitation.created_at).toLocaleDateString() }}
                                        </div>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="cancelInvitation(invitation.id)"
                                        class="text-red-500 hover:text-red-600 hover:bg-red-50"
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500">
                            No pending invitations.
                        </div>
                    </CardContent>
                </Card>

                <!-- Leave Team Section -->
                <Card v-if="user.id !== team.user_id">
                    <CardHeader>
                        <CardTitle>Leave Team</CardTitle>
                        <CardDescription>Leave this team. This action cannot be undone.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">
                                    You will no longer have access to this team's resources and data.
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                @click="leaveCurrentTeam"
                            >
                                Leave Team
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
