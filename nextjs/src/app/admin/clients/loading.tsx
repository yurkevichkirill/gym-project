import LoadingState from "@/shared/ui/LoadingState";

const AdminClientsLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState title="Opening clients..." description="We are preparing client administration." />
        </main>
    );
};

export default AdminClientsLoading;
