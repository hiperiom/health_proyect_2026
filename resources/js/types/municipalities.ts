export type Municipality = {
    id: number;
    name: string;
    description: string | null;
    value: string | null;
    stateId: number | null;
    stateName: string | null;
    isActive: boolean;
    createdAt: string;
    updatedAt: string;
};
