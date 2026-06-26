import Link from "next/link";
import type WorktimeData from "@/types/trainer/public/worktime.type";

type Props = {
    worktime: WorktimeData;
};

const WorktimeDetails = ({ worktime }: Props) => {
    return (
        <section className="mx-auto w-full max-w-4xl">
            <div className="flex flex-wrap gap-3">
                <Link
                    href="/worktimes"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition hover:border-secondary-500"
                >
                    Back to worktimes
                </Link>
                <Link
                    href={`/trainers/${worktime.trainerId}`}
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition hover:border-secondary-500"
                >
                    View trainer
                </Link>
            </div>

            <article className="mt-6 rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            Worktime #{worktime.id}
                        </p>
                        <h1 className="mt-2 text-3xl font-bold sm:text-4xl">
                            <time dateTime={worktime.date}>{worktime.date}</time>
                        </h1>
                    </div>
                    <span className="self-start rounded-full bg-primary-100 px-4 py-2 text-sm font-semibold text-primary-500">
                        Backend timezone
                    </span>
                </div>

                <dl className="mt-8 grid gap-5 sm:grid-cols-3">
                    <div className="rounded-xl bg-gray-50 p-5">
                        <dt className="text-sm text-gray-600">Trainer ID</dt>
                        <dd className="mt-2 text-2xl font-bold">{worktime.trainerId}</dd>
                    </div>
                    <div className="rounded-xl bg-gray-50 p-5">
                        <dt className="text-sm text-gray-600">Training type ID</dt>
                        <dd className="mt-2 text-2xl font-bold">{worktime.trainingTypeId}</dd>
                    </div>
                    <div className="rounded-xl bg-gray-50 p-5">
                        <dt className="text-sm text-gray-600">Date format</dt>
                        <dd className="mt-2 text-2xl font-bold">YYYY-MM-DD</dd>
                    </div>
                </dl>

                <div className="mt-8">
                    <h2 className="text-xl font-bold">Free intervals returned by the server</h2>
                    {worktime.freeSlots.length === 0 ? (
                        <p className="mt-4 rounded-xl bg-gray-50 p-5 text-gray-600">
                            No free intervals were returned for this worktime in the current response.
                        </p>
                    ) : (
                        <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                            {worktime.freeSlots.map((slot) => (
                                <li
                                    key={`${slot.start}-${slot.end}`}
                                    className="rounded-xl bg-gray-50 p-5"
                                >
                                    <span className="block text-sm text-gray-600">Server time</span>
                                    <span className="mt-2 block font-mono text-xl font-bold">
                                        <time dateTime={slot.start}>{slot.start}</time>
                                        {" — "}
                                        <time dateTime={slot.end}>{slot.end}</time>
                                    </span>
                                    <span className="mt-2 block text-xs text-gray-500">HH:mm:ss</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
                    Dates and times are displayed exactly as returned by the API, without conversion to the browser timezone. Slot availability can change; the backend booking operation performs the final validation.
                </div>
            </article>
        </section>
    );
};

export default WorktimeDetails;
