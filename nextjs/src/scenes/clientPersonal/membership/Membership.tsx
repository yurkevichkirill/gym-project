import { MembershipPlanType } from "@/types/membership/membership-plan.type";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";

const statusColorMap: Record<string, string> = {
    active: "bg-green-100 text-green-800",
    expired: "bg-red-100 text-red-800",
    pending: "bg-yellow-100 text-yellow-800",
    frozen: "bg-blue-100 text-blue-800",
    canceled: "bg-gray-100 text-gray-800",
};

type Props = {
    id: number,
    membershipPlan: MembershipPlanType,
    startDate: string,
    endDate: string,
    status: MembershipStatusEnum,
    visits: number,
    createdAt: string,
}

const PersonalMembership = ({
     id,
     membershipPlan,
     startDate,
     endDate,
     status,
     visits,
     createdAt
}: Props) => {
    const normalizedStatus = String(status).toLowerCase();
    const badgeColors = statusColorMap[normalizedStatus] || "bg-gray-100 text-gray-800";

    return (
        <div className="border border-gray-200 rounded-xl p-4 flex flex-col justify-between">
            <div className="flex justify-between items-start mb-2 gap-2">
                <p className="font-semibold">{membershipPlan.name}</p>
                <span className={`text-sm px-3 py-1 rounded-full ${badgeColors}`}>
                    {String(status).replace(/_/g, ' ')}
                </span>
            </div>
            
            <div className="mt-2">
                <p className="text-sm">{startDate} — {endDate}</p>
                <p className="text-sm mt-1">Visits left: <span className="font-semibold">{visits}</span></p>
            </div>
        </div>
    );
}

export default PersonalMembership;