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

export interface ClassType {
    name: string;
    description?: string;
    image: StaticImageData;
}
