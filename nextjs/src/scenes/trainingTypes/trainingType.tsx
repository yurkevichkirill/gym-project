import Image from "next/image";

type Props = {
    name: string;
    description: string;
    photoUrl: string;
}

const TrainingType = ({ name, description, photoUrl }: Props) => {
    const overlayStyles = `absolute inset-0 z-30 flex
    flex-col items-center justify-center
    bg-gray-200 text-center text-gray-400
    opacity-0 transition duration-500 hover:opacity-90`;

    return (
        <div className="relative inline-block">
            <div className={overlayStyles}>
                <p className="text-2xl" >{name}</p>
            </div>
            <Image
                   src={photoUrl} alt="" width={500} height={300}/>
        </div>
    );
}

export default TrainingType;