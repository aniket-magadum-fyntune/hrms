import { Head, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppearance } from '@/hooks/use-appearance';
import {
    applyThemeVariables,
    isHexColor,
    organizationThemeVariables,
} from '@/lib/theme';
import type { Organization } from '@/types';

type OrganizationForm = {
    name: string;
    legal_name: string;
    email: string;
    phone: string;
    website: string;
    address: string;
    primary_color: string;
    sidebar_color: string;
};

export default function OrganizationSettings({
    organization,
}: {
    organization: Organization;
}) {
    const { resolvedAppearance } = useAppearance();
    const form = useForm<OrganizationForm>({
        name: organization.name,
        legal_name: organization.legal_name ?? '',
        email: organization.email ?? '',
        phone: organization.phone ?? '',
        website: organization.website ?? '',
        address: organization.address ?? '',
        primary_color: organization.primary_color,
        sidebar_color: organization.sidebar_color,
    });
    const previewThemeVariables = useMemo(() => {
        if (
            !isHexColor(form.data.primary_color) ||
            !isHexColor(form.data.sidebar_color)
        ) {
            return null;
        }

        return organizationThemeVariables({
            primary_color: form.data.primary_color,
            sidebar_color: form.data.sidebar_color,
        });
    }, [form.data.primary_color, form.data.sidebar_color]);

    useEffect(() => {
        if (!previewThemeVariables || resolvedAppearance === 'dark') {
            return;
        }

        return applyThemeVariables(previewThemeVariables);
    }, [previewThemeVariables, resolvedAppearance]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.put('/settings/organization', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Organization settings" />

            <h1 className="sr-only">Organization settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Organization"
                    description="Configure the organization profile and site branding"
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="organization-name">
                            Organization name
                        </Label>
                        <Input
                            id="organization-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                            autoComplete="organization"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="organization-legal-name">
                            Legal name
                        </Label>
                        <Input
                            id="organization-legal-name"
                            value={form.data.legal_name}
                            onChange={(event) =>
                                form.setData('legal_name', event.target.value)
                            }
                            autoComplete="organization"
                        />
                        <InputError message={form.errors.legal_name} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="organization-email">Email</Label>
                            <Input
                                id="organization-email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                autoComplete="email"
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="organization-phone">Phone</Label>
                            <Input
                                id="organization-phone"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                                autoComplete="tel"
                            />
                            <InputError message={form.errors.phone} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="organization-website">Website</Label>
                        <Input
                            id="organization-website"
                            type="url"
                            value={form.data.website}
                            onChange={(event) =>
                                form.setData('website', event.target.value)
                            }
                            placeholder="https://example.com"
                            autoComplete="url"
                        />
                        <InputError message={form.errors.website} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="organization-address">Address</Label>
                        <textarea
                            id="organization-address"
                            value={form.data.address}
                            onChange={(event) =>
                                form.setData('address', event.target.value)
                            }
                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        />
                        <InputError message={form.errors.address} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="organization-primary-color">
                                Primary color
                            </Label>
                            <div className="flex gap-2">
                                <Input
                                    id="organization-primary-color"
                                    type="color"
                                    value={form.data.primary_color}
                                    onChange={(event) =>
                                        form.setData(
                                            'primary_color',
                                            event.target.value,
                                        )
                                    }
                                    className="h-9 w-14 p-1"
                                />
                                <Input
                                    value={form.data.primary_color}
                                    onChange={(event) =>
                                        form.setData(
                                            'primary_color',
                                            event.target.value,
                                        )
                                    }
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                />
                            </div>
                            <InputError message={form.errors.primary_color} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="organization-sidebar-color">
                                Sidebar color
                            </Label>
                            <div className="flex gap-2">
                                <Input
                                    id="organization-sidebar-color"
                                    type="color"
                                    value={form.data.sidebar_color}
                                    onChange={(event) =>
                                        form.setData(
                                            'sidebar_color',
                                            event.target.value,
                                        )
                                    }
                                    className="h-9 w-14 p-1"
                                />
                                <Input
                                    value={form.data.sidebar_color}
                                    onChange={(event) =>
                                        form.setData(
                                            'sidebar_color',
                                            event.target.value,
                                        )
                                    }
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                />
                            </div>
                            <InputError message={form.errors.sidebar_color} />
                        </div>
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        Save organization
                    </Button>
                </form>
            </div>
        </>
    );
}

OrganizationSettings.layout = {
    breadcrumbs: [
        {
            title: 'Settings',
            href: '/settings/organization',
        },
        {
            title: 'Organization settings',
            href: '/settings/organization',
        },
    ],
};
