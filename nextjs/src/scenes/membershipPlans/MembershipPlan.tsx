'use client'

import Link from "next/link";
import { ClockIcon, BoltIcon } from "@heroicons/react/16/solid";
import PurchaseMembershipButton from "@/scenes/membershipPlans/PurchaseMembershipButton";

type Props = {
    id: number;
    name: string;
    durationDays: number;
    sessionLimit: number | null;
    price: number;
};

const MembershipPlan = ({ id, name, durationDays, sessionLimit, price }: Props) => {
    const isUnlimited = sessionLimit === null;
    const formattedPrice = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(price / 100);

    return (
        <li
            className="group flex w-[220px] flex-shrink-0 flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-primary-100 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
        >
            <div className="flex justify-center">
                <div className="rounded-full border border-gray-200 bg-primary-100 p-4 shadow-sm transition group-hover:scale-110">
                    {isUnlimited
                        ? <BoltIcon className="h-6 w-6 text-primary-500" />
                        : <ClockIcon className="h-6 w-6 text-primary-500" />
                    }
                </div>
            </div>

            <div className="flex flex-col gap-2 text-center">
                <h4 className="text-lg font-semibold tracking-wide">{name}</h4>
                <p className="text-sm text-gray-500">{durationDays} days</p>
                <p className="text-sm font-semibold text-gray-700">
                    {isUnlimited ? "Unlimited sessions" : `${sessionLimit} sessions`}
                </p>
            </div>

            <div className="text-center">
                <p className="text-2xl font-bold tracking-tight">{formattedPrice}</p>
            </div>

            <div className="flex flex-col gap-2">
                <Link
                    href={`/membership-plans/${id}`}
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-center text-sm font-semibold transition hover:border-secondary-500"
                >
                    View details
                </Link>
                <PurchaseMembershipButton
                    membershipPlanId={id}
                    membershipPlanName={name}
                    className="text-sm"
                />
            </div>
        </li>
    );
};

export default MembershipPlan;
