import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import { index, show } from '@/routes/documents';
import { type BreadcrumbItem, type Document, type Office } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

interface DocumentsEditProps {
    document: Document;
    offices: Office[];
}

export default function DocumentsEdit({ document, offices }: DocumentsEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Documents',
            href: index().url,
        },
        {
            title: document.title,
            href: show(document.id).url,
        },
        {
            title: 'Edit',
            href: '#',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit: ${document.title}`} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={show(document.id).url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Edit Document</h1>
                        <p className="text-muted-foreground text-sm">
                            Update document information
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Document Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form {...DocumentController.update.form({ document: document.id })} className="space-y-6">
                            {({ processing, errors, data, setData }) => (
                                <>
                                    <div className="grid gap-6 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="title">
                                                Title <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="title"
                                                name="title"
                                                value={data.title ?? document.title}
                                                onChange={(e) => setData('title', e.target.value)}
                                                required
                                                autoFocus
                                            />
                                            <InputError message={errors.title} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="document_type">
                                                Document Type <span className="text-destructive">*</span>
                                            </Label>
                                            <Select
                                                value={data.document_type ?? document.document_type}
                                                onValueChange={(value) => setData('document_type', value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="incoming">Incoming</SelectItem>
                                                    <SelectItem value="outgoing">Outgoing</SelectItem>
                                                    <SelectItem value="internal">Internal</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.document_type} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="source">Source</Label>
                                            <Input
                                                id="source"
                                                name="source"
                                                value={data.source ?? document.source ?? ''}
                                                onChange={(e) => setData('source', e.target.value)}
                                                placeholder="Source office or external entity"
                                            />
                                            <InputError message={errors.source} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="priority">
                                                Priority <span className="text-destructive">*</span>
                                            </Label>
                                            <Select
                                                value={data.priority ?? document.priority}
                                                onValueChange={(value) => setData('priority', value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="low">Low</SelectItem>
                                                    <SelectItem value="normal">Normal</SelectItem>
                                                    <SelectItem value="high">High</SelectItem>
                                                    <SelectItem value="urgent">Urgent</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.priority} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="confidentiality">
                                                Confidentiality <span className="text-destructive">*</span>
                                            </Label>
                                            <Select
                                                value={data.confidentiality ?? document.confidentiality}
                                                onValueChange={(value) => setData('confidentiality', value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="public">Public</SelectItem>
                                                    <SelectItem value="confidential">Confidential</SelectItem>
                                                    <SelectItem value="restricted">Restricted</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.confidentiality} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="status">Status</Label>
                                            <Select
                                                value={data.status ?? document.status}
                                                onValueChange={(value) => setData('status', value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
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
                                            <InputError message={errors.status} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="date_received">Date Received</Label>
                                            <Input
                                                id="date_received"
                                                type="date"
                                                name="date_received"
                                                value={data.date_received ?? document.date_received ?? ''}
                                                onChange={(e) => setData('date_received', e.target.value)}
                                            />
                                            <InputError message={errors.date_received} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="date_due">Date Due</Label>
                                            <Input
                                                id="date_due"
                                                type="date"
                                                name="date_due"
                                                value={data.date_due ?? document.date_due ?? ''}
                                                onChange={(e) => setData('date_due', e.target.value)}
                                            />
                                            <InputError message={errors.date_due} />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">Description</Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            value={data.description ?? document.description ?? ''}
                                            onChange={(e) => setData('description', e.target.value)}
                                            rows={4}
                                            placeholder="Document description..."
                                        />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div className="flex items-center justify-end gap-4">
                                        <Button type="button" variant="outline" asChild>
                                            <Link href={show(document.id).url}>Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Updating...' : 'Update Document'}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
