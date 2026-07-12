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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AccessUser, OptionItem } from '@/types/admin';

type UserForm = {
    name: string;
    email: string;
    password: string;
    department_id: number | null;
    designation_id: number | null;
    roles: string[];
};

type UsersIndexProps = {
    users: AccessUser[];
    roles: string[];
    departments: OptionItem[];
    designations: OptionItem[];
    currentUserId: number;
};

function UserDialog({
    user,
    roles,
    departments,
    designations,
}: {
    user?: AccessUser;
    roles: string[];
    departments: OptionItem[];
    designations: OptionItem[];
}) {
    const [open, setOpen] = useState(false);
    const form = useForm<UserForm>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        department_id: user?.department_id ?? null,
        designation_id: user?.designation_id ?? null,
        roles: user?.roles ?? [],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);
                form.reset('password');

                if (!user) {
                    form.reset();
                }
            },
        };

        if (user) {
            form.put(`/users/${user.id}`, options);

            return;
        }

        form.post('/users', options);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {user ? (
                    <Button variant="ghost" size="icon" aria-label="Edit user">
                        <Pencil />
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New user
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-2xl">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>
                            {user ? 'Edit user' : 'New user'}
                        </DialogTitle>
                        <DialogDescription>
                            Manage account details and role assignments.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor={`user-name-${user?.id ?? 'new'}`}>
                                Name
                            </Label>
                            <Input
                                id={`user-name-${user?.id ?? 'new'}`}
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                autoComplete="name"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor={`user-email-${user?.id ?? 'new'}`}>
                                Email
                            </Label>
                            <Input
                                id={`user-email-${user?.id ?? 'new'}`}
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                autoComplete="email"
                            />
                            <InputError message={form.errors.email} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor={`user-password-${user?.id ?? 'new'}`}>
                            Password
                        </Label>
                        <Input
                            id={`user-password-${user?.id ?? 'new'}`}
                            type="password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            placeholder={
                                user
                                    ? 'Leave blank to keep current password'
                                    : 'Minimum 8 characters'
                            }
                            autoComplete="new-password"
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label
                                htmlFor={`user-department-${user?.id ?? 'new'}`}
                            >
                                Department
                            </Label>
                            <Select
                                value={
                                    form.data.department_id?.toString() ??
                                    'none'
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'department_id',
                                        value === 'none' ? null : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger
                                    id={`user-department-${user?.id ?? 'new'}`}
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select department" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        No department
                                    </SelectItem>
                                    {departments.map((department) => (
                                        <SelectItem
                                            key={department.id}
                                            value={department.id.toString()}
                                        >
                                            {department.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.department_id} />
                        </div>

                        <div className="space-y-2">
                            <Label
                                htmlFor={`user-designation-${user?.id ?? 'new'}`}
                            >
                                Designation
                            </Label>
                            <Select
                                value={
                                    form.data.designation_id?.toString() ??
                                    'none'
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'designation_id',
                                        value === 'none' ? null : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger
                                    id={`user-designation-${user?.id ?? 'new'}`}
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select designation" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        No designation
                                    </SelectItem>
                                    {designations.map((designation) => (
                                        <SelectItem
                                            key={designation.id}
                                            value={designation.id.toString()}
                                        >
                                            {designation.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.designation_id} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>Roles</Label>
                        <AccessCheckboxGroup
                            items={roles}
                            value={form.data.roles}
                            onChange={(value) => form.setData('roles', value)}
                            emptyText="Create roles before assigning them to users."
                        />
                        <InputError message={form.errors.roles} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            Save user
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteUserDialog({
    user,
    disabled,
}: {
    user: AccessUser;
    disabled: boolean;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({});

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Delete user"
                    disabled={disabled}
                >
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete user</DialogTitle>
                    <DialogDescription>
                        This permanently removes {user.name} and their access
                        assignments.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={form.processing}
                        onClick={() => form.delete(`/users/${user.id}`)}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function UsersIndex({
    users,
    roles,
    departments,
    designations,
    currentUserId,
}: UsersIndexProps) {
    return (
        <>
            <Head title="Users" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Users
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage accounts, profiles, and role assignments.
                        </p>
                    </div>
                    <UserDialog
                        roles={roles}
                        departments={departments}
                        designations={designations}
                    />
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[920px] text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">User</th>
                                <th className="px-4 py-3 font-medium">
                                    Department
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Designation
                                </th>
                                <th className="px-4 py-3 font-medium">Roles</th>
                                <th className="px-4 py-3 font-medium">
                                    Created
                                </th>
                                <th className="w-24 px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {users.map((user) => (
                                <tr key={user.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {user.name}
                                        </div>
                                        <div className="text-muted-foreground">
                                            {user.email}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {user.department ?? 'Unassigned'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {user.designation ?? 'Unassigned'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <AccessBadges
                                            values={user.roles}
                                            emptyText="No roles"
                                        />
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {user.created_at ?? 'Unknown'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            <UserDialog
                                                user={user}
                                                roles={roles}
                                                departments={departments}
                                                designations={designations}
                                            />
                                            <DeleteUserDialog
                                                user={user}
                                                disabled={
                                                    user.id === currentUserId
                                                }
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {users.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No users yet.
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

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'People',
            href: '/users',
        },
        {
            title: 'Users',
            href: '/users',
        },
    ],
};
