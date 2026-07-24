export type PatientStatusOption = {
    value: string;
    label: string;
    colorClass: string;
};

export type PatientNacionalityOption = {
    value: string;
    label: string;
};

export type PatientGenderOption = {
    value: string;
    label: string;
};

export type Patient = {
    id: number;
    photoUrl: string | null;
    firstName: string;
    lastName: string;
    fullName: string;
    nacionality: string;
    nacionalityLabel?: string | null;
    dni: string;
    birthDate: string;
    gender: string;
    genderLabel?: string | null;
    phoneMobile: string;
    phoneLandline: string | null;
    email: string;
    createdByUserId: number;
    createdByName?: string | null;
    status: string;
    statusLabel?: string | null;
    statusColorClass?: string | null;
    createdAt: string;
    updatedAt: string;
};
