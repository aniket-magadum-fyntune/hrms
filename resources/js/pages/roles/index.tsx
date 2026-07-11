import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { AccessBadges } from '@/components/access-badges';
import { AccessCheckboxGroup } from '@/components/access-checkbox-group';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AccessRole } from '@/types/admin';

type RoleForm = {
    name: string;
    permissions: string[];
};

type RolesIndexProps = {
    roles: AccessRole[];
    permissions: string[];
};

function RoleDialog({
    role,
    permissions,
}: {
    role?: AccessRole;
    permissions: string[];
}) {
    const [open, setOpen] = useState(false);
    const form = useForm<RoleForm>({
        name: role?.name ?? '',
        permissions: role?.permissions ?? [],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);

                if (!role) {
                    form.reset();
                }
            },
        };

        if (role) {
            form.put(`/roles/${role.id}`, options);

            return;
        }

        form.post('/roles', options);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {role ? (
                    <Button variant="ghost" size="icon" aria-label="Edit role">
                        <Pencil />
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New role
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>
                            {role ? 'Edit role' : 'New role'}
                        </DialogTitle>
                        <DialogDescription>
                            Define a role and the permissions it grants.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor={`role-name-${role?.id ?? 'new'}`}>
                            Name
                        </Label>
                        <Input
                            id={`role-name-${role?.id ?? 'new'}`}
                            value={form.data.name}
                            disabled={role?.is_system}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            autoComplete="off"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label>Permissions</Label>
                        <AccessCheckboxGroup
                            items={permissions}
                            value={form.data.permissions}
                            onChange={(value) =>
                                form.setData('permissions', value)
                            }
                            emptyText="Create permissions before assigning them to roles."
                        />
                        <InputError message={form.errors.permissions} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            Save role
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteRoleDialog({ role }: { role: AccessRole }) {
    const [open, setOpen] = useState(false);
    const form = useForm({});

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="icon" aria-label="Delete role">
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete role</DialogTitle>
                    <DialogDescription>
                        This removes the {role.name} role from users and cannot
                        be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={form.processing}
                        onClick={() => form.delete(`/roles/${role.id}`)}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function RolesIndex({ roles, permissions }: RolesIndexProps) {
    return (
        <>
            <Head title="Roles" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Roles
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Group permissions into job-based access profiles.
                        </p>
                    </div>
                    <RoleDialog permissions={permissions} />
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Role</th>
                                <th className="px-4 py-3 font-medium">
                                    Permissions
                                </th>
                                <th className="px-4 py-3 font-medium">Users</th>
                                <th className="w-24 px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {roles.map((role) => (
                                <tr key={role.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">
                                        {role.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        <AccessBadges
                                            values={role.permissions}
                                            emptyText="No permissions"
                                        />
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {role.users_count}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            {role.can_update && (
                                                <RoleDialog
                                                    role={role}
                                                    permissions={permissions}
                                                />
                                            )}
                                            {role.can_delete && (
                                                <DeleteRoleDialog role={role} />
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {roles.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No roles yet.
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

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: '/roles',
        },
    ],
};
