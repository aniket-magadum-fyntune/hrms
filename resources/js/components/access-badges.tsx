import { Badge } from '@/components/ui/badge';

type AccessBadgesProps = {
    values: string[];
    emptyText: string;
};

export function AccessBadges({ values, emptyText }: AccessBadgesProps) {
    if (values.length === 0) {
        return <span className="text-sm text-muted-foreground">{emptyText}</span>;
    }

    return (
        <div className="flex flex-wrap gap-1.5">
            {values.map((value) => (
                <Badge key={value} variant="secondary">
                    {value}
                </Badge>
            ))}
        </div>
    );
}
