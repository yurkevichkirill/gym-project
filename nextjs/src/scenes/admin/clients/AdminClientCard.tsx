import Link from "next/link";
import { AdminClient } from "@/types/admin/admin-client.type";
import {
    formatAdminClientDate,
    formatAdminClientMoney,
    getAdminClientStateClassName,
    getAdminClientStateLabel,
} from "@/scenes/admin/clients/admin-client-display";

type AdminClientCardProps = {
    client: AdminClient;
};

const AdminClientCard = ({ client }: AdminClientCardProps) => {
    return (
        <article className="flex h-full flex-col rounded-2xl bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Client #{client.id}
                    </p>
                    <h2 className="mt-2 text-xl font-bold">
                        {client.firstName} {client.lastName}
                    </h2>
                    <p className="mt-1 break-all text-sm text-gray-500">{client.email}</p>
                </div>
                <span className={`rounded-full px-3 py-1 text-sm font-semibold ${getAdminClientStateClassName(client)}`}>
                    {getAdminClientStateLabel(client)}
                </span>
            </div>

            <dl className="mt-5 grid gap-3 text-sm">
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Age</dt>
                    <dd className="font-semibold">{client.age}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Balance</dt>
                    <dd className="font-semibold">{formatAdminClientMoney(client.balance)}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Phone</dt>
                    <dd className="font-semibold">{client.phone}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Created</dt>
                    <dd className="text-right font-semibold">{formatAdminClientDate(client.createdAt)}</dd>
                </div>
            </dl>

            <Link
                href={`/admin/clients/${client.id}`}
                className="mt-6 rounded-md border border-gray-300 px-4 py-2 text-center font-semibold transition hover:border-secondary-500"
            >
                Open client
            </Link>
        </article>
    );
};

export default AdminClientCard;
