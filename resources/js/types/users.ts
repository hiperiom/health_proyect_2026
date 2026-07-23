export type RoleOption = {
    value: string;
    label: string;
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
