import { notFound } from "next/navigation";
import PaymentDetails from "@/scenes/clientPersonal/payment/PaymentDetails";
import { PaymentScope } from "@/api/client/payments.api";
import RoleGuard from "@/shared/auth/RoleGuard";
import { Roles } from "@/types/auth.type";

type TrainerPaymentPageProps = {
    params: Promise<{ id: string }>;
};

const TrainerPaymentPage = async ({ params }: TrainerPaymentPageProps) => {
    const { id } = await params;

    if (!/^\d+$/.test(id)) {
        notFound();
    }

    const paymentId = Number(id);

    if (!Number.isSafeInteger(paymentId) || paymentId <= 0) {
        notFound();
    }

    return (
        <RoleGuard allowedRoles={[Roles.TRAINER]}>
            <main className="px-6 pt-32 pb-20">
                <PaymentDetails paymentId={paymentId} forcedScope={PaymentScope.TRAINER} />
            </main>
        </RoleGuard>
    );
};

export default TrainerPaymentPage;
