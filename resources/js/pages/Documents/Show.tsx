import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { edit, index, route, show } from '@/routes/documents';
import { type BreadcrumbItem, type Document } from '@/types';
import { Form, Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    FileText,
    QrCode,
    Route,
    User,
    Building2,
    Clock,
} from 'lucide-react';
import { useState, FormEvent } from 'react';
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

interface DocumentsShowProps {
    document: Document;
    offices: Array<{ id: number; name: string }>;
}

export default function DocumentsShow({ document, offices }: DocumentsShowProps) {
    const [routeDialogOpen, setRouteDialogOpen] = useState(false);

    const routeForm = useForm({
        to_office_id: null as number | null,
        remarks: '',
    });

    const handleRouteSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!routeForm.data.to_office_id) {
            return;
        }

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
                            </div>
                            <p className="text-muted-foreground text-sm">
                                Tracking Number: <span className="font-mono font-medium">{document.tracking_number}</span>
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(document.id).url}>Edit</Link>
                        </Button>
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

                        {document.routings && document.routings.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Route className="size-5" />
                                        Routing History
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
                                                                {formatRelativeTime(routing.routed_at)}
                                                            </p>
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
                                <Dialog open={routeDialogOpen} onOpenChange={setRouteDialogOpen}>
                                    <DialogTrigger asChild>
                                        <Button className="w-full justify-start" variant="outline">
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
                                                    disabled={routeForm.processing || !routeForm.data.to_office_id}
                                                >
                                                    {routeForm.processing ? 'Routing...' : 'Route Document'}
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                                <Button className="w-full justify-start" variant="outline">
                                    <FileText className="size-4 mr-2" />
                                    Add Action
                                </Button>
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
