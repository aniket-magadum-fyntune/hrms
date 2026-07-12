import { Head } from '@inertiajs/react';
import { BookOpen, CircleHelp, Mail, MessageSquareText } from 'lucide-react';

const helpTopics = [
    {
        title: 'People directory',
        description:
            'Find users, departments, designations, and profile ownership rules.',
        icon: BookOpen,
    },
    {
        title: 'Access and roles',
        description:
            'Understand how roles map to permissions and what Super Admins can manage.',
        icon: CircleHelp,
    },
    {
        title: 'Account support',
        description:
            'Get help with sign-in, security settings, password changes, and passkeys.',
        icon: MessageSquareText,
    },
];

export default function Help() {
    return (
        <>
            <Head title="Help center" />

            <div className="space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Help center
                    </h1>
                    <p className="text-muted-foreground">
                        Quick guidance for the HRMS areas your team will use
                        most.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {helpTopics.map((topic) => (
                        <section
                            key={topic.title}
                            className="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <topic.icon className="mb-4 h-5 w-5 text-muted-foreground" />
                            <h2 className="font-medium">{topic.title}</h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {topic.description}
                            </p>
                        </section>
                    ))}
                </div>

                <section className="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex items-start gap-3">
                        <Mail className="mt-0.5 h-5 w-5 text-muted-foreground" />
                        <div>
                            <h2 className="font-medium">Need admin help?</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Contact your HRMS administrator for access
                                changes, organization settings, or employee
                                profile corrections.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

Help.layout = {
    breadcrumbs: [
        {
            title: 'Help center',
            href: '/help',
        },
    ],
};
