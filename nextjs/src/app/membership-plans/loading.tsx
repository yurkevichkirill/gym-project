import LoadingState from "@/shared/ui/LoadingState";

const MembershipPlansLoading = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <LoadingState
                title="Loading membership plans..."
                description="We are preparing the membership catalog."
            />
        </main>
    );
};

export default MembershipPlansLoading;
