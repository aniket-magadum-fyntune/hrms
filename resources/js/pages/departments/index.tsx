import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
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
import type { Department } from '@/types/admin';

type DepartmentForm = {
    name: string;
    description: string;
};

type DepartmentsIndexProps = {
    departments: Department[];
};

function DepartmentDialog({ department }: { department?: Department }) {
    const [open, setOpen] = useState(false);
    const form = useForm<DepartmentForm>({
        name: department?.name ?? '',
        description: department?.description ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);

                if (!department) {
                    form.reset();
                }
            },
        };

        if (department) {
            form.put(`/departments/${department.id}`, options);

            return;
        }

        form.post('/departments', options);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {department ? (
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Edit department"
                    >
                        <Pencil />
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New department
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>
                            {department ? 'Edit department' : 'New department'}
                        </DialogTitle>
                        <DialogDescription>
                            Maintain the functional teams used on employee
                            profiles.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label
                            htmlFor={`department-name-${department?.id ?? 'new'}`}
                        >
                            Name
                        </Label>
                        <Input
                            id={`department-name-${department?.id ?? 'new'}`}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            autoComplete="off"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor={`department-description-${department?.id ?? 'new'}`}
                        >
                            Description
                        </Label>
                        <textarea
                            id={`department-description-${department?.id ?? 'new'}`}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            Save department
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteDepartmentDialog({ department }: { department: Department }) {
    const [open, setOpen] = useState(false);
    const form = useForm({});

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Delete department"
                >
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete department</DialogTitle>
                    <DialogDescription>
                        This removes {department.name} from employee profiles.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={form.processing}
                        onClick={() =>
                            form.delete(`/departments/${department.id}`)
                        }
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function DepartmentsIndex({
    departments,
}: DepartmentsIndexProps) {
    return (
        <>
            <Head title="Departments" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Departments
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage functional teams for employee profiles.
                        </p>
                    </div>
                    <DepartmentDialog />
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Department
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Description
                                </th>
                                <th className="px-4 py-3 font-medium">Users</th>
                                <th className="w-24 px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {departments.map((department) => (
                                <tr key={department.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">
                                        {department.name}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {department.description ?? 'No description'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {department.users_count}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            <DepartmentDialog
                                                department={department}
                                            />
                                            <DeleteDepartmentDialog
                                                department={department}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {departments.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No departments yet.
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

DepartmentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'People',
            href: '/users',
        },
        {
            title: 'Departments',
            href: '/departments',
        },
    ],
};
