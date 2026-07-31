export type RoleModuleSummary = {
    id: number;
    name: string;
    display_name: string | null;
    color_class: string | null;
    text_class: string | null;
};

export type RoleModel = {
    id: number;
    name: string;
    slug: string;
    color_class: string | null;
    text_class: string | null;
    icon_svg: string | null;
    module_ids?: number[];
    modules?: RoleModuleSummary[];
    createdAt: string;
    updatedAt: string;
};

export type ModuleWithPermissions = {
    id: number;
    name: string;
    display_name: string | null;
    description: string | null;
    permissions: number[];
};
