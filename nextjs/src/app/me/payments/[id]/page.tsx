import { notFound } from "next/navigation";
import PaymentDetails from "@/scenes/clientPersonal/payment/PaymentDetails";

type PaymentPageProps = {
    params: Promise<{ id: string }>;
};

const PaymentPage = async ({ params }: PaymentPageProps) => {
    const { id } = await params;

    if (!/^\d+$/.test(id)) {
        notFound();
    }

    const paymentId = Number(id);

    if (!Number.isSafeInteger(paymentId) || paymentId <= 0) {
        notFound();
    }

    return (
        <main className="px-6 pt-32 pb-20">
            <PaymentDetails paymentId={paymentId} />
        </main>
    );
};

export default PaymentPage;
