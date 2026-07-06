import { apiDelete, apiPatch, apiPost } from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import {
    getMembershipPlan,
    getMembershipPlansPage,
    getMembershipPlansRequestKey,
    parseMembershipPlansListParams,
} from "@/api/public/membership-plans.api";
import {
    AdminMembershipPlan,
    AdminMembershipPlanCreateRequest,
    AdminMembershipPlanUpdateRequest,
} from "@/types/admin/admin-membership-plan.type";

export {
    getMembershipPlan as getAdminMembershipPlan,
    getMembershipPlansPage as getAdminMembershipPlans,
    getMembershipPlansRequestKey as getAdminMembershipPlansRequestKey,
    parseMembershipPlansListParams as parseAdminMembershipPlansListParams,
};

export const createAdminMembershipPlan = async (
    payload: AdminMembershipPlanCreateRequest,
): Promise<AdminMembershipPlan> => {
    const response = await apiPost<ApiItemResponse<AdminMembershipPlan>, AdminMembershipPlanCreateRequest>(
        "/membership/plans/",
        payload,
    );

    return response.data;
};

export const updateAdminMembershipPlan = async (
    id: number,
    payload: AdminMembershipPlanUpdateRequest,
): Promise<AdminMembershipPlan> => {
    const response = await apiPatch<ApiItemResponse<AdminMembershipPlan>, AdminMembershipPlanUpdateRequest>(
        `/membership/plans/${id}/`,
        payload,
    );

    return response.data;
};

export const deleteAdminMembershipPlan = async (id: number): Promise<void> => {
    await apiDelete<null>(`/membership/plans/${id}/`);
};

void getMembershipPlan;
void getMembershipPlansPage;
void getMembershipPlansRequestKey;
void parseMembershipPlansListParams;

