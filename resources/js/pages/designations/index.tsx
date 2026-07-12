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
import type { Designation } from '@/types/admin';

type DesignationForm = {
    name: string;
    description: string;
    max_users: string;
};

type DesignationsIndexProps = {
    designations: Designation[];
};

function DesignationDialog({ designation }: { designation?: Designation }) {
    const [open, setOpen] = useState(false);
    const form = useForm<DesignationForm>({
        name: designation?.name ?? '',
        description: designation?.description ?? '',
        max_users: designation?.max_users?.toString() ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);

                if (!designation) {
                    form.reset();
                }
            },
        };

        if (designation) {
            form.put(`/designations/${designation.id}`, options);

            return;
        }

        form.post('/designations', options);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {designation ? (
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Edit designation"
                    >
                        <Pencil />
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New designation
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>
                            {designation ? 'Edit designation' : 'New designation'}
                        </DialogTitle>
                        <DialogDescription>
                            Maintain job titles used on employee profiles.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label
                            htmlFor={`designation-name-${designation?.id ?? 'new'}`}
                        >
                            Name
                        </Label>
                        <Input
                            id={`designation-name-${designation?.id ?? 'new'}`}
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
                            htmlFor={`designation-description-${designation?.id ?? 'new'}`}
                        >
                            Description
                        </Label>
                        <textarea
                            id={`designation-description-${designation?.id ?? 'new'}`}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor={`designation-max-users-${designation?.id ?? 'new'}`}
                        >
                            Max users
                        </Label>
                        <Input
                            id={`designation-max-users-${designation?.id ?? 'new'}`}
                            type="number"
                            min="1"
                            value={form.data.max_users}
                            onChange={(event) =>
                                form.setData('max_users', event.target.value)
                            }
                            placeholder="Unlimited"
                            autoComplete="off"
                        />
                        <InputError message={form.errors.max_users} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            Save designation
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteDesignationDialog({ designation }: { designation: Designation }) {
    const [open, setOpen] = useState(false);
    const form = useForm({});

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Delete designation"
                >
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete designation</DialogTitle>
                    <DialogDescription>
                        This removes {designation.name} from employee profiles.
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
                            form.delete(`/designations/${designation.id}`)
                        }
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function DesignationsIndex({
    designations,
}: DesignationsIndexProps) {
    return (
        <>
            <Head title="Designations" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Designations
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage job titles for employee profiles.
                        </p>
                    </div>
                    <DesignationDialog />
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Designation
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Description
                                </th>
                                <th className="px-4 py-3 font-medium">Users</th>
                                <th className="px-4 py-3 font-medium">Limit</th>
                                <th className="w-24 px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {designations.map((designation) => (
                                <tr key={designation.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">
                                        {designation.name}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {designation.description ??
                                            'No description'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {designation.users_count}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {designation.max_users ?? 'Unlimited'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            <DesignationDialog
                                                designation={designation}
                                            />
                                            <DeleteDesignationDialog
                                                designation={designation}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {designations.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No designations yet.
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

DesignationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Designations',
            href: '/designations',
        },
    ],
};
