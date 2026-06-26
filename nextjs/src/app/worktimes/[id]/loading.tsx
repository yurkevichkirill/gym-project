import LoadingState from "@/shared/ui/LoadingState";

const WorktimeLoading = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <LoadingState
                title="Loading worktime..."
                description="We are fetching the current worktime details."
            />
        </main>
    );
};

export default WorktimeLoading;
