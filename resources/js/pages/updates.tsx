import { Head } from '@inertiajs/react';
import { BadgeCheck, Sparkles, Wrench } from 'lucide-react';

const updates = [
    {
        title: 'Departments and designations',
        description:
            'Employee profiles now support department and designation assignments.',
        icon: BadgeCheck,
    },
    {
        title: 'Organization branding',
        description:
            'Super Admins can configure organization details and light-mode colors.',
        icon: Sparkles,
    },
    {
        title: 'Navigation cleanup',
        description:
            'Sidebar groups, breadcrumbs, and page search make admin pages easier to reach.',
        icon: Wrench,
    },
];

export default function Updates() {
    return (
        <>
            <Head title="Updates" />

            <div className="space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Updates
                    </h1>
                    <p className="text-muted-foreground">
                        A lightweight changelog for HRMS improvements and
                        upcoming work.
                    </p>
                </div>

                <div className="space-y-3">
                    {updates.map((update) => (
                        <section
                            key={update.title}
                            className="flex gap-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <update.icon className="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                            <div>
                                <h2 className="font-medium">{update.title}</h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {update.description}
                                </p>
                            </div>
                        </section>
                    ))}
                </div>
            </div>
        </>
    );
}

Updates.layout = {
    breadcrumbs: [
        {
            title: 'Updates',
            href: '/updates',
        },
    ],
};
