'use client'

import {
    ClockIcon,
    BoltIcon
} from "@heroicons/react/16/solid";
import { handleMembership } from "@/handlers/membershipHandler";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";

const statusColorMap: Record<MembershipStatusEnum, string> = {
    [MembershipStatusEnum.ACTIVE]: "bg-green-100 text-green-800 border-green-200",
    [MembershipStatusEnum.EXPIRED]: "bg-gray-200 text-gray-600 border-gray-300",
    [MembershipStatusEnum.FROZEN]: "bg-gradient-to-r from-cyan-50 via-blue-100 to-cyan-50 text-cyan-800 border-cyan-300",
    [MembershipStatusEnum.PENDING]: "bg-yellow-100 text-yellow-800 border-yellow-200",
    [MembershipStatusEnum.CANCELED_PAYMENT_FAILED]: "bg-red-200 text-red-900 border-red-300",
    [MembershipStatusEnum.CANCELED_BY_CLIENT]: "bg-red-100 text-red-800 border-red-200",
    [MembershipStatusEnum.CANCELED_BY_SYSTEM]: "bg-red-50 text-red-700 border-red-200",
};

type Props = {
    id: number;
    name: string;
    durationDays: number;
    sessionLimit: number | null;
    price: number;
    status: MembershipStatusEnum;
};

const Membership = ({ id, name, durationDays, sessionLimit, price, status }: Props) => {
    const badgeColor = statusColorMap[status] || "bg-gray-100 text-gray-800 border-gray-200";
    
    const isFrozen = status === MembershipStatusEnum.FROZEN;

    return (
        <li
            className={`
                relative 
                group
                rounded-2xl
                border 
                ${isFrozen ? 'border-cyan-200 bg-cyan-50' : 'border-gray-200 bg-primary-100'}
                p-6
                flex-shrink-0
                w-[200px]
                flex flex-col
                justify-between
                gap-4
                shadow-sm
                hover:shadow-xl
                hover:-translate-y-1
                transition-all duration-300
            `}
        >
            {/* STATUS BADGE */}
            {status && (
                <div 
                    className={`
                        absolute top-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded border uppercase tracking-wider 
                        ${badgeColor} 
                        ${isFrozen ? 'animate-pulse shadow-[0_0_8px_rgba(103,232,249,0.8)]' : ''}
                    `}
                >
                    {isFrozen && <span className="mr-1 inline-block animate-spin-slow">❄️</span>}
                    {status.replace(/_/g, ' ')}
                </div>
            )}

            {/* Icon */}
            <div className="flex justify-center mt-2">
                <div className={`
                    rounded-full
                    border 
                    ${isFrozen ? 'border-cyan-200 bg-cyan-100' : 'border-gray-200 bg-primary-100'}
                    p-4
                    shadow-sm
                    group-hover:scale-110
                    transition
                `}>
                    {sessionLimit
                        ? <ClockIcon className={`h-6 w-6 ${isFrozen ? 'text-cyan-600' : 'text-primary-500'}`}/>
                        : <BoltIcon className={`h-6 w-6 ${isFrozen ? 'text-cyan-600' : 'text-primary-500'}`}/>
                    }
                </div>
            </div>

            {/* Content */}
            <div className="flex flex-col gap-2 text-center">
                <h4 className="text-lg font-semibold tracking-wide">
                    {name}
                </h4>

                <p className="text-sm text-gray-500">
                    {durationDays} days
                </p>
            </div>

            {/* Price */}
            <div className="text-center">
                <p className={`text-2xl font-bold tracking-tight ${isFrozen ? 'text-cyan-900' : ''}`}>
                    ${price}
                </p>
            </div>

            {/* CTA */}
            <button
                disabled={isFrozen}
                className={`
                    mt-2
                    inline-block
                    rounded-md
                    text-center
                    px-4 py-2
                    transition
                    ${isFrozen 
                        ? 'bg-cyan-200 text-cyan-700 cursor-not-allowed opacity-70' 
                        : 'bg-secondary-500 hover:bg-primary-500 hover:text-white'
                    }
                `}
                onClick={() => handleMembership(id)}
            >
                {isFrozen ? 'Frozen' : 'Buy plan'}
            </button>
        </li>
    );
};

export default Membership;