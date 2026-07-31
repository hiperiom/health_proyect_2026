export type PermissionOption = {
    id: number;
    name: string;
    slug: string;
    module: string;
    description: string | null;
};

export type RoleOption = {
    id: number;
    value: number;
    label: string;
    slug: string;
    color_class?: string | null;
    text_class?: string | null;
    icon_svg?: string | null;
};

export type UserRole = {
    slug: string;
    name: string;
    color_class?: string | null;
    text_class?: string | null;
    icon_svg?: string | null;
};

export type UserModel = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    roleName?: string | null;
    roles?: UserRole[];
    role_ids?: number[];
    permission_ids?: number[];
    permissions?: PermissionOption[];
    passwordUpdated?: boolean;
    createdAt: string;
    updatedAt: string;
};
