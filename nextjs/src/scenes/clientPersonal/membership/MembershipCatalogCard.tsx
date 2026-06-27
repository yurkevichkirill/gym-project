import Link from "next/link";
import MembershipType from "@/types/membership/membership.type";
import {
    formatMembershipDate,
    formatSessionLimit,
    getMembershipStatusClassName,
    getMembershipStatusLabel,
} from "@/scenes/clientPersonal/membership/membership-display";
import MembershipActions from "@/scenes/clientPersonal/membership/MembershipActions";

type MembershipCatalogCardProps = {
    membership: MembershipType;
};

const MembershipCatalogCard = ({ membership }: MembershipCatalogCardProps) => {
    const plan = membership.membershipPlan;

    return (
        <article className="flex h-full flex-col rounded-2xl bg-white p-6 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Membership #{membership.id}
                    </p>
                    <h2 className="mt-2 text-xl font-bold">{membership.name}</h2>
                    <p className="mt-1 text-sm text-gray-500">
                        {plan === null ? "Linked plan unavailable" : `Plan #${plan.id}`}
                    </p>
                </div>
                <span className={`rounded-full px-3 py-1 text-sm font-semibold ${getMembershipStatusClassName(membership.status)}`}>
                    {getMembershipStatusLabel(membership.status)}
                </span>
            </div>

            <dl className="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt className="text-gray-500">Start date</dt>
                    <dd className="mt-1 font-semibold">{formatMembershipDate(membership.startDate, "Not started")}</dd>
                </div>
                <div>
                    <dt className="text-gray-500">End date</dt>
                    <dd className="mt-1 font-semibold">{formatMembershipDate(membership.endDate)}</dd>
                </div>
                <div>
                    <dt className="text-gray-500">Visits used</dt>
                    <dd className="mt-1 font-semibold">{membership.visits}</dd>
                </div>
                <div>
                    <dt className="text-gray-500">Session limit</dt>
                    <dd className="mt-1 font-semibold">{formatSessionLimit(membership.sessionLimit)}</dd>
                </div>
            </dl>

            <div className="mt-auto grid gap-3 pt-6">
                <Link
                    href={`/me/memberships/${membership.id}`}
                    className="inline-flex w-full justify-center rounded-md bg-secondary-500 px-4 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                >
                    View details
                </Link>
                <MembershipActions
                    membershipId={membership.id}
                    status={membership.status}
                />
            </div>
        </article>
    );
};

export default MembershipCatalogCard;
