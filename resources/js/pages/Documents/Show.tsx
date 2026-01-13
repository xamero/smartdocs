import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { edit, index, route, show, actions } from '@/routes/documents';
import { type BreadcrumbItem, type Document, type DocumentRouting } from '@/types';
import { Form, Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    FileText,
    QrCode,
    Route,
    User,
    Building2,
    Clock,
    CheckCircle,
} from 'lucide-react';
import { useState, FormEvent } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
// Date formatting helper
const formatRelativeTime = (date: string) => {
    const now = new Date();
    const then = new Date(date);
    const diffInSeconds = Math.floor((now.getTime() - then.getTime()) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
    if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 604800)} weeks ago`;
    if (diffInSeconds < 31536000) return `${Math.floor(diffInSeconds / 2592000)} months ago`;
    return `${Math.floor(diffInSeconds / 31536000)} years ago`;
};

// Calculate routing time between routed_at and received_at
const calculateRoutingTime = (routedAt: string, receivedAt?: string): string | null => {
    if (!receivedAt) return null;

    const routed = new Date(routedAt);
    const received = new Date(receivedAt);
    const diffInMs = received.getTime() - routed.getTime();
    const diffInHours = diffInMs / (1000 * 60 * 60);
    const diffInDays = diffInMs / (1000 * 60 * 60 * 24);

    if (diffInHours < 1) {
        const minutes = Math.floor(diffInMs / (1000 * 60));
        return `${minutes} minute${minutes !== 1 ? 's' : ''}`;
    }
    if (diffInDays < 1) {
        return `${Math.round(diffInHours * 10) / 10} hour${diffInHours !== 1 ? 's' : ''}`;
    }
    return `${Math.round(diffInDays * 10) / 10} day${diffInDays !== 1 ? 's' : ''}`;
};

interface DocumentsShowProps {
    document: Document;
    offices: Array<{ id: number; name: string }>;
    receivableRouting?: DocumentRouting | null;
    isOriginatingOffice?: boolean;
    isIncomingToOffice?: boolean;
    outgoingRouting?: DocumentRouting | null;
    isInTransitToOtherOffice?: boolean;
}

export default function DocumentsShow({ document, offices, receivableRouting, isOriginatingOffice, isIncomingToOffice, outgoingRouting, isInTransitToOtherOffice }: DocumentsShowProps) {
    const { auth } = usePage<{ auth: { user: { id: number; office_id?: number | null; role: string } } }>().props;
    const [routeDialogOpen, setRouteDialogOpen] = useState(false);
    const [actionDialogOpen, setActionDialogOpen] = useState(false);

    const routeForm = useForm({
        to_office_id: null as number | null,
        to_office_ids: [] as number[],
        remarks: '',
        create_copies: false,
    });

    const actionForm = useForm({
        action_type: '' as 'approve' | 'note' | 'comply' | 'sign' | 'return' | 'forward' | '',
        remarks: '',
        memo_file: null as File | null,
        is_office_head_approval: false,
    });

    const handleRouteSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!routeForm.data.to_office_id && (!routeForm.data.to_office_ids || routeForm.data.to_office_ids.length === 0)) {
            return;
        }

        // Prepare the data to send
        const formData: any = {
            remarks: routeForm.data.remarks || '',
        };

        if (routeForm.data.create_copies && routeForm.data.to_office_ids && routeForm.data.to_office_ids.length > 0) {
            formData.to_office_ids = routeForm.data.to_office_ids;
            formData.create_copies = true;
        } else if (routeForm.data.to_office_id) {
            formData.to_office_id = routeForm.data.to_office_id;
            formData.create_copies = false;
        }

        routeForm.transform(() => formData);
        routeForm.post(route.url(document.id), {
            preserveScroll: true,
            onSuccess: () => {
                setRouteDialogOpen(false);
                routeForm.reset();
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        });
    };

    const handleActionSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!actionForm.data.action_type) {
            return;
        }

        actionForm.post(actions.store.url(document.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setActionDialogOpen(false);
                actionForm.reset();
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        });
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

    const getActionTypeLabel = (type: string) => {
        const labels: Record<string, string> = {
            approve: 'Approved',
            note: 'Noted',
            comply: 'Complied',
            sign: 'Signed',
            return: 'Returned',
            forward: 'Forwarded',
        };
        return labels[type] || type;
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Documents',
            href: index().url,
        },
        {
            title: document.title,
            href: show(document.id).url,
        },
    ];

    // Determine if Route Document and Add Action should be disabled
    // Disable if:
    // 1. Document is not received (status is not 'received')
    // 2. Document is in transit to another office
    const isDocumentReceived = document.status === 'received';
    const shouldDisableQuickActions = !isDocumentReceived || (isInTransitToOtherOffice ?? false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={document.title} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <h1 className="text-2xl font-semibold">{document.title}</h1>
                                <Badge variant={getStatusBadgeVariant(document.status)}>
                                    {document.status.replace('_', ' ')}
                                </Badge>
                                <Badge variant={getPriorityBadgeVariant(document.priority)}>
                                    {document.priority}
                                </Badge>
                                {isOriginatingOffice && (
                                    <Badge variant="outline">From your office</Badge>
                                )}
                                {!isOriginatingOffice && isIncomingToOffice && (
                                    <Badge variant="secondary">Incoming to your office</Badge>
                                )}
                            </div>
                            <p className="text-muted-foreground text-sm">
                                Tracking Number: <span className="font-mono font-medium">{document.tracking_number}</span>
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* Edit button - only for creating office or admin */}
                        {(auth.user?.role === 'admin' ||
                            (document.creator && document.creator.office_id === auth.user?.office_id)) && (
                                <Button variant="outline" asChild>
                                    <Link href={edit(document.id).url}>Edit</Link>
                                </Button>
                            )}

                        {/* Archive/Restore button - only for creating office or admin */}
                        {(auth.user?.role === 'admin' ||
                            (document.creator && document.creator.office_id === auth.user?.office_id)) && (
                                <>
                                    {!document.is_archived && (
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                if (confirm('Archive this document? It will be moved to the archive.')) {
                                                    router.post(`/documents/${document.id}/archive`);
                                                }
                                            }}
                                        >
                                            Archive
                                        </Button>
                                    )}
                                    {document.is_archived && (
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                if (confirm('Restore this document from the archive?')) {
                                                    router.post(`/documents/${document.id}/restore`);
                                                }
                                            }}
                                        >
                                            Restore
                                        </Button>
                                    )}
                                </>
                            )}

                        {/* Receive button - for latest in-transit/pending routing to user's office */}
                        {receivableRouting && (
                            <Button
                                variant="default"
                                onClick={() => {
                                    if (confirm('Mark this document as received by your office?')) {
                                        router.post(`/documents/${document.id}/routings/${receivableRouting.id}/receive`, {}, {
                                            preserveScroll: true,
                                        });
                                    }
                                }}
                            >
                                <CheckCircle className="size-4 mr-2" />
                                Receive Document
                            </Button>
                        )}

                        {document.qr_code && (
                            <Button variant="outline">
                                <QrCode className="size-4 mr-2" />
                                View QR Code
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="md:col-span-2 space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Document Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <p className="text-muted-foreground text-sm mb-1">Document Type</p>
                                        <p className="font-medium capitalize">{document.document_type}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground text-sm mb-1">Confidentiality</p>
                                        <p className="font-medium capitalize">{document.confidentiality}</p>
                                    </div>
                                    {document.source && (
                                        <div>
                                            <p className="text-muted-foreground text-sm mb-1">Source</p>
                                            <p className="font-medium">{document.source}</p>
                                        </div>
                                    )}
                                    {document.current_office && (
                                        <div>
                                            <p className="text-muted-foreground text-sm mb-1">Current Office</p>
                                            <p className="font-medium">{document.current_office.name}</p>
                                        </div>
                                    )}
                                    {document.date_received && (
                                        <div>
                                            <p className="text-muted-foreground text-sm mb-1">Date Received</p>
                                            <p className="font-medium">
                                                {new Date(document.date_received).toLocaleDateString()}
                                            </p>
                                        </div>
                                    )}
                                    {document.date_due && (
                                        <div>
                                            <p className="text-muted-foreground text-sm mb-1">Date Due</p>
                                            <div className="flex items-center gap-2">
                                                <p className="font-medium">
                                                    {new Date(document.date_due).toLocaleDateString()}
                                                </p>
                                                {(() => {
                                                    const due = new Date(document.date_due);
                                                    const now = new Date();
                                                    const isOverdue = due < now && !['completed', 'archived', 'returned'].includes(document.status);
                                                    const daysUntilDue = Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

                                                    if (isOverdue) {
                                                        return (
                                                            <Badge variant="destructive" className="text-xs">
                                                                SLA Breached
                                                            </Badge>
                                                        );
                                                    }
                                                    if (daysUntilDue <= 7 && daysUntilDue >= 0) {
                                                        return (
                                                            <Badge variant="default" className="text-xs">
                                                                Due Soon
                                                            </Badge>
                                                        );
                                                    }
                                                    if (daysUntilDue > 7) {
                                                        return (
                                                            <Badge variant="outline" className="text-xs">
                                                                Within SLA
                                                            </Badge>
                                                        );
                                                    }
                                                    return null;
                                                })()}
                                            </div>
                                        </div>
                                    )}
                                    {document.is_archived && document.archived_at && (
                                        <div>
                                            <p className="text-muted-foreground text-sm mb-1">Archived Date</p>
                                            <div className="flex items-center gap-2">
                                                <p className="font-medium">
                                                    {new Date(document.archived_at).toLocaleDateString()} at{' '}
                                                    {new Date(document.archived_at).toLocaleTimeString()}
                                                </p>
                                                <Badge variant="secondary" className="text-xs">
                                                    Archived
                                                </Badge>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {document.description && (
                                    <>
                                        <Separator />
                                        <div>
                                            <p className="text-muted-foreground text-sm mb-2">Description</p>
                                            <p className="text-sm whitespace-pre-wrap">{document.description}</p>
                                        </div>
                                    </>
                                )}

                                {document.creator && (
                                    <>
                                        <Separator />
                                        <div className="flex items-center gap-2 text-sm">
                                            <User className="size-4 text-muted-foreground" />
                                            <span className="text-muted-foreground">Created by</span>
                                            <span className="font-medium">{document.creator.name}</span>
                                            <span className="text-muted-foreground">
                                                {formatRelativeTime(document.created_at)}
                                            </span>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Show parent document link if this is a copy */}
                        {document.is_copy && document.parent_document && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="size-5" />
                                        Parent Document
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="font-medium">{document.parent_document.title}</p>
                                            <p className="text-sm text-muted-foreground">
                                                Tracking: {document.parent_document.tracking_number}
                                            </p>
                                        </div>
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={show(document.parent_document.id).url}>
                                                View Parent
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Main Document Routing History */}
                        {document.routings && document.routings.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Route className="size-5" />
                                        {document.is_copy ? 'Copy Routing History' : 'Main Document Routing History'}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {document.routings.map((routing, index) => (
                                            <div key={routing.id} className="relative pl-8">
                                                {index < document.routings!.length - 1 && (
                                                    <div className="absolute left-3 top-8 bottom-0 w-0.5 bg-border" />
                                                )}
                                                <div className="flex items-start gap-3">
                                                    <div className="mt-1 flex size-6 items-center justify-center rounded-full bg-primary/10">
                                                        <Building2 className="size-3 text-primary" />
                                                    </div>
                                                    <div className="flex-1 space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <p className="font-medium">
                                                                {routing.from_office?.name || 'Origin'} → {routing.to_office?.name}
                                                            </p>
                                                            <Badge variant={routing.status === 'received' ? 'default' : 'outline'}>
                                                                {routing.status.replace('_', ' ')}
                                                            </Badge>
                                                        </div>
                                                        <div className="text-muted-foreground text-sm space-y-1">
                                                            {routing.routed_by_user && (
                                                                <p>
                                                                    Routed by <span className="font-medium">{routing.routed_by_user.name}</span>
                                                                </p>
                                                            )}
                                                            <p className="flex items-center gap-1">
                                                                <Clock className="size-3" />
                                                                Routed {formatRelativeTime(routing.routed_at)}
                                                            </p>
                                                            {routing.received_at && (
                                                                <p className="flex items-center gap-1">
                                                                    <CheckCircle className="size-3" />
                                                                    Received {formatRelativeTime(routing.received_at)}
                                                                    {routing.received_by_user && (
                                                                        <span className="ml-1">by {routing.received_by_user.name}</span>
                                                                    )}
                                                                </p>
                                                            )}
                                                            {routing.received_at && routing.routed_at && (
                                                                <p className="text-xs text-muted-foreground/80">
                                                                    Routing time: {calculateRoutingTime(routing.routed_at, routing.received_at)}
                                                                </p>
                                                            )}
                                                            {routing.remarks && (
                                                                <p className="mt-1 italic">"{routing.remarks}"</p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Show copies if this is the main document */}
                        {!document.is_copy && document.copies && document.copies.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="size-5" />
                                        Document Copies ({document.copies.length})
                                    </CardTitle>
                                    <CardDescription>
                                        Copies of this document routed to different offices. Each copy maintains its own routing history.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        {document.copies.map((copy) => (
                                            <div key={copy.id} className="rounded-lg border p-4 space-y-3">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 mb-2">
                                                            <h4 className="font-semibold">{copy.title}</h4>
                                                            <Badge variant="secondary">Copy #{copy.copy_number}</Badge>
                                                            <Badge variant={getStatusBadgeVariant(copy.status)}>
                                                                {copy.status.replace('_', ' ')}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-sm text-muted-foreground mb-2">
                                                            Tracking: <span className="font-mono">{copy.tracking_number}</span>
                                                        </p>
                                                        {copy.current_office && (
                                                            <p className="text-sm text-muted-foreground">
                                                                Current Office: <span className="font-medium">{copy.current_office.name}</span>
                                                            </p>
                                                        )}
                                                    </div>
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={show(copy.id).url}>
                                                            View Copy
                                                        </Link>
                                                    </Button>
                                                </div>

                                                {/* Copy's routing history */}
                                                {copy.routings && copy.routings.length > 0 && (
                                                    <div className="mt-4 pt-4 border-t">
                                                        <p className="text-sm font-medium mb-3 text-muted-foreground">Copy Routing History:</p>
                                                        <div className="space-y-3">
                                                            {copy.routings.map((routing, index) => (
                                                                <div key={routing.id} className="relative pl-6">
                                                                    {index < copy.routings!.length - 1 && (
                                                                        <div className="absolute left-2 top-6 bottom-0 w-0.5 bg-border" />
                                                                    )}
                                                                    <div className="flex items-start gap-2">
                                                                        <div className="mt-1 flex size-4 items-center justify-center rounded-full bg-secondary/50">
                                                                            <Building2 className="size-2 text-muted-foreground" />
                                                                        </div>
                                                                        <div className="flex-1 space-y-1">
                                                                            <div className="flex items-center gap-2">
                                                                                <p className="text-sm font-medium">
                                                                                    {routing.from_office?.name || 'Origin'} → {routing.to_office?.name}
                                                                                </p>
                                                                                <Badge variant={routing.status === 'received' ? 'default' : 'outline'} className="text-xs">
                                                                                    {routing.status.replace('_', ' ')}
                                                                                </Badge>
                                                                            </div>
                                                                            <div className="text-muted-foreground text-xs space-y-0.5">
                                                                                <p className="flex items-center gap-1">
                                                                                    <Clock className="size-2.5" />
                                                                                    Routed {formatRelativeTime(routing.routed_at)}
                                                                                </p>
                                                                                {routing.received_at && (
                                                                                    <p className="flex items-center gap-1">
                                                                                        <CheckCircle className="size-2.5" />
                                                                                        Received {formatRelativeTime(routing.received_at)}
                                                                                    </p>
                                                                                )}
                                                                                {routing.remarks && (
                                                                                    <p className="mt-1 italic text-xs">"{routing.remarks}"</p>
                                                                                )}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {document.actions && document.actions.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="size-5" />
                                        Actions
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {document.actions.map((action) => (
                                            <div key={action.id} className="rounded-lg border p-4">
                                                <div className="flex items-start justify-between mb-2">
                                                    <div className="flex items-center gap-2">
                                                        <Badge>{getActionTypeLabel(action.action_type)}</Badge>
                                                        {action.is_office_head_approval && (
                                                            <Badge variant="secondary">Office Head</Badge>
                                                        )}
                                                    </div>
                                                    <span className="text-muted-foreground text-xs">
                                                        {formatRelativeTime(action.action_at)}
                                                    </span>
                                                </div>
                                                {action.action_by_user && (
                                                    <p className="text-muted-foreground text-sm mb-2">
                                                        By <span className="font-medium">{action.action_by_user.name}</span>
                                                        {action.office && (
                                                            <> from <span className="font-medium">{action.office.name}</span></>
                                                        )}
                                                    </p>
                                                )}
                                                {action.remarks && (
                                                    <p className="text-sm whitespace-pre-wrap">{action.remarks}</p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {document.attachments && document.attachments.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Attachments</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {document.attachments.map((attachment) => (
                                            <div key={attachment.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div className="flex items-center gap-3">
                                                    <FileText className="size-5 text-muted-foreground" />
                                                    <div>
                                                        <p className="font-medium text-sm">{attachment.original_name}</p>
                                                        {attachment.uploaded_by_user && (
                                                            <p className="text-muted-foreground text-xs">
                                                                Uploaded by {attachment.uploaded_by_user.name}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/documents/${document.id}/attachments/${attachment.id}/download`}
                                                    >
                                                        Download
                                                    </a>
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Quick Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Reroute Document Button - shown when document is in transit to another office */}
                                {isInTransitToOtherOffice && outgoingRouting && (
                                    <Button
                                        className="w-full justify-start"
                                        variant="destructive"
                                        onClick={() => {
                                            if (confirm(`Cancel the routing to ${outgoingRouting.to_office?.name}? This will cancel the current route.`)) {
                                                router.delete(`/documents/${document.id}/routings/${outgoingRouting.id}/cancel`, {
                                                    preserveScroll: true,
                                                    onSuccess: () => {
                                                        // Success handled by redirect
                                                    },
                                                });
                                            }
                                        }}
                                    >
                                        <Route className="size-4 mr-2" />
                                        Reroute Document
                                    </Button>
                                )}

                                <Dialog open={routeDialogOpen} onOpenChange={setRouteDialogOpen}>
                                    <DialogTrigger asChild>
                                        <Button
                                            className="w-full justify-start"
                                            variant="outline"
                                            disabled={shouldDisableQuickActions}
                                        >
                                            <Route className="size-4 mr-2" />
                                            Route Document
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Route Document</DialogTitle>
                                            <DialogDescription>
                                                Select the destination office and add optional remarks for routing this document.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form onSubmit={handleRouteSubmit} className="space-y-4">
                                            <div className="flex items-center space-x-2">
                                                <Checkbox
                                                    id="create_copies"
                                                    checked={routeForm.data.create_copies}
                                                    onCheckedChange={(checked) => {
                                                        routeForm.setData('create_copies', checked as boolean);
                                                        if (!checked) {
                                                            routeForm.setData('to_office_ids', []);
                                                        } else {
                                                            routeForm.setData('to_office_id', null);
                                                        }
                                                    }}
                                                />
                                                <Label htmlFor="create_copies" className="cursor-pointer">
                                                    Create copies for multiple offices (for distribution)
                                                </Label>
                                            </div>

                                            {!routeForm.data.create_copies ? (
                                                <div className="grid gap-2">
                                                    <Label htmlFor="to_office_id">
                                                        Destination Office <span className="text-destructive">*</span>
                                                    </Label>
                                                    <Select
                                                        value={routeForm.data.to_office_id ? String(routeForm.data.to_office_id) : undefined}
                                                        onValueChange={(value) => routeForm.setData('to_office_id', Number(value))}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select destination office" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {offices
                                                                .filter((office) => office.id !== document.current_office_id)
                                                                .map((office) => (
                                                                    <SelectItem key={office.id} value={String(office.id)}>
                                                                        {office.name}
                                                                    </SelectItem>
                                                                ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError message={routeForm.errors.to_office_id} />
                                                </div>
                                            ) : (
                                                <div className="grid gap-2">
                                                    <Label htmlFor="to_office_ids">
                                                        Destination Offices <span className="text-destructive">*</span>
                                                    </Label>
                                                    <div className="border rounded-md p-3 max-h-60 overflow-y-auto space-y-2">
                                                        {offices
                                                            .filter((office) => office.id !== document.current_office_id)
                                                            .map((office) => (
                                                                <div key={office.id} className="flex items-center space-x-2">
                                                                    <Checkbox
                                                                        id={`office_${office.id}`}
                                                                        checked={routeForm.data.to_office_ids?.includes(office.id) || false}
                                                                        onCheckedChange={(checked) => {
                                                                            const currentIds = routeForm.data.to_office_ids || [];
                                                                            if (checked) {
                                                                                routeForm.setData('to_office_ids', [...currentIds, office.id]);
                                                                            } else {
                                                                                routeForm.setData('to_office_ids', currentIds.filter(id => id !== office.id));
                                                                            }
                                                                        }}
                                                                    />
                                                                    <Label htmlFor={`office_${office.id}`} className="cursor-pointer font-normal">
                                                                        {office.name}
                                                                    </Label>
                                                                </div>
                                                            ))}
                                                    </div>
                                                    <InputError message={routeForm.errors.to_office_ids} />
                                                    {routeForm.data.to_office_ids && routeForm.data.to_office_ids.length > 0 && (
                                                        <p className="text-sm text-muted-foreground">
                                                            {routeForm.data.to_office_ids.length} office{routeForm.data.to_office_ids.length !== 1 ? 's' : ''} selected
                                                        </p>
                                                    )}
                                                </div>
                                            )}

                                            <div className="grid gap-2">
                                                <Label htmlFor="remarks">Remarks (Optional)</Label>
                                                <Textarea
                                                    id="remarks"
                                                    name="remarks"
                                                    value={routeForm.data.remarks}
                                                    onChange={(e) => routeForm.setData('remarks', e.target.value)}
                                                    rows={4}
                                                    placeholder="Add any remarks or notes about this routing..."
                                                />
                                                <InputError message={routeForm.errors.remarks} />
                                            </div>

                                            <DialogFooter>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setRouteDialogOpen(false);
                                                        routeForm.reset();
                                                    }}
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    disabled={
                                                        routeForm.processing ||
                                                        (!routeForm.data.to_office_id && (!routeForm.data.to_office_ids || routeForm.data.to_office_ids.length === 0))
                                                    }
                                                >
                                                    {routeForm.processing
                                                        ? 'Routing...'
                                                        : routeForm.data.create_copies
                                                            ? `Create ${routeForm.data.to_office_ids?.length || 0} Copies & Route`
                                                            : 'Route Document'}
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                                <Dialog open={actionDialogOpen} onOpenChange={setActionDialogOpen}>
                                    <DialogTrigger asChild>
                                        <Button
                                            className="w-full justify-start"
                                            variant="outline"
                                            disabled={shouldDisableQuickActions}
                                        >
                                            <FileText className="size-4 mr-2" />
                                            Add Action
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Add Action</DialogTitle>
                                            <DialogDescription>
                                                Record an action taken on this document by your office.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form onSubmit={handleActionSubmit} className="space-y-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="action_type">
                                                    Action Type <span className="text-destructive">*</span>
                                                </Label>
                                                <Select
                                                    value={actionForm.data.action_type}
                                                    onValueChange={(value) => actionForm.setData('action_type', value as typeof actionForm.data.action_type)}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select action type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="approve">Approve</SelectItem>
                                                        <SelectItem value="note">Note</SelectItem>
                                                        <SelectItem value="comply">Comply</SelectItem>
                                                        <SelectItem value="sign">Sign</SelectItem>
                                                        <SelectItem value="return">Return</SelectItem>
                                                        <SelectItem value="forward">Forward</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={actionForm.errors.action_type} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="remarks">Remarks (Optional)</Label>
                                                <Textarea
                                                    id="remarks"
                                                    name="remarks"
                                                    value={actionForm.data.remarks}
                                                    onChange={(e) => actionForm.setData('remarks', e.target.value)}
                                                    rows={4}
                                                    placeholder="Add any remarks or notes about this action..."
                                                />
                                                <InputError message={actionForm.errors.remarks} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="memo_file">Memo File (Optional)</Label>
                                                <Input
                                                    id="memo_file"
                                                    type="file"
                                                    accept=".pdf,.doc,.docx"
                                                    onChange={(e) => {
                                                        const file = e.target.files?.[0];
                                                        if (file) {
                                                            actionForm.setData('memo_file', file);
                                                        }
                                                    }}
                                                />
                                                <p className="text-muted-foreground text-xs">
                                                    PDF or Word document (max 10MB)
                                                </p>
                                                <InputError message={actionForm.errors.memo_file} />
                                            </div>

                                            <div className="flex items-center space-x-2">
                                                <Checkbox
                                                    id="is_office_head_approval"
                                                    checked={actionForm.data.is_office_head_approval}
                                                    onCheckedChange={(checked) => {
                                                        actionForm.setData('is_office_head_approval', checked === true);
                                                    }}
                                                />
                                                <Label
                                                    htmlFor="is_office_head_approval"
                                                    className="text-sm font-normal cursor-pointer"
                                                >
                                                    Office Head Approval
                                                </Label>
                                            </div>

                                            <DialogFooter>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setActionDialogOpen(false);
                                                        actionForm.reset();
                                                    }}
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    disabled={actionForm.processing || !actionForm.data.action_type}
                                                >
                                                    {actionForm.processing ? 'Submitting...' : 'Add Action'}
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                                <div className="space-y-2">
                                    <p className="text-sm font-medium">Upload Attachment</p>
                                    <Form
                                        action={`/documents/${document.id}/attachments`}
                                        method="post"
                                        encType="multipart/form-data"
                                    >
                                        {({ processing, setData, errors }) => (
                                            <div className="space-y-2">
                                                <input
                                                    type="file"
                                                    name="file"
                                                    onChange={(e) => {
                                                        const file = e.currentTarget.files?.[0];
                                                        if (file) {
                                                            setData('file', file);
                                                        }
                                                    }}
                                                    className="block w-full text-sm"
                                                />
                                                {errors.file && (
                                                    <p className="text-xs text-destructive">{errors.file}</p>
                                                )}
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    className="w-full justify-center"
                                                    disabled={processing}
                                                >
                                                    {processing ? 'Uploading...' : 'Upload'}
                                                </Button>
                                            </div>
                                        )}
                                    </Form>
                                </div>
                            </CardContent>
                        </Card>

                        {document.qr_code && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <QrCode className="size-5" />
                                        QR Code
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-center rounded-lg border p-4 bg-muted/50">
                                        {document.qr_code.image_path ? (
                                            <img
                                                src={`/storage/${document.qr_code.image_path}`}
                                                alt="QR Code"
                                                className="size-48 object-contain"
                                            />
                                        ) : (
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground text-center">
                                                <QrCode className="size-16" />
                                                <p className="text-sm">QR Code image not generated</p>
                                                <p className="text-xs break-all max-w-full px-2">
                                                    {document.qr_code.verification_url}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                    <div className="space-y-2 text-sm">
                                        <div>
                                            <p className="text-muted-foreground">Scans</p>
                                            <p className="font-medium">{document.qr_code.scan_count}</p>
                                        </div>
                                        {document.qr_code.last_scanned_at && (
                                            <div>
                                                <p className="text-muted-foreground">Last Scanned</p>
                                                <p className="font-medium">
                                                    {formatRelativeTime(document.qr_code.last_scanned_at)}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                    <Button variant="outline" className="w-full" asChild>
                                        <a href={`/documents/${document.id}/qr-code/download`} download>
                                            Download QR Code
                                        </a>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        onClick={() => {
                                            if (confirm('Are you sure you want to regenerate this QR code? The old one will be deactivated.')) {
                                                router.post(`/documents/${document.id}/qr-code/regenerate`);
                                            }
                                        }}
                                    >
                                        Regenerate QR Code
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
