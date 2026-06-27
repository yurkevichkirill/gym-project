import Link from "next/link";
import { BoltIcon, ClockIcon } from "@heroicons/react/16/solid";
import type { MembershipPlanType } from "@/types/membership/membership-plan.type";
import PurchaseMembershipButton from "@/scenes/membershipPlans/PurchaseMembershipButton";

type Props = {
    membershipPlan: MembershipPlanType;
};

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(price / 100);
};

const PublicMembershipPlanCard = ({ membershipPlan }: Props) => {
    const isUnlimited = membershipPlan.sessionLimit === null;

    return (
        <article className="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        {isUnlimited ? "Unlimited" : "Limited"}
                    </p>
                    <h2 className="mt-2 text-2xl font-bold">{membershipPlan.name}</h2>
                </div>
                <div className="rounded-full bg-primary-100 p-3">
                    {isUnlimited ? (
                        <BoltIcon className="h-6 w-6 text-primary-500" aria-hidden="true" />
                    ) : (
                        <ClockIcon className="h-6 w-6 text-primary-500" aria-hidden="true" />
                    )}
                </div>
            </div>

            <dl className="mt-6 space-y-3 text-sm text-gray-600">
                <div className="flex items-center justify-between gap-4">
                    <dt>Duration</dt>
                    <dd className="font-semibold text-gray-900">{membershipPlan.durationDays} days</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt>Sessions</dt>
                    <dd className="font-semibold text-gray-900">
                        {isUnlimited ? "Unlimited" : membershipPlan.sessionLimit}
                    </dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt>Price</dt>
                    <dd className="text-xl font-bold text-gray-900">{formatPrice(membershipPlan.price)}</dd>
                </div>
            </dl>

            <div className="mt-auto grid gap-3 pt-8">
                <Link
                    href={`/membership-plans/${membershipPlan.id}`}
                    className="rounded-md border border-gray-300 bg-white px-5 py-2 text-center font-semibold transition hover:border-secondary-500"
                >
                    View details
                </Link>
                <PurchaseMembershipButton
                    membershipPlanId={membershipPlan.id}
                    membershipPlanName={membershipPlan.name}
                />
            </div>
        </article>
    );
};

export default PublicMembershipPlanCard;
