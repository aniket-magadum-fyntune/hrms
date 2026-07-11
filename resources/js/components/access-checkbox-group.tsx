import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

type AccessCheckboxGroupProps = {
    items: string[];
    value: string[];
    onChange: (value: string[]) => void;
    emptyText: string;
};

export function AccessCheckboxGroup({
    items,
    value,
    onChange,
    emptyText,
}: AccessCheckboxGroupProps) {
    const toggle = (item: string, checked: boolean) => {
        onChange(
            checked
                ? [...value, item]
                : value.filter((selected) => selected !== item),
        );
    };

    if (items.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyText}</p>;
    }

    return (
        <div className="grid max-h-56 gap-3 overflow-y-auto rounded-md border p-3 sm:grid-cols-2">
            {items.map((item) => (
                <Label
                    key={item}
                    className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                >
                    <Checkbox
                        checked={value.includes(item)}
                        onCheckedChange={(checked) =>
                            toggle(item, checked === true)
                        }
                    />
                    <span className="break-all">{item}</span>
                </Label>
            ))}
        </div>
    );
}
