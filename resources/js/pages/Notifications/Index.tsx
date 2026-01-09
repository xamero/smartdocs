import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as notificationsIndex } from '@/routes/notifications';
import { show } from '@/routes/documents';
import { type BreadcrumbItem, type SmartdocNotification } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';

interface NotificationsIndexProps {
    notifications: {
        data: SmartdocNotification[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: notificationsIndex().url,
    },
];

export default function NotificationsIndex({ notifications: notificationsData }: NotificationsIndexProps) {
    const [markingAll, setMarkingAll] = useState(false);

    const handleMarkAsRead = async (notificationId: number) => {
        try {
            await fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            router.reload({ only: ['notifications'] });
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const handleMarkAllAsRead = async () => {
        setMarkingAll(true);
        try {
            await fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            router.reload({ only: ['notifications'] });
        } catch (error) {
            console.error('Failed to mark all as read:', error);
        } finally {
            setMarkingAll(false);
        }
    };

    const formatTime = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
        return date.toLocaleDateString();
    };

    const getNotificationVariant = (type: SmartdocNotification['type']) => {
        const variants: Record<SmartdocNotification['type'], 'default' | 'destructive' | 'secondary'> = {
            routing: 'default',
            action_required: 'default',
            overdue: 'destructive',
            qr_scan: 'secondary',
            priority_escalation: 'destructive',
        };
        return variants[type] || 'default';
    };

    const unreadCount = notificationsData.data.filter((n) => !n.is_read).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Notifications</h1>
                        <p className="text-muted-foreground text-sm">
                            {notificationsData.total} total notification{notificationsData.total !== 1 ? 's' : ''}
                        </p>
                    </div>
                    {unreadCount > 0 && (
                        <Button variant="outline" onClick={handleMarkAllAsRead} disabled={markingAll}>
                            {markingAll ? 'Marking...' : `Mark all as read (${unreadCount})`}
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Bell className="size-5" />
                            All Notifications
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {notificationsData.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <Bell className="mb-4 size-12 text-muted-foreground" />
                                <p className="text-muted-foreground text-lg font-medium">No notifications</p>
                                <p className="text-muted-foreground text-sm">You're all caught up!</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {notificationsData.data.map((notification) => (
                                    <div
                                        key={notification.id}
                                        className={`rounded-lg border p-4 transition-colors ${
                                            !notification.is_read ? 'bg-muted/50' : ''
                                        }`}
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2 mb-2">
                                                    <Badge variant={getNotificationVariant(notification.type)} className="text-xs">
                                                        {notification.type.replace('_', ' ')}
                                                    </Badge>
                                                    {!notification.is_read && (
                                                        <div className="size-2 rounded-full bg-primary" />
                                                    )}
                                                    <span className="text-xs text-muted-foreground">
                                                        {formatTime(notification.created_at)}
                                                    </span>
                                                </div>
                                                <h3 className="font-semibold mb-1">{notification.title}</h3>
                                                <p className="text-sm text-muted-foreground mb-3">{notification.message}</p>
                                                {notification.document_id && (
                                                    <Link
                                                        href={show(notification.document_id).url}
                                                        className="text-sm text-primary hover:underline"
                                                    >
                                                        View Document →
                                                    </Link>
                                                )}
                                            </div>
                                            {!notification.is_read && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleMarkAsRead(notification.id)}
                                                >
                                                    Mark as read
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {notificationsData.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-center gap-2">
                                {notificationsData.links.map((link, index) => (
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
