import { motion } from "framer-motion";

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
        return (
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="rounded-2xl shadow-md p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6"
            >
                {/* LEFT */}
                <div className="flex flex-col gap-2">
                    <h2 className="text-2xl font-bold">
                        {firstName} {lastName}
                    </h2>
                    <p className="text-sm">{email}</p>
                    <p className="text-sm">{phone}</p>
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
        );
}

export default User;