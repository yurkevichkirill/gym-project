import { apiGet, apiPost } from "@/lib/apiClient";
import MembershipType from "@/types/membership/membership.type";
import MembershipBuyType from "@/types/membership/membership-buy.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { MembershipFreezeType } from "@/types/membership/membership-freeze.type";
import { MembershipUnfreezeType } from "@/types/membership/membership-unfreeze.type";
import { MembershipRenewType } from "@/types/membership/membership-renew.type";
import { MembershipTerminateType } from "@/types/membership/membership-terminate.type";

export const getAllMemberships = async (): Promise<MembershipType[]> => {
    const response = await apiGet<ApiCollectionResponse<MembershipType[]>>("/me/memberships/");

    return response.data;
};

export const buyMembership = async ({ membershipPlanId }: MembershipBuyType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>, MembershipBuyType>("/me/membership/", {
        membershipPlanId,
    });

    return response.data;
};

export const freezeMembership = async ({ id }: MembershipFreezeType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/freeze/`);

    return response.data;
};

export const unfreezeMembership = async ({ id }: MembershipUnfreezeType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/unfreeze/`);

    return response.data;
};

export const renewMembership = async ({ id }: MembershipRenewType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/renew/`);

    return response.data;
};

export const terminateMembership = async ({ id }: MembershipTerminateType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/terminate/`);

    return response.data;
};
