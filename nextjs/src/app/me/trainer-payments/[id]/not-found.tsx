import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const TrainerPaymentNotFound = () => (
    <main className="px-6 pt-32 pb-20">
        <EmptyState
            title="Payment not found"
            description="The requested trainer payment identifier is invalid or the payment is unavailable."
            action={(
                <Link
                    href="/me/trainer-payments"
                    className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                >
                    Back to trainer payments
                </Link>
            )}
        />
    </main>
);

export default TrainerPaymentNotFound;
