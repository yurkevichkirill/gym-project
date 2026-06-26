import LoadingState from "@/shared/ui/LoadingState";

const MembershipPlanLoading = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <LoadingState
                title="Loading membership plan..."
                description="We are fetching the membership plan details."
            />
        </main>
    );
};

export default MembershipPlanLoading;
