import Image from "next/image";
import Link from "next/link";
import { resolveStorageUrl } from "@/lib/resolveStorageUrl";
import type TrainingTypeData from "@/types/training-type.type";

type Props = {
    trainingType: TrainingTypeData;
};

const TrainingTypeDetails = ({ trainingType }: Props) => {
    const imageUrl = trainingType.photoPath
        ? resolveStorageUrl(trainingType.photoPath, "")
        : null;

    return (
        <section className="mx-auto w-full max-w-5xl">
            <Link
                href="/training-types"
                className="inline-flex rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition hover:border-secondary-500"
            >
                Back to training types
            </Link>

            <article className="mt-6 grid gap-8 rounded-2xl bg-white p-6 shadow-sm md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] md:p-8">
                <div className="relative aspect-[5/3] overflow-hidden rounded-2xl bg-gray-100 md:aspect-[4/3]">
                    {imageUrl ? (
                        <Image
                            src={imageUrl}
                            alt={trainingType.name}
                            fill
                            sizes="(max-width: 768px) 100vw, 45vw"
                            className="object-cover"
                            unoptimized
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center px-6 text-center text-gray-500">
                            No image available
                        </div>
                    )}
                </div>

                <div className="flex flex-col justify-center">
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Training type
                    </p>
                    <h1 className="mt-2 text-3xl font-bold sm:text-4xl">
                        {trainingType.name}
                    </h1>
                    <p className="mt-6 whitespace-pre-line text-lg leading-8 text-gray-700">
                        {trainingType.description}
                    </p>
                </div>
            </article>
        </section>
    );
};

export default TrainingTypeDetails;
