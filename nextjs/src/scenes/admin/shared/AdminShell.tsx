import Link from "next/link";
import type { ReactNode } from "react";
import Section, { secondaryActionClassName } from "@/shared/Section";

export type AdminNavItem = {
    href: string;
    label: string;
    description: string;
};

export const adminNavItems: AdminNavItem[] = [
    { href: "/admin/clients", label: "Clients", description: "Accounts, visits, imports, bookings and memberships." },
    { href: "/admin/trainers", label: "Trainers", description: "Trainer accounts, photos, status and worktime." },
    { href: "/admin/bookings", label: "Bookings", description: "Booking search, details and cancellations." },
    { href: "/admin/memberships", label: "Client memberships", description: "Membership assignment and lifecycle actions." },
    { href: "/admin/membership-plans", label: "Tariff plans", description: "Membership pricing, duration and visit limits." },
    { href: "/admin/payments", label: "Payments", description: "Read-only payment ledger and detail view." },
    { href: "/admin/training-types", label: "Training types", description: "Training catalog images and descriptions." },
    { href: "/admin/trainings", label: "Trainings", description: "Training schedule, reschedule, cancel and complete." },
];

type AdminShellProps = {
    title: string;
    description?: string;
    children: ReactNode;
    action?: ReactNode;
};

export const AdminShell = ({ title, description, children, action }: AdminShellProps) => (
    <main className="bg-gray-20 pt-32 pb-20">
        <div className="mx-auto flex w-11/12 max-w-5xl flex-col gap-6 sm:w-5/6">
            <Section
                title={title}
                description={description}
                action={(
                    <div className="flex flex-wrap gap-2">
                        <Link href="/admin" className={secondaryActionClassName}>
                            Admin home
                        </Link>
                        {action}
                    </div>
                )}
            >
                <nav className="flex gap-2 overflow-x-auto pb-1" aria-label="Admin sections">
                    {adminNavItems.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="shrink-0 rounded-xl border border-gray-100 bg-gray-20 px-3 py-2 text-sm font-semibold text-gray-500 transition hover:border-primary-300 hover:bg-primary-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>
            </Section>
            {children}
        </div>
    </main>
);
