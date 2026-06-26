import Link from "next/link";
import type WorktimeData from "@/types/trainer/public/worktime.type";

type Props = {
    worktime: WorktimeData;
};

const PublicWorktimeCard = ({ worktime }: Props) => {
    return (
        <article className="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Worktime #{worktime.id}
                    </p>
                    <h2 className="mt-2 text-2xl font-bold">
                        <time dateTime={worktime.date}>{worktime.date}</time>
                    </h2>
                </div>
                <span className="rounded-full bg-primary-100 px-3 py-1 text-sm font-semibold text-primary-500">
                    Server time
                </span>
            </div>

            <dl className="mt-6 space-y-3 text-sm text-gray-600">
                <div className="flex items-center justify-between gap-4">
                    <dt>Trainer ID</dt>
                    <dd className="font-semibold text-gray-900">{worktime.trainerId}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt>Training type ID</dt>
                    <dd className="font-semibold text-gray-900">{worktime.trainingTypeId}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt>Date format</dt>
                    <dd className="font-semibold text-gray-900">YYYY-MM-DD</dd>
                </div>
            </dl>

            <div className="mt-6">
                <h3 className="font-semibold">Free intervals returned by the server</h3>
                {worktime.freeSlots.length === 0 ? (
                    <p className="mt-3 text-sm text-gray-600">
                        No free intervals were returned in this response.
                    </p>
                ) : (
                    <ul className="mt-3 flex flex-wrap gap-2">
                        {worktime.freeSlots.map((slot) => (
                            <li
                                key={`${slot.start}-${slot.end}`}
                                className="rounded-md bg-gray-100 px-3 py-2 font-mono text-sm text-gray-800"
                            >
                                <time dateTime={slot.start}>{slot.start}</time>
                                {" — "}
                                <time dateTime={slot.end}>{slot.end}</time>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <p className="mt-5 text-xs leading-5 text-gray-500">
                Times are shown without browser timezone conversion. Final availability is checked by the backend when a booking is created.
            </p>

            <div className="mt-auto flex flex-wrap gap-3 pt-6">
                <Link
                    href={`/worktimes/${worktime.id}`}
                    className="rounded-md bg-secondary-500 px-5 py-2 text-center font-semibold transition hover:bg-primary-500 hover:text-white"
                >
                    View details
                </Link>
                <Link
                    href={`/trainers/${worktime.trainerId}`}
                    className="rounded-md border border-gray-300 bg-white px-5 py-2 text-center font-semibold transition hover:border-secondary-500"
                >
                    View trainer
                </Link>
            </div>
        </article>
    );
};

export default PublicWorktimeCard;
