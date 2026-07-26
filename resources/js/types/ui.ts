export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type BreadcrumbItem = {
    title: string;
    href: string;
};

export interface NavItem {
    title: string;
    href?: string;
    icon?: any;
    isActive?: boolean;
    items?: NavItem[]; // <- Agrega esta línea si no existía
}
