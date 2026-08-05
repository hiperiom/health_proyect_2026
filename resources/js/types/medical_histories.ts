export type MedicalHistory = {
    id: number;
    uuid?: string | null;
    name: string;
    description: string | null;
    value: string | null;
    patient_identifier?: string | null;
    mrn?: string | null;
    createdAt: string;
    updatedAt: string;
    patient?: {
        firstName?: string | null;
        lastName?: string | null;
        dni?: string | null;
        email?: string | null;
        photoUrl?: string | null;
    };
};

export type MedicalHistoryTicket = {
    mrn: string;
    firstName: string | null;
    lastName: string | null;
    dni: string | null;
    totalEncounters: number;
};
