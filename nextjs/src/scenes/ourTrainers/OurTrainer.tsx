'use client'

import { motion } from "framer-motion";
import Link from "next/link";
import Image from "next/image";

type Props = {
    id: number,
    name: string,
    photoPath: string | null,
}

const childVariant = {
    hidden: { opacity: 0, scale: 0.9 },
    visible: { opacity: 1, scale: 1 },
}

const OurTrainer = ({ id, name, photoPath }: Props) => {
    const storageBaseUrl = process.env.NEXT_PUBLIC_STORAGE_URL || 'http://localhost:9005/evogym-bucket';
    
    const isAbsoluteUrl = photoPath?.startsWith('http');
    const imageUrl = photoPath 
        ? (isAbsoluteUrl ? photoPath : `${storageBaseUrl}/${photoPath}`) 
        : '/assets/default-trainer.jpg';

    return (
        <Link href={`/trainers/${id}`}>
            <motion.div
                variants={childVariant}
                className="rounded-md w-7/8 h-auto"
            >
                <div className="relative aspect-[3/4] rounded-2xl border overflow-hidden">
                    <Image 
                        src={imageUrl} 
                        alt={`Photo of ${name}`} 
                        fill 
                        sizes="(max-width: 768px) 100vw, 300px" 
                        className="object-cover"
                        unoptimized={true}
                    />
                </div>
                <p className="font-bold text-2xl mt-3">{ name }</p>
            </motion.div>
        </Link>
    );
}

export default OurTrainer;