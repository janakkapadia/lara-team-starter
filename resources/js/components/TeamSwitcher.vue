<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger, } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { ChevronsUpDown, Plus, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { getInitials } from '@/composables/useInitials';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { usePage, router } from '@inertiajs/vue3';
import type { SharedData, User, Team } from '@/types';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';

const { isMobile } = useSidebar();
const page = usePage<SharedData>();

const user = computed(() => page.props.auth.user as User);
const allTeams = computed(() => page.props.auth.teams as Team[]);
const ownedTeams = computed(() => allTeams.value.filter(team => team.is_owner));
const memberTeams = computed(() => allTeams.value.filter(team => !team.is_owner));
const activeTeam = computed(() => {
    return allTeams.value.find(team => team.id === user.value.current_team_id) || allTeams.value[0]
});

const isAddTeamModal = ref(false)
const form = useForm({
    name: ''
})

const addTeam = (e: Event) => {
    e.preventDefault();
    form.post(route('teams.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    });
}
const toggleModal = () => {
    isAddTeamModal.value = !isAddTeamModal.value
}
const closeModal = () => {
    form.reset()
    isAddTeamModal.value = false
}
const switchTeam = (team: Team) => {
    form.post(route('teams.switch', { team: team.id }), {
        preserveScroll: true,
    });
}
const navigateToTeamSettings = (team: Team) => {
    router.visit(route('teams.edit', { team: team.id }));
}
</script>
<template>
    <div>
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                            <div
                                class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                            >
                                <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                    <AvatarFallback class="rounded-lg text-black dark:text-white">
                                        {{ getInitials(activeTeam?.name || 'Team') }}
                                    </AvatarFallback>
                                </Avatar>
                                <!--                            <component :is="activeTeam.logo" class="size-4" />-->
                            </div>
                            <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-semibold">
                                {{ activeTeam?.name || 'Select Team' }}
                            </span>
                                <!--                            <span class="truncate text-xs">{{ activeTeam.plan }}</span>-->
                            </div>
                            <ChevronsUpDown class="ml-auto" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                        align="start"
                        :side="isMobile ? 'bottom' : 'right'"
                        :side-offset="4"
                    >
                        <DropdownMenuLabel class="text-xs text-muted-foreground"> Owned Teams </DropdownMenuLabel>
                        <DropdownMenuItem
                            v-for="team in ownedTeams"
                            :key="team.id"
                            :class="[user.current_team_id === team.id ? 'bg-sidebar-accent' : '' ,'gap-2 p-2']"
                            @click="switchTeam(team)">
                            <div class="flex size-6 items-center justify-center rounded-sm border">
                                <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                    <AvatarFallback class="rounded-lg text-black dark:text-white">
                                        {{ getInitials(team.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </div>
                            {{ team.name }}
                            <Settings v-if="activeTeam.id === team.id" class="ml-auto size-4 cursor-pointer" @click.stop="navigateToTeamSettings(team)" />
                        </DropdownMenuItem>
                        <div v-if="memberTeams?.length">
                            <DropdownMenuSeparator />
                            <DropdownMenuLabel class="text-xs text-muted-foreground"> Member of Teams </DropdownMenuLabel>
                            <DropdownMenuItem
                                v-for="team in memberTeams"
                                :key="team.id"
                                :class="[user.current_team_id === team.id ? 'bg-sidebar-accent' : '' ,'gap-2 p-2']"
                                @click="switchTeam(team)">
                                <div class="flex size-6 items-center justify-center rounded-sm border">
                                    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                        <AvatarFallback class="rounded-lg text-black dark:text-white">
                                            {{ getInitials(team.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </div>
                                {{ team.name }}
                                <Settings v-if="activeTeam.id === team.id" class="ml-auto size-4 cursor-pointer" @click.stop="navigateToTeamSettings(team)" />
                            </DropdownMenuItem>
                        </div>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem class="gap-2 p-2" @click="toggleModal">
                            <div class="flex size-6 items-center justify-center rounded-md border bg-background">
                                <Plus class="size-4" />
                            </div>
                            <div class="font-medium text-muted-foreground">Add team</div>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
        <Dialog :open="isAddTeamModal" @update:open="closeModal">
            <DialogContent>
                <form class="space-y-6" @submit="addTeam">
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Create a New Team</DialogTitle>
                        <DialogDescription>
                            Add a new team to collaborate with others. You can invite team members after creating the team.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="team-name">Team Name</Label>
                        <Input id="team-name" type="text" name="team-name" v-model="form.name" placeholder="Enter team name" />
                        <InputError :message="form.errors?.name" />
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="secondary" @click="closeModal">Cancel</Button>
                        </DialogClose>

                        <Button type="submit" :disabled="form.processing">
                            Create Team
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
