import Image from "next/image";
import { resolveStorageUrl } from "@/lib/resolveStorageUrl";

type Props = {
    name: string;
    description: string;
    photoPath: string | null;
}

const TrainingType = ({ name, description, photoPath }: Props) => {
    const imageUrl = photoPath ? resolveStorageUrl(photoPath, "") : null;
    const overlayStyles = `absolute inset-0 z-20 flex
    flex-col items-center justify-center
    bg-gray-200 text-center text-gray-400
    opacity-0 transition duration-500 hover:opacity-90`;

    return (
        <div className="relative aspect-[5/3] w-full overflow-hidden bg-gray-100">
            <div className={overlayStyles}>
                <p className="text-2xl">{name}</p>
                <p className="mt-2 px-4 text-sm">{description}</p>
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
                <div className="flex h-full items-center justify-center text-gray-400">
                    No image available
                </div>
            )}
        </div>
    );
};

export default TrainingType;
