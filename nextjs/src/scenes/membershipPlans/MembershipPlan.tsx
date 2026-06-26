'use client'

import Link from "next/link";
import { ClockIcon, BoltIcon } from "@heroicons/react/16/solid";

type Props = {
    id: number;
    name: string;
    durationDays: number;
    sessionLimit: number | null;
    price: number;
    onBuy: (id: number) => Promise<void>;
    isLoading: boolean;
};

const MembershipPlan = ({ id, name, durationDays, sessionLimit, price, onBuy, isLoading }: Props) => {
    const isUnlimited = sessionLimit === null;
    const formattedPrice = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(price / 100);

    return (
        <li
            className="
                group rounded-2xl border border-gray-200 bg-primary-100 p-6 flex-shrink-0 w-[220px]
                flex flex-col justify-between gap-4 shadow-sm hover:shadow-xl hover:-translate-y-1
                transition-all duration-300
            "
        >
            <div className="flex justify-center">
                <div className="rounded-full border border-gray-200 bg-primary-100 p-4 shadow-sm group-hover:scale-110 transition">
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
                <button
                    type="button"
                    disabled={isLoading}
                    className="
                        inline-block rounded-md text-center px-4 py-2 bg-secondary-500
                        transition hover:bg-primary-500 hover:text-white cursor-pointer disabled:opacity-50
                    "
                    onClick={() => onBuy(id)}
                >
                    {isLoading ? "Processing..." : "Buy plan"}
                </button>
            </div>
        </li>
    );
};

export default MembershipPlan;
