import type {ReactNode} from "react";
import RoleGuard from "@/shared/auth/RoleGuard";
import {Roles} from "@/types/auth.type";

const CLIENT_ROLES = [Roles.CLIENT] as const;

const MeLayout = ({children}: Readonly<{children: ReactNode}>) => {
    return (
        <RoleGuard allowedRoles={CLIENT_ROLES}>
            {children}
        </RoleGuard>
    );
};

export default MeLayout;
