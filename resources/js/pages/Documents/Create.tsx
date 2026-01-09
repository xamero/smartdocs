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
import { create, index } from '@/routes/documents';
import { type BreadcrumbItem, type Office } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

interface DocumentsCreateProps {
    offices: Office[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documents',
        href: documents().url,
    },
    {
        title: 'Create',
        href: create().url,
    },
];

export default function DocumentsCreate({ offices }: DocumentsCreateProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Document" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Create New Document</h1>
                        <p className="text-muted-foreground text-sm">
                            Register a new document in the system
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Document Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form {...DocumentController.store.form()} className="space-y-6">
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
                                                value={data.title || ''}
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
                                                value={data.document_type || ''}
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
                                                value={data.source || ''}
                                                onChange={(e) => setData('source', e.target.value)}
                                                placeholder="Source office or external entity"
                                            />
                                            <InputError message={errors.source} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="receiving_office_id">Receiving Office</Label>
                                            <Select
                                                value={data.receiving_office_id ? String(data.receiving_office_id) : ''}
                                                onValueChange={(value) =>
                                                    setData('receiving_office_id', value ? Number(value) : null)
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select office" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">None</SelectItem>
                                                    {offices.map((office) => (
                                                        <SelectItem key={office.id} value={String(office.id)}>
                                                            {office.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.receiving_office_id} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="priority">
                                                Priority <span className="text-destructive">*</span>
                                            </Label>
                                            <Select
                                                value={data.priority || 'normal'}
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
                                                value={data.confidentiality || 'public'}
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
                                            <Label htmlFor="date_received">Date Received</Label>
                                            <Input
                                                id="date_received"
                                                type="date"
                                                name="date_received"
                                                value={data.date_received || ''}
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
                                                value={data.date_due || ''}
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
                                            value={data.description || ''}
                                            onChange={(e) => setData('description', e.target.value)}
                                            rows={4}
                                            placeholder="Document description..."
                                        />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div className="flex items-center justify-end gap-4">
                                        <Button type="button" variant="outline" asChild>
                                            <Link href={index().url}>Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Creating...' : 'Create Document'}
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
