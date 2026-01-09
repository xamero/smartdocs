import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Office } from '@/types';
import { Head } from '@inertiajs/react';

interface OfficesIndexProps {
    offices: Office[];
    scope?: 'all' | 'mine';
}

export default function OfficesIndex({ offices, scope = 'all' }: OfficesIndexProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Offices',
            href: scope === 'mine' ? '/offices/my' : '/offices',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Offices" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {scope === 'mine' ? 'My Office Dashboard' : 'Offices'}
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {scope === 'mine'
                            ? 'Your office and its child offices with document workloads.'
                            : 'Overview of offices and their document workloads.'}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Office Overview</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-2 text-xs text-muted-foreground mb-4">
                            <p>
                                <span className="font-medium text-foreground">Legend:</span>{' '}
                                <Badge variant="outline" className="ml-1">
                                    Total
                                </Badge>{' '}
                                <Badge variant="outline" className="ml-1">
                                    In Transit
                                </Badge>{' '}
                                <Badge variant="outline" className="ml-1">
                                    In Action
                                </Badge>{' '}
                                <Badge variant="outline" className="ml-1">
                                    Completed
                                </Badge>{' '}
                                <Badge variant="outline" className="ml-1">
                                    Archived
                                </Badge>
                            </p>
                        </div>
                        <div className="overflow-x-auto rounded-lg border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted/50">
                                    <tr>
                                        <th className="px-4 py-2 text-left font-medium">Office</th>
                                        <th className="px-4 py-2 text-left font-medium">Code</th>
                                        <th className="px-4 py-2 text-left font-medium">Parent</th>
                                        <th className="px-4 py-2 text-left font-medium">Status</th>
                                        <th className="px-4 py-2 text-left font-medium">Documents</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {offices.map((office) => (
                                        <tr
                                            key={office.id}
                                            className="border-t hover:bg-muted/40 transition-colors"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex flex-col">
                                                    <span className="font-medium">{office.name}</span>
                                                    {office.description && (
                                                        <span className="text-xs text-muted-foreground">
                                                            {office.description}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-xs font-mono text-muted-foreground">
                                                {office.code || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground">
                                                    {office.parent ? office.parent.name : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={office.is_active ? 'outline' : 'destructive'}
                                                    className="text-xs"
                                                >
                                                    {office.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-2">
                                                    <Badge variant="outline" className="text-xs">
                                                        Total:{' '}
                                                        {office.documents_total_count ?? 0}
                                                    </Badge>
                                                    <Badge variant="outline" className="text-xs">
                                                        In transit:{' '}
                                                        {office.documents_in_transit_count ?? 0}
                                                    </Badge>
                                                    <Badge variant="outline" className="text-xs">
                                                        In action:{' '}
                                                        {office.documents_in_action_count ?? 0}
                                                    </Badge>
                                                    <Badge variant="outline" className="text-xs">
                                                        Completed:{' '}
                                                        {office.documents_completed_count ?? 0}
                                                    </Badge>
                                                    <Badge variant="outline" className="text-xs">
                                                        Archived:{' '}
                                                        {office.documents_archived_count ?? 0}
                                                    </Badge>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Separator className="my-2" />
            </div>
        </AppLayout>
    );
}

