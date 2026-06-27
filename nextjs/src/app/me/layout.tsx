import type {ReactNode} from "react";
import RoleGuard from "@/shared/auth/RoleGuard";
import {Roles} from "@/types/auth.type";

const ME_ROLES = [Roles.CLIENT, Roles.TRAINER] as const;

const MeLayout = ({children}: Readonly<{children: ReactNode}>) => {
    return (
        <RoleGuard allowedRoles={ME_ROLES}>
            {children}
        </RoleGuard>
    );
};

export default MeLayout;
