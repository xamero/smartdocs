import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { show } from '@/routes/documents';
import { type BreadcrumbItem, type Document, type Office } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileText, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface OfficesIndexProps {
    offices: Office[];
    scope?: 'all' | 'mine';
    documents?: {
        data: Document[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters?: {
        search?: string;
        status?: string;
        document_type?: string;
        priority?: string;
        overdue?: boolean;
        due_this_week?: boolean;
    };
}

export default function OfficesIndex({ offices, scope = 'all', documents, filters = {} }: OfficesIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [documentType, setDocumentType] = useState(filters.document_type || '');
    const [priority, setPriority] = useState(filters.priority || '');

    const handleFilter = (e: FormEvent) => {
        e.preventDefault();
        const currentUrl = scope === 'mine' ? '/offices/my' : '/offices';
        router.get(
            currentUrl,
            {
                search: search || undefined,
                status: status || undefined,
                document_type: documentType || undefined,
                priority: priority || undefined,
                overdue: filters.overdue || undefined,
                due_this_week: filters.due_this_week || undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setDocumentType('');
        setPriority('');
        const currentUrl = scope === 'mine' ? '/offices/my' : '/offices';
        router.get(currentUrl, {}, { preserveState: true, replace: true });
    };
    const getStatusBadgeVariant = (status: Document['status']) => {
        const variants: Record<Document['status'], 'default' | 'secondary' | 'destructive' | 'outline'> = {
            draft: 'outline',
            registered: 'secondary',
            in_transit: 'default',
            received: 'default',
            in_action: 'default',
            completed: 'default',
            archived: 'secondary',
            returned: 'destructive',
        };
        return variants[status] || 'outline';
    };

    const getPriorityBadgeVariant = (priority: Document['priority']) => {
        const variants: Record<Document['priority'], 'default' | 'secondary' | 'destructive' | 'outline'> = {
            low: 'outline',
            normal: 'secondary',
            high: 'default',
            urgent: 'destructive',
        };
        return variants[priority] || 'outline';
    };

    const getAgingLabel = (document: Document) => {
        if (!document.date_received) {
            return '—';
        }

        const received = new Date(document.date_received);
        const now = new Date();
        const msPerDay = 1000 * 60 * 60 * 24;
        const days = Math.floor((now.getTime() - received.getTime()) / msPerDay);

        if (!document.date_due) {
            return `${days} day${days === 1 ? '' : 's'} since received`;
        }

        const due = new Date(document.date_due);
        const diffToDue = Math.floor((due.getTime() - now.getTime()) / msPerDay);

        if (diffToDue < 0) {
            const overdue = Math.abs(diffToDue);
            return `${overdue} day${overdue === 1 ? '' : 's'} overdue`;
        }

        if (diffToDue === 0) {
            return 'Due today';
        }

        return `Due in ${diffToDue} day${diffToDue === 1 ? '' : 's'}`;
    };
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

                {scope === 'mine' && documents && (
                    <>
                        <Separator className="my-2" />
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>
                                        My Office Documents ({documents.total})
                                    </CardTitle>
                                    <div className="flex items-center gap-2 text-xs">
                                        <Button
                                            type="button"
                                            variant={!filters.overdue && !filters.due_this_week && !filters.status ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => {
                                                router.get(
                                                    '/offices/my',
                                                    {
                                                        ...filters,
                                                        status: undefined,
                                                        overdue: undefined,
                                                        due_this_week: undefined,
                                                    },
                                                    { preserveState: true, replace: true }
                                                );
                                            }}
                                        >
                                            Active
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={filters.status === 'archived' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => {
                                                router.get(
                                                    '/offices/my',
                                                    {
                                                        ...filters,
                                                        status: filters.status === 'archived' ? undefined : 'archived',
                                                        overdue: undefined,
                                                        due_this_week: undefined,
                                                    },
                                                    { preserveState: true, replace: true }
                                                );
                                            }}
                                        >
                                            Archived
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={filters.overdue ? 'destructive' : 'outline'}
                                            size="sm"
                                            onClick={() => {
                                                router.get(
                                                    '/offices/my',
                                                    {
                                                        ...filters,
                                                        overdue: filters.overdue ? undefined : true,
                                                        due_this_week: undefined,
                                                        status: undefined,
                                                    },
                                                    { preserveState: true, replace: true }
                                                );
                                            }}
                                        >
                                            Overdue
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={filters.due_this_week ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => {
                                                router.get(
                                                    '/offices/my',
                                                    {
                                                        ...filters,
                                                        due_this_week: filters.due_this_week ? undefined : true,
                                                        overdue: undefined,
                                                        status: undefined,
                                                    },
                                                    { preserveState: true, replace: true }
                                                );
                                            }}
                                        >
                                            Due This Week
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleFilter} className="flex flex-wrap gap-4 mb-6">
                                    <div className="flex-1 min-w-[200px]">
                                        <div className="relative">
                                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                type="text"
                                                placeholder="Search by tracking number, title..."
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                className="pl-9"
                                            />
                                        </div>
                                    </div>

                                    <Select value={status || 'all'} onValueChange={(value) => setStatus(value === 'all' ? '' : value)}>
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Status</SelectItem>
                                            <SelectItem value="draft">Draft</SelectItem>
                                            <SelectItem value="registered">Registered</SelectItem>
                                            <SelectItem value="in_transit">In Transit</SelectItem>
                                            <SelectItem value="received">Received</SelectItem>
                                            <SelectItem value="in_action">In Action</SelectItem>
                                            <SelectItem value="completed">Completed</SelectItem>
                                            <SelectItem value="archived">Archived</SelectItem>
                                            <SelectItem value="returned">Returned</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <Select value={documentType || 'all'} onValueChange={(value) => setDocumentType(value === 'all' ? '' : value)}>
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Types</SelectItem>
                                            <SelectItem value="incoming">Incoming</SelectItem>
                                            <SelectItem value="outgoing">Outgoing</SelectItem>
                                            <SelectItem value="internal">Internal</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <Select value={priority || 'all'} onValueChange={(value) => setPriority(value === 'all' ? '' : value)}>
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Priority" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Priorities</SelectItem>
                                            <SelectItem value="low">Low</SelectItem>
                                            <SelectItem value="normal">Normal</SelectItem>
                                            <SelectItem value="high">High</SelectItem>
                                            <SelectItem value="urgent">Urgent</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <div className="flex gap-2">
                                        <Button type="submit">Filter</Button>
                                        <Button type="button" variant="outline" onClick={clearFilters}>
                                            Clear
                                        </Button>
                                    </div>
                                </form>
                                {documents.data.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-12 text-center">
                                        <FileText className="mb-4 size-12 text-muted-foreground" />
                                        <p className="text-muted-foreground text-lg font-medium">
                                            No documents found
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            There are no documents in your office at the moment
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {documents.data.map((document) => (
                                            <Link
                                                key={document.id}
                                                href={show(document.id).url}
                                                className="block rounded-lg border p-4 transition-colors hover:bg-accent cursor-pointer"
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 mb-2">
                                                            <h3 className="font-semibold">
                                                                {document.title}
                                                            </h3>
                                                            <Badge variant={getStatusBadgeVariant(document.status)}>
                                                                {document.status.replace('_', ' ')}
                                                            </Badge>
                                                            <Badge variant={getPriorityBadgeVariant(document.priority)}>
                                                                {document.priority}
                                                            </Badge>
                                                        </div>
                                                        <div className="text-muted-foreground text-sm space-y-1">
                                                            <p>
                                                                <span className="font-medium">Tracking:</span>{' '}
                                                                {document.tracking_number}
                                                            </p>
                                                            {document.description && (
                                                                <p className="line-clamp-2">
                                                                    {document.description}
                                                                </p>
                                                            )}
                                                            <div className="flex flex-wrap items-center gap-4 mt-2">
                                                                <span>
                                                                    <span className="font-medium">Type:</span>{' '}
                                                                    {document.document_type}
                                                                </span>
                                                                {document.date_received && (
                                                                    <span>
                                                                        <span className="font-medium">Received:</span>{' '}
                                                                        {new Date(document.date_received).toLocaleDateString()}
                                                                    </span>
                                                                )}
                                                                <span>
                                                                    <span className="font-medium">Aging:</span>{' '}
                                                                    {getAgingLabel(document)}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                )}

                                {documents.last_page > 1 && (
                                    <div className="mt-6 flex items-center justify-center gap-2">
                                        {documents.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-2 rounded-md text-sm ${
                                                    link.active
                                                        ? 'bg-primary text-primary-foreground'
                                                        : link.url
                                                          ? 'bg-secondary hover:bg-secondary/80'
                                                          : 'bg-secondary/50 cursor-not-allowed opacity-50'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

