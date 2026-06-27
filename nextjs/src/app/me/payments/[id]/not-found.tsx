import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const PaymentNotFound = () => (
    <main className="px-6 pt-32 pb-20">
        <EmptyState
            title="Payment not found"
            description="The requested payment identifier is invalid or the payment is unavailable."
            action={(
                <Link
                    href="/me/payments"
                    className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                >
                    Back to payments
                </Link>
            )}
        />
    </main>
);

export default PaymentNotFound;
