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

export type UserStatusOption = {
    value: string;
    label: string;
    colorClass: string;
};

export type UserNacionalityOption = {
    value: string;
    label: string;
};

export type UserGenderOption = {
    value: string;
    label: string;
};

export type StateOption = {
    id: number;
    name: string;
};

export type MunicipalityOption = {
    id: number;
    name: string;
    state_id: number;
};

export type UserModel = {
    id: number;
    name: string;
    email: string;
    profileCompletion: number;
    missingFields: string[];
    role: string | null;
    roleName?: string | null;
    roles?: UserRole[];
    role_ids?: number[];
    permission_ids?: number[];
    permissions?: PermissionOption[];
    passwordUpdated?: boolean;
    usersProfileId?: number | null;
    photoUrl?: string | null;
    firstName?: string | null;
    lastName?: string | null;
    nacionality?: string | null;
    dni?: string | null;
    birthDate?: string | null;
    gender?: string | null;
    phoneMobile?: string | null;
    phoneLandline?: string | null;
    stateId?: number | null;
    municipalityId?: number | null;
    address?: string | null;
    status?: string | null;
    statusLabel?: string | null;
    statusColorClass?: string | null;
    createdAt: string;
    updatedAt: string;
};
