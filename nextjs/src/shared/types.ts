import type {JSX} from "react";
import {StaticImageData} from "next/image";

export const SelectedPage = {
    Home: "home",
    OurTrainers: "ourtrainers",
    Benefits: "benefits",
    OurClasses: "ourclasses",
    ContactUs: "contactus",
} as const;

export type SelectedPage = (typeof SelectedPage)[keyof typeof SelectedPage];

export interface BenefitType {
    icon: JSX.Element;
    title: string;
    description: string;
}

export interface ClassType {
    name: string;
    description?: string;
    image: StaticImageData;
}