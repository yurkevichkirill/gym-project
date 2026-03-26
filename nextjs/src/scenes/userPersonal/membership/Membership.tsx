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
        <>
            <p>{id}</p>
            <p>{membershipPlan.name}</p>
            <p>{startDate}</p>
            <p>{endDate}</p>
            <p>{status}</p>
            <p>{visits}</p>
            <p>{createdAt}</p>
        </>
    );
}

export default PersonalMembership;