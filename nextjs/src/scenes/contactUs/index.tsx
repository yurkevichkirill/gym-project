'use client'

import {SelectedPage} from "@/shared/types";
import {useForm} from "react-hook-form";
import { motion } from "framer-motion";
import HText from "@/shared/HText";
import ContactUsPageGraphic from "@/assets/ContactUsPageGraphic.png";
import Image from "next/image";
import {useNavigation} from "@/context/navigation-context";
import {notify} from "@/lib/notify";
import { ApiClientError } from "@/lib/apiClient";
import { submitContactRequest } from "@/api/contact/contact.api";
import type { ContactRequest } from "@/types/contact/contact-request.type";

type ContactFormData = ContactRequest;

const getContactErrorMessage = (error: unknown): string => {
    if (!(error instanceof ApiClientError)) {
        return "We could not send your message. Please try again.";
    }

    if (error.status === 422 || error.status === 400) {
        return "Please check the highlighted fields and try again.";
    }

    if (error.status === 429) {
        return "Too many contact requests. Please wait and try again later.";
    }

    if (error.status === 503 || error.status === 500) {
        return "Contact delivery is temporarily unavailable. Please try again later.";
    }

    return "We could not send your message. Please try again.";
};

const ContactUs = () => {
    const { setSelectedPage } = useNavigation();
    const inputStyles = `mb-5 w-full rounded-lg bg-primary-300 px-5 py-3 placeholder-white`;

    const {
        register,
        handleSubmit,
        reset,
        formState: { errors, isSubmitting }
    } = useForm<ContactFormData>();

    const onSubmit = async (data: ContactFormData) => {
        const toastId = notify.loading("Sending your message...");

        try {
            await submitContactRequest(data);
            reset();
            notify.success(
                "Message sent",
                "Thank you. We will get back to you soon.",
                toastId,
            );
        } catch (error: unknown) {
            notify.error(
                "Message was not sent",
                getContactErrorMessage(error),
                toastId,
            );
        }
    };

    return <section id="contactus" className="mx-auto w-5/6 pt-24 pb-32">
        <motion.div onViewportEnter={() => setSelectedPage(SelectedPage.ContactUs)}>
            {/* HEADER */}
            <motion.div
                className="md:w-3/5"
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.5 }}
                transition={{ duration: 0.5 }}
                variants={{
                    hidden: { opacity: 0, x:-50 },
                    visible: { opacity: 1, x: 0 },
                }}
            >
                <HText>
                    <span className="text-primary-500">JOIN NOW</span>
                    <p className="my-5">
                        Join thousands of warriors who’ve shattered their limits.
                        Enter your info below to unlock elite training programs,
                        savage classes, and personalized coaching that delivers
                        unbreakable results. No weakness tolerated. Transformation starts now.
                    </p>
                </HText>
            </motion.div>

            {/* FORM AND IMAGE */}
            <div className="mt-10 justify-between gap-8 md:flex">
                <motion.div
                    className="mt-10 basis-3/5 md:mt-0"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.5 }}
                    transition={{ duration: 0.5 }}
                    variants={{
                        hidden: { opacity: 0, y: 50 },
                        visible: { opacity: 1, y: 0 },
                    }}
                >
                    <form onSubmit={handleSubmit(onSubmit)}>
                        <input
                            className={inputStyles}
                            type="text"
                            placeholder="NAME"
                            {...register("name", {
                                required: true,
                                maxLength: 100,
                            })}
                        />
                        {errors.name && (
                            <p className="mt-1 text-primary-500">
                                {errors.name.type === "required" && "This field is required."}
                                {errors.name.type === "maxLength" && "Max length is 100 char."}
                            </p>
                        )}

                        <input
                            className={inputStyles}
                            type="email"
                            placeholder="EMAIL"
                            {...register("email", {
                                required: true,
                                maxLength: 254,
                                pattern: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                            })}
                        />
                        {errors.email && (
                            <p className="mt-1 text-primary-500">
                                {errors.email.type === "required" && "This field is required."}
                                {errors.email.type === "maxLength" && "Max length is 254 char."}
                                {errors.email.type === "pattern" && "Invalid email address."}
                            </p>
                        )}

                        <textarea
                            className={inputStyles}
                            placeholder="MESSAGE"
                            rows={4}
                            cols={50}
                            {...register("message", {
                                required: true,
                                maxLength: 2000,
                            })}
                        />
                        {errors.message && (
                            <p className="mt-1 text-primary-500">
                                {errors.message.type === "required" && "This field is required."}
                                {errors.message.type === "maxLength" && "Max length is 2000 char."}
                            </p>
                        )}

                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="mt-5 rounded-lg bg-secondary-500 px-20 py-3 transition duration-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isSubmitting ? "SENDING..." : "SUBMIT"}
                        </button>
                    </form>
                </motion.div>
                <motion.div
                    className="relative mt-16 basis-2/5 md:mt-0"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.5 }}
                    transition={{ delay:0.2, duration: 0.5 }}
                    variants={{
                        hidden: { opacity: 0, y:-50 },
                        visible: { opacity: 1, y: 0 },
                    }}
                >
                    <div className="md:before:content-evolvetext w-full before:absolute before:-bottom-20 before:-right-10 before:z-[-1]">
                        <Image
                            className="w-full"
                            alt="contact-us-page-graphic"
                            src={ContactUsPageGraphic}
                        />
                    </div>
                </motion.div>
            </div>
        </motion.div>
    </section>
};

export default ContactUs;
