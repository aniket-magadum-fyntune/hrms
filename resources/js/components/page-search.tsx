import { router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import type { ReactElement } from 'react';
import {
    cloneElement,
    isValidElement,
    useEffect,
    useMemo,
    useState,
} from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { searchableNavItems } from '@/lib/navigation';
import { toUrl } from '@/lib/utils';
import type { Auth } from '@/types';

function isTypingTarget(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    return (
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT' ||
        target.isContentEditable
    );
}

export function PageSearch({ trigger }: { trigger?: ReactElement }) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [selectedIndex, setSelectedIndex] = useState(0);
    const pages = useMemo(
        () => searchableNavItems(auth.isSuperAdmin),
        [auth.isSuperAdmin],
    );
    const filteredPages = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        if (normalizedQuery === '') {
            return pages;
        }

        return pages.filter((page) =>
            page.title.toLowerCase().includes(normalizedQuery),
        );
    }, [pages, query]);

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                setOpen((value) => !value);

                return;
            }

            if (event.key === '/' && !isTypingTarget(event.target)) {
                event.preventDefault();
                setOpen(true);
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    const visitPage = (href: Parameters<typeof toUrl>[0]) => {
        setOpen(false);
        setQuery('');
        setSelectedIndex(0);
        router.visit(toUrl(href));
    };

    const handleSearchKeyDown = (
        event: React.KeyboardEvent<HTMLInputElement>,
    ) => {
        if (event.key === 'Enter' && filteredPages[selectedIndex]) {
            event.preventDefault();
            visitPage(filteredPages[selectedIndex].href);

            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setSelectedIndex((current) =>
                Math.min(current + 1, filteredPages.length - 1),
            );

            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setSelectedIndex((current) => Math.max(current - 1, 0));
        }
    };

    const defaultTrigger = (
        <button type="button">
            <Search />
            <span>Search</span>
        </button>
    );
    const triggerElement = trigger ?? defaultTrigger;
    const triggerWithType = isValidElement<{ type?: string }>(triggerElement)
        ? cloneElement(triggerElement, {
              type: triggerElement.props.type ?? 'button',
          })
        : triggerElement;

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                setOpen(value);

                if (!value) {
                    setQuery('');
                    setSelectedIndex(0);
                }
            }}
        >
            <DialogTrigger asChild>{triggerWithType}</DialogTrigger>
            <DialogContent className="gap-0 p-0 sm:max-w-lg">
                <DialogHeader className="sr-only">
                    <DialogTitle>Search pages</DialogTitle>
                    <DialogDescription>
                        Search for a page and open it.
                    </DialogDescription>
                </DialogHeader>
                <div className="flex items-center gap-2 border-b px-4 py-3">
                    <Search className="size-4 text-muted-foreground" />
                    <Input
                        value={query}
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setSelectedIndex(0);
                        }}
                        onKeyDown={handleSearchKeyDown}
                        placeholder="Search pages"
                        autoFocus
                        className="h-8 border-0 px-0 shadow-none focus-visible:ring-0"
                    />
                    <kbd className="rounded border bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground">
                        Esc
                    </kbd>
                </div>
                <div className="max-h-80 overflow-y-auto p-2">
                    {filteredPages.map((page, index) => (
                        <button
                            key={page.title}
                            type="button"
                            onClick={() => visitPage(page.href)}
                            onMouseEnter={() => setSelectedIndex(index)}
                            data-selected={index === selectedIndex}
                            className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none data-[selected=true]:bg-accent data-[selected=true]:text-accent-foreground"
                        >
                            {page.icon && (
                                <page.icon className="size-4 text-muted-foreground" />
                            )}
                            <span className="font-medium">{page.title}</span>
                            <span className="ml-auto text-xs text-muted-foreground">
                                {toUrl(page.href)}
                            </span>
                        </button>
                    ))}
                    {filteredPages.length === 0 && (
                        <div className="px-3 py-8 text-center text-sm text-muted-foreground">
                            No pages found.
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
