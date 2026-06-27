import LoadingState from "@/shared/ui/LoadingState";

const MembershipsLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening memberships..."
                description="We are preparing your membership history."
            />
        </main>
    );
};

export default MembershipsLoading;
