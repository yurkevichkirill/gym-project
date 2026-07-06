import { resolveStorageUrl } from "@/lib/resolveStorageUrl";
import Section from "@/shared/Section";
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
    const fullName = `${trainer.firstName} ${trainer.lastName}`;

    return (
        <Section title="Trainer profile">
            <div className="flex flex-col gap-5 md:flex-row md:items-start">
                <div className="relative aspect-square w-32 shrink-0 overflow-hidden rounded-2xl border border-gray-100 bg-gray-20 sm:w-40">
                    {photoUrl ? (
                        <Image
                            src={photoUrl}
                            alt={`Photo of ${fullName}`}
                            fill
                            sizes="(min-width: 768px) 160px, 128px"
                            className="object-cover"
                            unoptimized
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center px-4 text-center text-sm text-gray-500">
                            No profile photo
                        </div>
                    )}
                </div>

                <div className="min-w-0 flex-1">
                    <h1 className="text-2xl font-bold text-gray-500 sm:text-3xl">
                        {fullName}
                    </h1>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div className="min-w-0">
                            <dt className="font-semibold text-gray-500">Email</dt>
                            <dd className="mt-1 break-words text-gray-500">{trainer.email}</dd>
                        </div>
                        <div>
                            <dt className="font-semibold text-gray-500">Phone</dt>
                            <dd className="mt-1 text-gray-500">{trainer.phone}</dd>
                        </div>
                        <div>
                            <dt className="font-semibold text-gray-500">Specialization</dt>
                            <dd className="mt-1 text-gray-500">{trainer.trainingType.name}</dd>
                        </div>
                        <div>
                            <dt className="font-semibold text-gray-500">Joined</dt>
                            <dd className="mt-1 text-gray-500">{formatDate(trainer.createdAt)}</dd>
                        </div>
                    </dl>

                    <dl className="mt-5 grid items-stretch gap-4 sm:grid-cols-3">
                        <div className="flex min-h-28 flex-col rounded-2xl border border-gray-100 bg-gray-20/70 p-4">
                            <dt className="text-xs font-semibold uppercase text-gray-500">Current rate</dt>
                            <dd className="mt-2 text-xl font-bold text-gray-500">
                                {formatMoney(trainer.pricePerHour)}
                            </dd>
                            <p className="mt-auto pt-3 text-sm text-gray-500">Per hour</p>
                        </div>
                        <div className="flex min-h-28 flex-col rounded-2xl border border-gray-100 bg-gray-20/70 p-4">
                            <dt className="text-xs font-semibold uppercase text-gray-500">Balance</dt>
                            <dd className="mt-2 text-xl font-bold text-gray-500">
                                {formatMoney(trainer.balance)}
                            </dd>
                            <p className="mt-auto pt-3 text-sm text-gray-500">Available account value</p>
                        </div>
                        <div className="flex min-h-28 flex-col rounded-2xl border border-gray-100 bg-gray-20/70 p-4">
                            <dt className="text-xs font-semibold uppercase text-gray-500">Debt</dt>
                            <dd className="mt-2 text-xl font-bold text-gray-500">
                                {formatMoney(trainer.debt)}
                            </dd>
                            <p className="mt-auto pt-3 text-sm text-gray-500">Outstanding amount</p>
                        </div>
                    </dl>
                </div>
            </div>

            {trainer.education || trainer.about ? (
                <div className="mt-5 grid gap-4 md:grid-cols-2">
                    {trainer.education ? (
                        <div className="rounded-2xl border border-gray-100 bg-white p-4">
                            <h2 className="font-semibold text-gray-500">Education</h2>
                            <p className="mt-2 whitespace-pre-wrap text-sm text-gray-500">
                                {trainer.education}
                            </p>
                        </div>
                    ) : null}

                    {trainer.about ? (
                        <div className="rounded-2xl border border-gray-100 bg-white p-4">
                            <h2 className="font-semibold text-gray-500">About</h2>
                            <p className="mt-2 whitespace-pre-wrap text-sm text-gray-500">
                                {trainer.about}
                            </p>
                        </div>
                    ) : null}
                </div>
            ) : null}
        </Section>
    );
};

export default TrainerProfileOverview;
