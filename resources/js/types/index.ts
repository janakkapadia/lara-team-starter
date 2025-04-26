import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    teams: Team[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
    [key: string]: any;
}

export interface User {
    id: number;
    name: string;
    email: string;
    current_team_id: number;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Team {
    id: number;
    name: string;
    is_owner: boolean;
    user_id: number;
}

export interface TeamMember {
    id: number;
    email: string;
    role: 'admin' | 'member';
    user: User;
}

export interface TeamInvitation {
    id: number;
    email: string;
    role: string;
    created_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
