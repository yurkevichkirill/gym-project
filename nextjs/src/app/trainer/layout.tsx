import type {ReactNode} from "react";
import RoleGuard from "@/shared/auth/RoleGuard";
import {Roles} from "@/types/auth.type";

const TRAINER_ROLES = [Roles.TRAINER] as const;

const TrainerLayout = ({children}: Readonly<{children: ReactNode}>) => {
    return (
        <RoleGuard allowedRoles={TRAINER_ROLES}>
            {children}
        </RoleGuard>
    );
};

export default TrainerLayout;
