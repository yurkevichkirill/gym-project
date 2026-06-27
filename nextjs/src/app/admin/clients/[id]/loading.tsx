import LoadingState from "@/shared/ui/LoadingState";

const AdminClientLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState title="Opening client..." description="We are preparing the client record." />
        </main>
    );
};

export default AdminClientLoading;
