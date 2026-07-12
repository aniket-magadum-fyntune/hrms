import { Head } from '@inertiajs/react';
import type { AccessPermission } from '@/types/admin';

type PermissionsIndexProps = {
    permissions: AccessPermission[];
};

export default function PermissionsIndex({
    permissions,
}: PermissionsIndexProps) {
    return (
        <>
            <Head title="Permissions" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Permissions
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Review code-defined actions available for role
                            assignment.
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Permission
                                </th>
                                <th className="px-4 py-3 font-medium">Roles</th>
                                <th className="px-4 py-3 font-medium">Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            {permissions.map((permission) => (
                                <tr key={permission.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">
                                        {permission.name}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {permission.roles_count}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {permission.users_count}
                                    </td>
                                </tr>
                            ))}
                            {permissions.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={3}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No permissions yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

PermissionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Access',
            href: '/roles',
        },
        {
            title: 'Permissions',
            href: '/permissions',
        },
    ],
};
