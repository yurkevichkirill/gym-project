import type {ReactNode} from "react";
import RoleGuard from "@/shared/auth/RoleGuard";
import {Roles} from "@/types/auth.type";

const ADMIN_ROLES = [Roles.ADMIN] as const;

const AdminLayout = ({children}: Readonly<{children: ReactNode}>) => {
    return (
        <RoleGuard allowedRoles={ADMIN_ROLES}>
            {children}
        </RoleGuard>
    );
};

export default AdminLayout;
