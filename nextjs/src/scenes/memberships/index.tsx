'use client'

import {SelectedPage} from "@/shared/types";
import { motion } from "framer-motion";
import HText from "@/shared/HText";
import Membership from "@/scenes/memberships/Membership";
import ActionButton from "@/shared/ActionButton";
import BenefitsPageGraphic from "@/assets/BenefitsPageGraphic.png";
import {useNavigation} from "@/context/navigation-context";
import Image from "next/image";
import {useEffect, useState} from "react";
import {MembershipPlanType} from "@/types/membership/membership-plan.type";
import {getMembershipPlans} from "@/api/public/membership-plans.api";

const container = {
    hidden: {},
    visible: {
        transition: { staggerChildren: 0.2 }
    }
}

const Memberships = () => {
    const { setSelectedPage } = useNavigation();
    const [ memberships, setMemberships ] = useState<MembershipPlanType[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getMembershipPlans();
                setMemberships(data);
            } catch (e) {
                console.error(e);

                if (e instanceof Error) {
                    setError(e.message);
                } else {
                    setError("Something went wrong");
                }
            } finally {
                setLoading(false);
            }
        }

        void fetchData();
    }, []);

    if (loading) {
        return <div>Loading ...</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

    return (
        <section id="memberships" className="mx-auto min-h-full w-full py-20">
            <motion.div
                onViewportEnter={() => setSelectedPage(SelectedPage.Memberships)}
            >
                {/* HEADER */}
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
                    <HText>MORE THAN JUST A GYM.</HText>
                    <p className="my-5 text-sm">
                        We arm you with elite iron, savage trainers, and brutal workouts to
                        shatter your limits and forge a weapon-grade body. Personalized assault
                        on every fighter — no mercy, just total transformation.
                    </p>
                </motion.div>

                {/* MEMBERSHIPS */}
                <div>
                    <div className="mt-10 h-[353px] w-full overflow-x-auto overflow-y-hidden">
                        <ul className="flex justify-center gap-20 whitespace-nowrap">
                            {memberships.map((membership: MembershipPlanType) => (
                                <Membership
                                    key = {membership.id}
                                    id = {membership.id}
                                    name = {membership.name}
                                    durationDays = {membership.durationDays}
                                    sessionLimit = {membership.sessionLimit}
                                    price = {membership.price}
                                />
                            ))}
                        </ul>
                    </div>
                </div>

                {/* GRAPHICS AND DESCRIPTION */}
                <div className="mt-16 items-center justify-between gap-20 md:mt-28 md:flex mx-auto w-5/6">
                    {/* GRAPHIC */}
                    <Image
                        className="mx-auto"
                        alt="benefits-page-graphic"
                        src={BenefitsPageGraphic}
                    />

                    {/* DESCRIPTION */}
                    <div>
                        {/* TITLE */}
                        <div>
                            <div className="before:absolute before:-top-20 before:-left-20 before:z-[-1] before:content-abstractwaves ">
                                <motion.div
                                    initial="hidden"
                                    whileInView="visible"
                                    viewport={{ once: true, amount: 0.5 }}
                                    transition={{ duration: 0.5 }}
                                    variants={{
                                        hidden: { opacity: 0, x:50 },
                                        visible: { opacity: 1, x: 0 },
                                    }}
                                >
                                    <HText>
                                        MILLIONS OF HAPPY MEMBERS GETTING{" "}
                                        <span className="text-primary-500">FIT</span>
                                    </HText>
                                </motion.div>
                            </div>
                        </div>

                        {/* DESCRIPT */}
                        <motion.div
                            initial="hidden"
                            whileInView="visible"
                            viewport={{ once: true, amount: 0.5 }}
                            transition={{ delay: 0.2, duration: 0.5 }}
                            variants={{
                                hidden: { opacity: 0, x:50 },
                                visible: { opacity: 1, x: 0 },
                            }}
                        >
                            <p className="my-5">
                                Thousands of battle-hardened members across the globe have transformed
                                through our brutal training regimens. From complete beginners to elite
                                competitors, they've shattered personal records, built unbreakable bodies,
                                and achieved fitness dominance. Our savage methodology delivers measurable
                                results - increased strength, shredded fat, enhanced endurance.
                            </p>
                            <p className="mb-5">
                                Join the legion of victors who've conquered their genetic limits.
                                Your transformation awaits among millions who've already claimed
                                victory over weakness. Step into the arena. Become unbreakable.
                            </p>
                        </motion.div>

                        {/* BUTTON */}
                        <div className="relative mt-16">
                            <div className="before:absolute before:-bottom-20 before:right-40 before:z-[-1] before:content-sparkles">
                                <ActionButton setSelectedPage={setSelectedPage}>
                                    Join Now
                                </ActionButton>
                            </div>
                        </div>

                    </div>
                </div>
            </motion.div>
        </section>
    );
};

export default Memberships;