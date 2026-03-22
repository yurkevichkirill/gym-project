import "@/app/globals.css"
import Home from "@/scenes/home";
import Memberships from "@/scenes/memberships"
import ContactUs from "@/scenes/contactUs";
import OurTrainers from "@/scenes/ourTrainers";
import TrainingTypes from "@/scenes/trainingTypes";

export default function MainPage() {
    return (
        <>
            <Home />
            <OurTrainers />
            <Memberships />
            <TrainingTypes />
            <ContactUs />
        </>
    );
}