'use client'

import { SelectedPage, type ClassType } from "@/shared/types";
import image1 from "@/assets/image1.png"
import image2 from "@/assets/image2.png"
import image3 from "@/assets/image3.png"
import image4 from "@/assets/image4.png"
import image5 from "@/assets/image5.png"
import image6 from "@/assets/image6.png"
import { motion } from "framer-motion";
import HText from "@/shared/HText";
import Class from "@/scenes/ourClasses/Class";
import {useNavigation} from "@/context/navigation-context";

const classes: Array<ClassType> = [
    {
        name: "Weight Training Classes",
        image: image1,
    },
    {
        name: "Fitness Classes",
        description: "Brutal HIIT combat sessions and metabolic conditioning that torch fat and build relentless endurance.",
        image: image2,
    },
    {
        name: "Adventure Classes",
        description: "Battle ropes, sled pushes, tire flips - functional strength training that forges real-world power.",
        image: image3,
    },
    {
        name: "Ab Core Classes",
        image: image4,
    },
    {
        name: "Yoga Classes",
        description: "Dynamic power yoga flows that enhance mobility, recovery, and mental toughness for warriors.",
        image: image5,
    },
    {
        name: "Training Classes",
        description: "Elite programming blending strength, hypertrophy, and conditioning for maximum physique transformation.",
        image: image6,
    },
]

const OurClasses = () => {
    const { setSelectedPage } = useNavigation();

    return (
        <section
            id="ourclasses"
            className="w-full bg-primary-100 py-40"
        >
            <motion.div
                onViewportEnter={() => setSelectedPage(SelectedPage.OurClasses)}
            >
                <motion.div
                    className="mx-auto w-5/6"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.5 }}
                    transition={{ duration: 0.5 }}
                    variants={{
                        hidden: { opacity: 0, x:-50 },
                        visible: { opacity: 1, x: 0 },
                    }}
                >
                    <div className="md:w-3/5">
                        <HText>OUR CLASSES</HText>
                        <p className="py-5">
                            Assault your limits with our savage class lineup: Powerlifting Warfare,
                            HIIT Combat Drills, Brutal Bodybuilding Circuits. Expert coaches drive
                            you beyond failure. No beginners. Only warriors ready to shatter PRs and
                            forge elite physiques. Claim your spot. Destroy weakness.
                        </p>
                    </div>
                </motion.div>
                <div>
                    <div className="mt-10 h-[353px] w-full overflow-x-auto overflow-y-hidden">
                        <ul className="w-[2800px whitespace-nowrap">
                            {classes.map((item: ClassType, index) => (
                                <Class
                                    key={`${item.name}-${index}`}
                                    name={item.name}
                                    description={item.description}
                                    image={item.image}
                                />
                            ))}
                        </ul>
                    </div>
                </div>
            </motion.div>
        </section>
    );
};

export default OurClasses;