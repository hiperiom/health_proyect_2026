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
};