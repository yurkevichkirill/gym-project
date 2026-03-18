import "@/app/globals.css"
import Home from "@/scenes/home";
import Benefits from "@/scenes/benefits"
import OurClasses from "@/scenes/ourClasses";
import ContactUs from "@/scenes/contactUs";
import OurTrainers from "@/scenes/ourTrainers";

export default function MainPage() {

    return (
        <>
            <Home />
            <OurTrainers />
            <Benefits />
            <OurClasses />
            <ContactUs />
        </>
    );
}