import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { Employee, OptionItem } from '@/types/admin';

type EmployeeForm = {
    employee_code: string;
    name: string;
    work_email: string;
    create_login: boolean;
    department_id: number | null;
    designation_id: number | null;
    manager_id: number | null;
    employment_status: string;
    joined_on: string;
};

type EmployeesIndexProps = {
    employees: Employee[];
    departments: OptionItem[];
    designations: OptionItem[];
    managers: OptionItem[];
    statuses: string[];
};

function titleCase(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function nullableSelectValue(value: number | null) {
    return value?.toString() ?? 'none';
}

function EmployeeDialog({
    employee,
    departments,
    designations,
    managers,
    statuses,
}: {
    employee?: Employee;
    departments: OptionItem[];
    designations: OptionItem[];
    managers: OptionItem[];
    statuses: string[];
}) {
    const [open, setOpen] = useState(false);
    const managerOptions = useMemo(
        () => managers.filter((manager) => manager.id !== employee?.id),
        [employee?.id, managers],
    );

    const form = useForm<EmployeeForm>({
        employee_code: employee?.employee_code ?? '',
        name: employee?.name ?? '',
        work_email: employee?.work_email ?? '',
        create_login: false,
        department_id: employee?.department_id ?? null,
        designation_id: employee?.designation_id ?? null,
        manager_id: employee?.manager_id ?? null,
        employment_status: employee?.employment_status ?? 'active',
        joined_on: employee?.joined_on ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);

                if (!employee) {
                    form.reset();
                }
            },
        };

        if (employee) {
            form.put(`/employees/${employee.id}`, options);

            return;
        }

        form.post('/employees', options);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {employee ? (
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Edit employee"
                    >
                        <Pencil />
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New employee
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-3xl">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>
                            {employee ? 'Edit employee' : 'New employee'}
                        </DialogTitle>
                        <DialogDescription>
                            Manage employee identity, reporting, and employment
                            assignment.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor={`employee-code-${employee?.id ?? 'new'}`}>
                                Employee code
                            </Label>
                            <Input
                                id={`employee-code-${employee?.id ?? 'new'}`}
                                value={form.data.employee_code}
                                onChange={(event) =>
                                    form.setData(
                                        'employee_code',
                                        event.target.value,
                                    )
                                }
                                autoComplete="off"
                            />
                            <InputError message={form.errors.employee_code} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor={`employee-name-${employee?.id ?? 'new'}`}>
                                Name
                            </Label>
                            <Input
                                id={`employee-name-${employee?.id ?? 'new'}`}
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                autoComplete="name"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor={`employee-work-email-${employee?.id ?? 'new'}`}>
                                Work email
                            </Label>
                            <Input
                                id={`employee-work-email-${employee?.id ?? 'new'}`}
                                type="email"
                                value={form.data.work_email}
                                onChange={(event) =>
                                    form.setData(
                                        'work_email',
                                        event.target.value,
                                    )
                                }
                                autoComplete="email"
                            />
                            <InputError message={form.errors.work_email} />
                        </div>

                        <div className="space-y-2 sm:col-span-2">
                            <Label>Login access</Label>
                            {employee?.user_email ? (
                                <div className="rounded-md border bg-muted/30 px-3 py-2">
                                    <div className="text-sm font-medium">
                                        Enabled
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {employee.user_email}
                                    </div>
                                </div>
                            ) : (
                                <label
                                    htmlFor={`employee-create-login-${employee?.id ?? 'new'}`}
                                    className="flex cursor-pointer items-start gap-3 rounded-md border px-3 py-3"
                                >
                                <Checkbox
                                    id={`employee-create-login-${employee?.id ?? 'new'}`}
                                    className="mt-0.5"
                                    checked={form.data.create_login}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'create_login',
                                            checked === true,
                                        )
                                    }
                                />
                                    <span className="space-y-1">
                                        <span className="block text-sm font-medium">
                                            Create login access
                                        </span>
                                        <span className="block text-sm text-muted-foreground">
                                            Creates an employee portal account
                                            using the work email above.
                                        </span>
                                    </span>
                                </label>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor={`employee-status-${employee?.id ?? 'new'}`}>
                                Status
                            </Label>
                            <Select
                                value={form.data.employment_status}
                                onValueChange={(value) =>
                                    form.setData('employment_status', value)
                                }
                            >
                                <SelectTrigger
                                    id={`employee-status-${employee?.id ?? 'new'}`}
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {titleCase(status)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.employment_status} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor={`employee-department-${employee?.id ?? 'new'}`}>
                                Department
                            </Label>
                            <Select
                                value={nullableSelectValue(
                                    form.data.department_id,
                                )}
                                onValueChange={(value) =>
                                    form.setData(
                                        'department_id',
                                        value === 'none' ? null : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger
                                    id={`employee-department-${employee?.id ?? 'new'}`}
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
                            <Label htmlFor={`employee-designation-${employee?.id ?? 'new'}`}>
                                Designation
                            </Label>
                            <Select
                                value={nullableSelectValue(
                                    form.data.designation_id,
                                )}
                                onValueChange={(value) =>
                                    form.setData(
                                        'designation_id',
                                        value === 'none' ? null : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger
                                    id={`employee-designation-${employee?.id ?? 'new'}`}
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

                        <div className="space-y-2">
                            <Label htmlFor={`employee-manager-${employee?.id ?? 'new'}`}>
                                Manager
                            </Label>
                            <Select
                                value={nullableSelectValue(form.data.manager_id)}
                                onValueChange={(value) =>
                                    form.setData(
                                        'manager_id',
                                        value === 'none' ? null : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger
                                    id={`employee-manager-${employee?.id ?? 'new'}`}
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select manager" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        No manager
                                    </SelectItem>
                                    {managerOptions.map((manager) => (
                                        <SelectItem
                                            key={manager.id}
                                            value={manager.id.toString()}
                                        >
                                            {manager.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.manager_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor={`employee-joined-on-${employee?.id ?? 'new'}`}>
                                Joined on
                            </Label>
                            <Input
                                id={`employee-joined-on-${employee?.id ?? 'new'}`}
                                type="date"
                                value={form.data.joined_on}
                                onChange={(event) =>
                                    form.setData('joined_on', event.target.value)
                                }
                            />
                            <InputError message={form.errors.joined_on} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            Save employee
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteEmployeeDialog({ employee }: { employee: Employee }) {
    const [open, setOpen] = useState(false);
    const form = useForm({});

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Delete employee"
                >
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete employee</DialogTitle>
                    <DialogDescription>
                        This removes {employee.name} from employee records.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={form.processing}
                        onClick={() => form.delete(`/employees/${employee.id}`)}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function EmployeesIndex({
    employees,
    departments,
    designations,
    managers,
    statuses,
}: EmployeesIndexProps) {
    return (
        <>
            <Head title="Employees" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Employees
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage employee identities, assignments, reporting,
                            and login links.
                        </p>
                    </div>
                    <EmployeeDialog
                        departments={departments}
                        designations={designations}
                        managers={managers}
                        statuses={statuses}
                    />
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[1100px] text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Employee
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Work email
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Department
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Designation
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Manager
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Login
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Joined
                                </th>
                                <th className="w-24 px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {employees.map((employee) => (
                                <tr key={employee.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {employee.name}
                                        </div>
                                        <div className="text-muted-foreground">
                                            {employee.employee_code}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {employee.work_email ?? 'Not set'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {employee.department ?? 'Unassigned'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {employee.designation ?? 'Unassigned'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {employee.manager ?? 'No manager'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {titleCase(employee.employment_status)}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {employee.user_email ?? 'No login'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {employee.joined_on ?? 'Not set'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            <EmployeeDialog
                                                employee={employee}
                                                departments={departments}
                                                designations={designations}
                                                managers={managers}
                                                statuses={statuses}
                                            />
                                            <DeleteEmployeeDialog
                                                employee={employee}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {employees.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={9}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No employees yet.
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

EmployeesIndex.layout = {
    breadcrumbs: [
        {
            title: 'People',
            href: '/employees',
        },
        {
            title: 'Employees',
            href: '/employees',
        },
    ],
};
