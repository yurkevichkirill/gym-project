import Home from "@/scenes/home";
import MembershipPlans from "@/scenes/membershipPlans";
import OurTrainers from "@/scenes/ourTrainers";
import TrainingTypes from "@/scenes/trainingTypes";
import { getMembershipPlans } from "@/api/public/membership-plans.api";
import { getTrainers } from "@/api/public/trainers.api";
import { getTrainingTypes } from "@/api/public/training-types.api";
import { getErrorMessage } from "@/lib/getErrorMessage";

export const dynamic = "force-dynamic";

export default async function MainPage() {
    const [trainersResult, membershipPlansResult, trainingTypesResult] = await Promise.allSettled([
        getTrainers(),
        getMembershipPlans(),
        getTrainingTypes(),
    ]);

    const trainers = trainersResult.status === "fulfilled" ? trainersResult.value : [];
    const trainersError = trainersResult.status === "rejected"
        ? getErrorMessage(trainersResult.reason, "Unable to load trainers.")
        : null;

    const membershipPlans = membershipPlansResult.status === "fulfilled"
        ? membershipPlansResult.value
        : [];
    const membershipPlansError = membershipPlansResult.status === "rejected"
        ? getErrorMessage(membershipPlansResult.reason, "Unable to load membership plans.")
        : null;

    const trainingTypes = trainingTypesResult.status === "fulfilled"
        ? trainingTypesResult.value
        : [];
    const trainingTypesError = trainingTypesResult.status === "rejected"
        ? getErrorMessage(trainingTypesResult.reason, "Unable to load training types.")
        : null;

    return (
        <>
            <Home />
            <OurTrainers trainers={trainers} error={trainersError} />
            <MembershipPlans membershipPlans={membershipPlans} error={membershipPlansError} />
            <TrainingTypes trainingTypes={trainingTypes} error={trainingTypesError} />
        </>
    );
}
