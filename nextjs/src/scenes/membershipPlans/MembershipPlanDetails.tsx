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

const MembershipPlanDetails = ({ membershipPlan }: Props) => {
    const isUnlimited = membershipPlan.sessionLimit === null;

    return (
        <section className="mx-auto w-full max-w-4xl">
            <Link
                href="/membership-plans"
                className="inline-flex rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition hover:border-secondary-500"
            >
                Back to membership plans
            </Link>

            <article className="mt-6 rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            {isUnlimited ? "Unlimited plan" : "Limited plan"}
                        </p>
                        <h1 className="mt-2 text-3xl font-bold sm:text-4xl">{membershipPlan.name}</h1>
                    </div>
                    <div className="rounded-full bg-primary-100 p-4">
                        {isUnlimited ? (
                            <BoltIcon className="h-8 w-8 text-primary-500" aria-hidden="true" />
                        ) : (
                            <ClockIcon className="h-8 w-8 text-primary-500" aria-hidden="true" />
                        )}
                    </div>
                </div>

                <dl className="mt-10 grid gap-5 sm:grid-cols-3">
                    <div className="rounded-xl bg-gray-50 p-5">
                        <dt className="text-sm text-gray-600">Duration</dt>
                        <dd className="mt-2 text-2xl font-bold">{membershipPlan.durationDays} days</dd>
                    </div>
                    <div className="rounded-xl bg-gray-50 p-5">
                        <dt className="text-sm text-gray-600">Sessions</dt>
                        <dd className="mt-2 text-2xl font-bold">
                            {isUnlimited ? "Unlimited" : membershipPlan.sessionLimit}
                        </dd>
                    </div>
                    <div className="rounded-xl bg-gray-50 p-5">
                        <dt className="text-sm text-gray-600">Price</dt>
                        <dd className="mt-2 text-2xl font-bold">{formatPrice(membershipPlan.price)}</dd>
                    </div>
                </dl>

                <div className="mt-8 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="max-w-2xl text-sm leading-6 text-gray-600">
                        Purchase is available to authenticated client accounts. The server remains the source of truth for active, frozen, and pending membership conflicts.
                    </p>
                    <PurchaseMembershipButton
                        membershipPlanId={membershipPlan.id}
                        membershipPlanName={membershipPlan.name}
                        className="min-w-40"
                    />
                </div>
            </article>
        </section>
    );
};

export default MembershipPlanDetails;
