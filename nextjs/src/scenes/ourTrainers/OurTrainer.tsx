'use client'

import { motion } from "framer-motion";
import Link from "next/link";
import Image from "next/image";
import { resolveStorageUrl } from "@/lib/resolveStorageUrl";

type Props = {
    id: number;
    name: string;
    photoPath: string | null;
};

const childVariant = {
    hidden: { opacity: 0, scale: 0.9 },
    visible: { opacity: 1, scale: 1 },
};

const OurTrainer = ({ id, name, photoPath }: Props) => {
    const imageUrl = photoPath ? resolveStorageUrl(photoPath, "") : null;

    return (
        <Link href={`/trainers/${id}`}>
            <motion.div
                variants={childVariant}
                className="rounded-md w-7/8 h-auto"
            >
                <div className="relative aspect-[3/4] rounded-2xl border overflow-hidden bg-gray-100">
                    {imageUrl ? (
                        <Image
                            src={imageUrl}
                            alt={`Photo of ${name}`}
                            fill
                            sizes="(max-width: 768px) 100vw, 300px"
                            className="object-cover"
                            unoptimized
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center px-4 text-center text-gray-400">
                            No image available
                        </div>
                    )}
                </div>
                <p className="font-bold text-2xl mt-3">{name}</p>
            </motion.div>
        </Link>
    );
};

export default OurTrainer;
