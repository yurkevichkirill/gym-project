import { resolveStorageUrl } from "@/lib/resolveStorageUrl";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";
import Image from "next/image";

const formatMoney = (amount: number): string => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount / 100);
};

const formatDate = (value: string): string => {
    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? "Not available"
        : new Intl.DateTimeFormat("en-US", {
            dateStyle: "medium",
        }).format(date);
};

const TrainerProfileOverview = ({
    trainer,
}: {
    trainer: TrainerPersonalType;
}) => {
    const photoUrl = trainer.photoPath
        ? resolveStorageUrl(trainer.photoPath, "")
        : null;

    return (
        <section className="rounded-2xl bg-white p-6 shadow-md sm:p-8">
            <div className="flex flex-col gap-6 sm:flex-row sm:items-start">
                <div className="relative aspect-[3/4] w-full max-w-56 shrink-0 overflow-hidden rounded-2xl bg-gray-100">
                    {photoUrl ? (
                        <Image
                            src={photoUrl}
                            alt={`Photo of ${trainer.firstName} ${trainer.lastName}`}
                            fill
                            sizes="224px"
                            className="object-cover"
                            unoptimized
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center px-4 text-center text-gray-400">
                            No profile photo
                        </div>
                    )}
                </div>

                <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium uppercase tracking-wide text-gray-500">
                        Trainer profile
                    </p>
                    <h1 className="mt-1 text-3xl font-bold">
                        {trainer.firstName} {trainer.lastName}
                    </h1>
                    <p className="mt-2 break-all text-gray-600">{trainer.email}</p>

                    <dl className="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm text-gray-500">Specialization</dt>
                            <dd className="font-semibold">{trainer.trainingType.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Current rate</dt>
                            <dd className="font-semibold">
                                {formatMoney(trainer.pricePerHour)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Balance</dt>
                            <dd className="font-semibold">{formatMoney(trainer.balance)}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Debt</dt>
                            <dd className="font-semibold">{formatMoney(trainer.debt)}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Joined</dt>
                            <dd className="font-semibold">{formatDate(trainer.createdAt)}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Last updated</dt>
                            <dd className="font-semibold">{formatDate(trainer.updatedAt)}</dd>
                        </div>
                    </dl>

                    {trainer.education && (
                        <div className="mt-6">
                            <h2 className="font-semibold">Education</h2>
                            <p className="mt-1 whitespace-pre-wrap text-gray-600">
                                {trainer.education}
                            </p>
                        </div>
                    )}

                    {trainer.about && (
                        <div className="mt-4">
                            <h2 className="font-semibold">About</h2>
                            <p className="mt-1 whitespace-pre-wrap text-gray-600">
                                {trainer.about}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
};

export default TrainerProfileOverview;
