import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Document, type QRCode } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, XCircle, QrCode, FileText } from 'lucide-react';

interface VerifyProps {
    code: string;
    qrCode?: QRCode | null;
    document?: Document | null;
}

export default function Verify({ code, qrCode, document }: VerifyProps) {
    const isValid = !!qrCode && !!document;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Home',
            href: '/',
        },
        {
            title: 'Verify Document',
            href: '#',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Verify Document" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/">
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Verify Document</h1>
                        <p className="text-muted-foreground text-sm">
                            QR Code: <span className="font-mono break-all">{code}</span>
                        </p>
                    </div>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <QrCode className="size-5" />
                            QR Verification Result
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {isValid ? (
                            <>
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="size-5 text-emerald-500" />
                                    <span className="font-medium text-emerald-600 dark:text-emerald-400">
                                        Valid QR code. Document found.
                                    </span>
                                </div>

                                <div className="rounded-lg border bg-muted/40 p-4 space-y-2">
                                    <div className="flex items-center gap-2">
                                        <FileText className="size-4 text-muted-foreground" />
                                        <p className="font-medium">{document!.title}</p>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Tracking Number:{' '}
                                        <span className="font-mono font-medium">
                                            {document!.tracking_number}
                                        </span>
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="outline" className="capitalize">
                                            {document!.document_type}
                                        </Badge>
                                        <Badge variant="outline" className="capitalize">
                                            {document!.confidentiality}
                                        </Badge>
                                        <Badge variant="outline" className="capitalize">
                                            {document!.status.replace('_', ' ')}
                                        </Badge>
                                    </div>
                                </div>

                                <Button asChild>
                                    <Link href={`/documents/${document!.id}`}>
                                        View Document Details
                                    </Link>
                                </Button>
                            </>
                        ) : (
                            <div className="space-y-3">
                                <div className="flex items-center gap-2">
                                    <XCircle className="size-5 text-destructive" />
                                    <span className="font-medium text-destructive">
                                        Invalid or inactive QR code.
                                    </span>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    We could not find a document associated with this QR code. The
                                    code may be expired, disabled, or incorrect.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

