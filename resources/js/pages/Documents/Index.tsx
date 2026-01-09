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
import AppLayout from '@/layouts/app-layout';
import { create, index, show } from '@/routes/documents';
import { type BreadcrumbItem, type Document, type Office } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/react';
import { FileText, Plus, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface DocumentsIndexProps {
    documents: {
        data: Document[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        status?: string;
        document_type?: string;
        priority?: string;
        office_id?: string;
        overdue?: boolean;
        due_this_week?: boolean;
    };
    offices: Office[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documents',
        href: index().url,
    },
];

export default function DocumentsIndex({
    documents: documentsData,
    filters,
    offices,
}: DocumentsIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [documentType, setDocumentType] = useState(filters.document_type || '');
    const [priority, setPriority] = useState(filters.priority || '');
    const [officeId, setOfficeId] = useState(filters.office_id || '');

    const handleFilter = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            index().url,
            {
                search: search || undefined,
                status: status || undefined,
                document_type: documentType || undefined,
                priority: priority || undefined,
                office_id: officeId || undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setDocumentType('');
        setPriority('');
        setOfficeId('');
        router.get(index().url, {}, { preserveState: true, replace: true });
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Documents" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {status === 'archived' ? 'Archived Documents' : 'Documents'}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {status === 'archived'
                                ? 'View and manage archived documents'
                                : 'Manage and track all documents'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Form action="/documents/import" method="post" encType="multipart/form-data">
                            {({ processing, setData, errors }) => (
                                <div className="flex items-center gap-2">
                                    <Input
                                        type="file"
                                        name="file"
                                        accept=".csv,text/csv"
                                        className="max-w-xs cursor-pointer"
                                        onChange={(e) => {
                                            const file = e.currentTarget.files?.[0];
                                            if (file) {
                                                setData('file', file);
                                            }
                                        }}
                                    />
                                    <Button type="submit" variant="outline" size="sm" disabled={processing}>
                                        {processing ? 'Importing…' : 'Import CSV'}
                                    </Button>
                                    {errors.file && (
                                        <p className="text-xs text-destructive">{errors.file}</p>
                                    )}
                                </div>
                            )}
                        </Form>
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                New Document
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between gap-4">
                            <CardTitle>Filters</CardTitle>
                            <div className="flex items-center gap-2 text-xs">
                                <Button
                                    type="button"
                                    variant={status !== 'archived' && !filters.overdue && !filters.due_this_week ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => {
                                        setStatus('');
                                        router.get(
                                            index().url,
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
                                    variant={status === 'archived' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => {
                                        setStatus('archived');
                                        router.get(
                                            index().url,
                                            {
                                                ...filters,
                                                status: 'archived',
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
                                            index().url,
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
                                            index().url,
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
                        <form onSubmit={handleFilter} className="flex flex-wrap gap-4">
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

                            <Select value={officeId || 'all'} onValueChange={(value) => setOfficeId(value === 'all' ? '' : value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Office" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Offices</SelectItem>
                                    {offices.map((office) => (
                                        <SelectItem key={office.id} value={String(office.id)}>
                                            {office.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="flex gap-2">
                                <Button type="submit">Filter</Button>
                                <Button type="button" variant="outline" onClick={clearFilters}>
                                    Clear
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            Documents ({documentsData.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {documentsData.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <FileText className="mb-4 size-12 text-muted-foreground" />
                                <p className="text-muted-foreground text-lg font-medium">
                                    No documents found
                                </p>
                                <p className="text-muted-foreground text-sm">
                                    Get started by creating a new document
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {documentsData.data.map((document) => (
                                    <Link
                                        key={document.id}
                                        href={show(document.id).url}
                                        className={`block rounded-lg border p-4 transition-colors hover:bg-accent ${
                                            document.is_archived ? 'opacity-75 bg-muted/30' : ''
                                        }`}
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2 mb-2">
                                                    <h3 className={`font-semibold ${document.is_archived ? 'text-muted-foreground' : ''}`}>
                                                        {document.title}
                                                    </h3>
                                                    {document.is_archived && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            Archived
                                                        </Badge>
                                                    )}
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
                                                        {document.current_office && (
                                                            <span>
                                                                <span className="font-medium">Office:</span>{' '}
                                                                {document.current_office.name}
                                                            </span>
                                                        )}
                                                        {document.date_received && (
                                                            <span>
                                                                <span className="font-medium">Received:</span>{' '}
                                                                {new Date(document.date_received).toLocaleDateString()}
                                                            </span>
                                                        )}
                                                        {document.is_archived && document.archived_at && (
                                                            <span className="text-muted-foreground/80">
                                                                <span className="font-medium">Archived:</span>{' '}
                                                                {new Date(document.archived_at).toLocaleDateString()}
                                                            </span>
                                                        )}
                                                        {!document.is_archived && (
                                                            <span>
                                                                <span className="font-medium">Aging:</span>{' '}
                                                                {getAgingLabel(document)}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}

                        {documentsData.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-center gap-2">
                                {documentsData.links.map((link, index) => (
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
            </div>
        </AppLayout>
    );
}
