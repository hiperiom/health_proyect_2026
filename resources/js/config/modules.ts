import {
    Boxes,
    FileText,
    Key,
    Shield,
    Stethoscope,
    Users,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

/**
 * Display metadata for every CRUD module in the application.
 *
 * The sidebar reads this map at runtime to build its `mainNavItems`
 * list. When `php artisan make:crud` finishes, it appends the new
 * module here so the menu picks it up without any manual editing
 * of `AppSidebar.vue`.
 *
 * Keys must match the module's `name` in the `modules` database
 * table (which is the kebab-case technical name, e.g. `users`,
 * `medical-specialties`).
 *
 * By default every entry is rendered as a top-level menu item. If
 * you want to group a few modules under a collapsible section, use
 * the `group: { title, icon, children }` shape. See the commented
 * example at the bottom of this file for details.
 */
export type ModuleSidebarItem = {
    title: string;
    icon: LucideIcon;
};

export type ModuleSidebarGroup = {
    title: string;
    icon: LucideIcon;
    children: ModuleSidebarItem[];
};

export type ModuleSidebarEntry = ModuleSidebarItem | ModuleSidebarGroup;

export const moduleSidebarConfig: Record<string, ModuleSidebarEntry> = {
    users: { title: 'Usuarios', icon: Key },
    roles: { title: 'Roles', icon: Shield },
    permissions: { title: 'Permisos', icon: FileText },
    modules: { title: 'Modules', icon: Boxes },
    patients: { title: 'Patients', icon: Users },
    'medical-especialties': { title: 'Medical Specialties', icon: Stethoscope },
};

/**
 * Default fallback metadata for any module that does not have an
 * explicit entry in `moduleSidebarConfig`. Used by the sidebar to
 * still render a sensible menu item for newly generated CRUDs
 * (until the user customizes it in `moduleSidebarConfig`).
 */
export const defaultModuleSidebarEntry: ModuleSidebarItem = {
    title: '',
    icon: FileText,
};

/* -----------------------------------------------------------------------
 * Example: how to group modules under collapsible sections.
 *
 * If you want, for example, all clinical modules under a "Clínica"
 * group, replace any module entry above with a `group` shape and list
 * the children inside:
 *
 *   'clinical': {
 *       title: 'Clínica',
 *       icon: Stethoscope,
 *       children: [
 *           { title: 'Patients',         icon: Users },
 *           { title: 'Medical Specialties', icon: Stethoscope },
 *       ],
 *   },
 *
 *   // Standalone modules remain simple:
 *   'users':   { title: 'Usuarios', icon: Key },
 *   'roles':   { title: 'Roles',    icon: Shield },
 *
 *   // A module listed only as a child of a group is *not* rendered
 *   // at the top level, so the group becomes its only entry point.
 *   // To keep a module visible both at the top level and inside a
 *   // group, register the module's key in `moduleSidebarConfig`
 *   // (top-level) and reference its `title` + `icon` from inside
 *   // the group's `children` array.
 * ----------------------------------------------------------------------- */
