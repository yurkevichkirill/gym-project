import type {JSX} from "react";
import {StaticImageData} from "next/image";

export const SelectedPage = {
    Home: "home",
    OurTrainers: "ourtrainers",
    Memberships: "memberships",
    OurClasses: "ourclasses",
    ContactUs: "contactus",
    TrainingTypes: "trainingtypes",
} as const;

export type SelectedPage = (typeof SelectedPage)[keyof typeof SelectedPage];

export interface MembershipType {
    id: number;
    name: string;
    durationDays: number;
    sessionLimit: number | null;
    price: string;
}

export interface ClassType {
    name: string;
    description?: string;
    image: StaticImageData;
}