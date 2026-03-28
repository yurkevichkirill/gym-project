import {MembershipPlanType} from "@/types/membership-plan.type";

type Props = {
    id: number,
    membershipPlan: MembershipPlanType,
    startDate: string,
    endDate: string,
    status: string,
    visits: number,
    createdAt: string,
}

const PersonalMembership =
    ({
         id,
         membershipPlan,
         startDate,
         endDate,
         status,
         visits,
         createdAt
    }: Props) => {
        return (
            <div className="border rounded-xl p-4">
                <p className="font-semibold">{membershipPlan.name}</p>
                <p className="text-sm">{startDate} — {endDate}</p>
                <p className="text-sm mt-2">Visits left: {visits}</p>
            </div>
        );
}

export default PersonalMembership;