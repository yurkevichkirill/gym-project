import Image from "next/image";
import Link from "next/link";
import { resolveStorageUrl } from "@/lib/resolveStorageUrl";

type Props = {
    id: number;
    name: string;
    description: string;
    photoPath: string | null;
};

const TrainingType = ({ id, name, description, photoPath }: Props) => {
    const imageUrl = photoPath ? resolveStorageUrl(photoPath, "") : null;
    const overlayStyles = `absolute inset-0 z-20 flex
    flex-col items-center justify-center
    bg-gray-200 text-center text-gray-700
    opacity-0 transition duration-500
    group-hover:opacity-90 group-focus-visible:opacity-90`;

    return (
        <Link
            href={`/training-types/${id}`}
            className="group relative block aspect-[5/3] w-full overflow-hidden rounded-xl bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary-500"
            aria-label={`View ${name} training type`}
        >
            <div className={overlayStyles}>
                <p className="text-2xl font-semibold">{name}</p>
                <p className="mt-2 line-clamp-3 px-4 text-sm">{description}</p>
                <span className="mt-4 text-sm font-semibold">View details</span>
            </div>
            {imageUrl ? (
                <Image
                    src={imageUrl}
                    alt={name}
                    fill
                    sizes="(max-width: 768px) 50vw, 25vw"
                    className="object-cover"
                    unoptimized
                />
            ) : (
                <div className="flex h-full flex-col items-center justify-center px-4 text-center text-gray-500">
                    <p className="text-lg font-semibold">{name}</p>
                    <p className="mt-2 text-sm">No image available</p>
                </div>
            )}
        </Link>
    );
};

export default TrainingType;
