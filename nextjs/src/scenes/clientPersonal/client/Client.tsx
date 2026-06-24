'use client'

import { motion } from "framer-motion";
import { useModifyClient } from "@/hooks/useModifyClient";
import { observer } from "mobx-react-lite";
import { useStore } from "@/store/StoreProvider";
import { isClient } from "@/lib/utils/user.types.utils";
import ClientType from "@/types/client/client.type";
import { TopUpSection } from "./TopUpSection";

const Client = observer(() => {
    const { authStore } = useStore();
    const user = authStore.user;
    const client = user && isClient(user) ? user as ClientType : null;

    const {
        newPhone,
        setNewPhone,
        onEdit,
        setOnEdit,
        loading,
        handleEdit,
        handleDelete,
    } = useModifyClient(client?.phone ?? "");

    if (!client) {
        return null;
    }

    const formattedAmount = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(client.balance / 100);

    return (
        <motion.div className="flex flex-col rounded-2xl shadow-md ">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6"
            >
                <div className="flex flex-col gap-2">
                    <h2 className="text-2xl font-bold">
                        {client.firstName} {client.lastName}
                    </h2>
                    <p className="text-sm">{client.email}</p>
                    {onEdit ? (
                        <input
                            type="text"
                            value={newPhone}
                            onChange={(event) => setNewPhone(event.target.value)}
                            className="rounded text-sm bg-gray-100"
                        />
                    ) : (
                        <p className="text-sm">{client.phone}</p>
                    )}
                </div>

                <div className="flex flex-col items-start md:items-end gap-2">
                    <p className="text-sm">Age: {client.age}</p>
                    <p className="text-sm">Joined: {new Date(client.createdAt).toISOString().split("T")[0]}</p>
                    <p className="text-sm">Balance: {formattedAmount}</p>
                </div>
            </motion.div>
            <div className="flex">
                <TopUpSection />

                <button
                    className="flex-1 cursor-pointer bg-secondary-500 px-10 hover:bg-primary-500 hover:text-white transition-colors"
                    onClick={handleEdit}
                >
                    {onEdit ? (loading ? "Saving..." : "Save") : "Edit"}
                </button>

                {onEdit && (
                    <button
                        className="flex-1 cursor-pointer bg-gray-100 px-10 hover:bg-primary-500 hover:text-white transition-colors"
                        onClick={() => {
                            setOnEdit(false);
                            setNewPhone(client.phone);
                        }}
                    >
                        Cancel
                    </button>
                )}

                <button
                    className="flex-1 rounded-br-2xl cursor-pointer bg-primary-300 px-10 hover:bg-primary-500 hover:text-white transition-colors"
                    onClick={handleDelete}
                >
                    Delete
                </button>
            </div>
        </motion.div>
    );
});

export default Client;
