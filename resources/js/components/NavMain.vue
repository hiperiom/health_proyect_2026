<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();

/**
 * Current request URL (e.g. "/users" or "/users?page=2").
 * Inertia always provides this; falling back to window.location covers
 * the rare SSR/hard-load edge case.
 */
const currentUrl = computed<string>(() => {
    const url = (page.url as string | undefined) ?? '';
    if (url) {
        return url;
    }
    return typeof window !== 'undefined' ? window.location.pathname + window.location.search : '';
});

/**
 * Normalise an href for comparison:
 *   - treat null/undefined as inactive
 *   - strip the query string and trailing slash
 *   - prepend "/" when the href is relative (e.g. "users" → "/users")
 */
const normalizeHref = (href?: string | null): string => {
    if (!href) {
        return '';
    }

    let path = href.split('?')[0] ?? '';
    if (path !== '/' && path.endsWith('/')) {
        path = path.slice(0, -1);
    }
    if (!path.startsWith('/')) {
        path = '/' + path;
    }

    return path;
};

const currentPath = computed<string>(() => {
    const url = currentUrl.value;
    const path = url.split('?')[0] ?? '';
    if (path !== '/' && path.endsWith('/')) {
        return path.slice(0, -1);
    }
    return path;
});

/**
 * Exact match: a top-level item is active when its href equals the
 * current path. For "Dashboard"-style root items we compare against "/".
 */
const isItemActive = (item: NavItem): boolean => {
    const href = normalizeHref(item.href);
    if (!href) {
        return false;
    }

    return currentPath.value === href;
};

/**
 * Sub-items inherit activeness from their parent — if any of the
 * children is active the group should also be highlighted.
 */
const isSubItemActive = (subHref?: string | null): boolean => {
    const href = normalizeHref(subHref);
    if (!href) {
        return false;
    }
    return currentPath.value === href;
};

const hasActiveSubItem = (item: NavItem): boolean => {
    if (!item.items || item.items.length === 0) {
        return false;
    }
    return item.items.some((sub) => isSubItemActive(sub.href));
};

const isParentActive = (item: NavItem): boolean =>
    isItemActive(item) || hasActiveSubItem(item);
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>Plataforma</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Si el elemento tiene subítems, renderizamos un Collapsible -->
                <Collapsible
                    v-if="item.items && item.items.length > 0"
                    as-child
                    :default-open="hasActiveSubItem(item)"
                    :open="hasActiveSubItem(item)"
                    class="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :tooltip="item.title"
                                :is-active="isParentActive(item)"
                            >
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="subItem in item.items"
                                    :key="subItem.title"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="isSubItemActive(subItem.href)"
                                    >
                                        <Link :href="subItem.href">
                                            <component :is="subItem.icon" v-if="subItem.icon" />
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Si es un elemento sencillo de nivel superior -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton
                        as-child
                        :tooltip="item.title"
                        :is-active="isItemActive(item)"
                    >
                        <Link :href="item.href!">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
