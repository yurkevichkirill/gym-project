import LoadingState from "@/shared/ui/LoadingState";

const MembershipDetailsLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening membership..."
                description="We are preparing the membership details."
            />
        </main>
    );
};

export default MembershipDetailsLoading;
