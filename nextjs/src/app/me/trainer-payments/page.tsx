import PaymentsCatalog from "@/scenes/clientPersonal/payment/PaymentsCatalog";
import { PaymentScope } from "@/api/client/payments.api";
import RoleGuard from "@/shared/auth/RoleGuard";
import { Roles } from "@/types/auth.type";

const TrainerPaymentsPage = () => {
    return (
        <RoleGuard allowedRoles={[Roles.TRAINER]}>
            <main className="px-6 pt-32 pb-20">
                <PaymentsCatalog forcedScope={PaymentScope.TRAINER} />
            </main>
        </RoleGuard>
    );
};

export default TrainerPaymentsPage;
