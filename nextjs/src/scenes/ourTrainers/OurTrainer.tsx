'use client'

import { motion } from "framer-motion";
import Link from "next/link";
import Image from "next/image";

type Props = {
    id: string,
    name: string,
    photoUrl: string,
}

const childVariant = {
    hidden: { opacity: 0, scale: 0.9 },
    visible: {opacity: 1, scale: 1 },
}

const OurTrainer = ({ id, name, photoUrl }: Props) => {
    return (
        <Link href={`/trainers/${id}`}>
            <motion.div
                variants={childVariant}
                className="rounded-md w-7/8 h-auto"
            >
                <div className="relative aspect-[3/4] rounded-2xl border">
                    <Image src={ photoUrl } alt="Avatar" fill sizes="(max-width: 768px) 100vw, 300px" className="rounded-2xl object-cover"/>
                </div>
                <p className="font-bold text-2xl">{ name }</p>
            </motion.div>
        </Link>
    );
}

export default OurTrainer;