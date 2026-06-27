'use client'

import Link from "next/link";
import { MembershipPlanType } from "@/types/membership/membership-plan.type";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import {
    formatMembershipDate,
    formatSessionLimit,
    getMembershipStatusClassName,
    getMembershipStatusLabel,
} from "@/scenes/clientPersonal/membership/membership-display";
import MembershipActions from "@/scenes/clientPersonal/membership/MembershipActions";

type Props = {
    id: number;
    name: string;
    sessionLimit: number | null;
    membershipPlan: MembershipPlanType | null;
    startDate: string | null;
    endDate: string | null;
    status: MembershipStatusEnum;
    visits: number;
};

const PersonalMembership = ({
    id,
    name,
    sessionLimit,
    membershipPlan,
    startDate,
    endDate,
    status,
    visits,
}: Props) => {
    return (
        <article className="flex flex-col gap-4 rounded-xl border border-gray-200 p-4">
            <div>
                <div className="mb-2 flex items-start justify-between gap-2">
                    <div>
                        <p className="font-semibold">{name}</p>
                        <p className="mt-1 text-xs text-gray-500">
                            {membershipPlan === null ? "Linked plan unavailable" : `Plan #${membershipPlan.id}`}
                        </p>
                    </div>
                    <span className={`rounded-full px-3 py-1 text-sm ${getMembershipStatusClassName(status)}`}>
                        {getMembershipStatusLabel(status)}
                    </span>
                </div>

                <div className="mt-3 text-sm text-gray-700">
                    <p>
                        {formatMembershipDate(startDate, "Not started")} — {formatMembershipDate(endDate)}
                    </p>
                    <p className="mt-1">
                        Visits used: <span className="font-semibold">{visits}</span>
                    </p>
                    <p className="mt-1">
                        Session limit: <span className="font-semibold">{formatSessionLimit(sessionLimit)}</span>
                    </p>
                </div>
            </div>

            <div className="mt-auto flex flex-wrap gap-2 border-t pt-3">
                <Link
                    href={`/me/memberships/${id}`}
                    className="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold transition hover:border-secondary-500"
                >
                    View details
                </Link>
                <MembershipActions
                    membershipId={id}
                    status={status}
                    compact
                />
            </div>
        </article>
    );
};

export default PersonalMembership;
