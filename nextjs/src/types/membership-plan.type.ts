export interface MembershipPlanType {
    id: number;
    name: string;
    durationDays: number;
    sessionLimit: number | null;
    price: string;
}