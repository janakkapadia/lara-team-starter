<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar" class="h-screen">
        <AppSidebar />
        <AppContent variant="sidebar" class="flex h-full flex-col overflow-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="flex-1 overflow-y-auto">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
