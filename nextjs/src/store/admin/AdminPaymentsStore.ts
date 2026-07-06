import { getAdminPayment, getAdminPayments, getAdminPaymentsRequestKey } from "@/api/admin/payments.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type { AdminPayment, AdminPaymentsGetQueryParams } from "@/types/admin/admin-payment.type";

class AdminPaymentsStore extends AdminResourceStore<AdminPayment, AdminPaymentsGetQueryParams> {
    public constructor() {
        super(getAdminPayments, getAdminPaymentsRequestKey, getAdminPayment);
    }
}

export const adminPaymentsStore = new AdminPaymentsStore();

