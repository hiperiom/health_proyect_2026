export type RoleOption = {
    value: string;
    label: string;
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
    passwordUpdated?: boolean;
    createdAt: string;
    updatedAt: string;
};
