import "@/app/globals.css"
import Home from "@/scenes/home";
import MembershipPlans from "@/scenes/membershipPlans";
import OurTrainers from "@/scenes/ourTrainers";
import TrainingTypes from "@/scenes/trainingTypes";

export default function MainPage() {
    return (
        <>
            <Home />
            <OurTrainers />
            <MembershipPlans />
            <TrainingTypes />
        </>
    );
}