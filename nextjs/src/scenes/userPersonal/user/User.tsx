import { motion } from "framer-motion";
import {useEditUser} from "@/hooks/useEditUser";

type Props = {
    id: number,
    age: number,
    firstName: string,
    lastName: string,
    email: string,
    phone: string,
    createdAt: string,
    balance: string,
}

const User =
    ({
          id,
          age,
          firstName,
          lastName,
          email,
          phone,
          createdAt,
          balance
     }: Props
    ) => {
    const {
        newPhone,
        setNewPhone,
        onEdit,
        setOnEdit,
        loading,
        handleEdit,
    } = useEditUser(phone);

    return (
        <motion.div className="flex flex-col rounded-2xl shadow-md ">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6"
            >
                {/* LEFT */}
                <div className="flex flex-col gap-2">
                    <h2 className="text-2xl font-bold">
                        {firstName} {lastName}
                    </h2>
                    <p className="text-sm">{email}</p>
                    {onEdit ?
                        <input
                            type="text"
                            value={newPhone}
                            onChange={(e) => setNewPhone(e.target.value)}
                            className="rounded text-sm bg-gray-100"
                        /> :
                        <p className="text-sm">{newPhone}</p>
                    }
                </div>

                {/* RIGHT */}
                <div className="flex flex-col items-start md:items-end gap-2">
                    <p className="text-sm">Age: {age}</p>
                    <p className="text-sm">Joined: {new Date(createdAt).toISOString().split('T')[0]}</p>

                    <p className="text-sm">
                        Balance: {balance} $
                    </p>
                </div>
            </motion.div>
            <div className="flex">
                <button
                    className="flex-1 rounded-bl-2xl cursor-pointer bg-secondary-500 px-10 hover:bg-primary-500 hover:text-white"
                    onClick={handleEdit}
                >
                    {onEdit ? (loading ? "Saving..." : "Save") : "Edit"}
                </button>
                {onEdit &&
                <button
                    className="flex-1 cursor-pointer bg-gray-100 px-10 hover:bg-primary-500 hover:text-white"
                    onClick={() => setOnEdit(false)}
                >
                    Cancel
                </button>
                }
                <button className="flex-1 rounded-br-2xl cursor-pointer bg-primary-300 px-10 hover:bg-primary-500 hover:text-white">
                    Delete
                </button>
            </div>
        </motion.div>
    );
}

export default User;