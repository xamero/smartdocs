import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuHeader,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { show } from '@/routes/documents';
import { index as notificationsIndex } from '@/routes/notifications';
import { type SmartdocNotification } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useState } from 'react';

interface NotificationsDropdownProps {
    initialCount?: number;
}

export function NotificationsDropdown({ initialCount = 0 }: NotificationsDropdownProps) {
    const [unreadCount, setUnreadCount] = useState(initialCount);
    const [notifications, setNotifications] = useState<SmartdocNotification[]>([]);
    const [isOpen, setIsOpen] = useState(false);

    useEffect(() => {
        const fetchUnreadCount = async () => {
            try {
                const response = await fetch('/notifications/unread-count', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (response.ok) {
                    const data = await response.json();
                    setUnreadCount(data.count || 0);
                }
            } catch (error) {
                console.error('Failed to fetch unread count:', error);
            }
        };

        fetchUnreadCount();

        const interval = setInterval(fetchUnreadCount, 30000);

        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        if (isOpen) {
            const fetchRecent = async () => {
                try {
                    const response = await fetch('/notifications/recent', {
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (response.ok) {
                        const data = await response.json();
                        setNotifications(data || []);
                    }
                } catch (error) {
                    console.error('Failed to fetch notifications:', error);
                }
            };

            fetchRecent();
        }
    }, [isOpen]);

    const handleMarkAsRead = async (notificationId: number) => {
        try {
            await fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            setNotifications((prev) =>
                prev.map((n) => (n.id === notificationId ? { ...n, is_read: true } : n))
            );
            setUnreadCount((prev) => Math.max(0, prev - 1));
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const handleMarkAllAsRead = async () => {
        try {
            await fetch('/notifications/read-all', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
            setUnreadCount(0);
        } catch (error) {
            console.error('Failed to mark all as read:', error);
        }
    };

    const formatTime = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
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

    return (
        <DropdownMenu open={isOpen} onOpenChange={setIsOpen}>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative h-9 w-9" aria-label="Notifications">
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <Badge
                            variant="destructive"
                            className="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full p-0 text-xs"
                        >
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuHeader>
                    <div className="flex items-center justify-between">
                        <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                        {unreadCount > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-auto p-0 text-xs"
                                onClick={handleMarkAllAsRead}
                            >
                                Mark all as read
                            </Button>
                        )}
                    </div>
                </DropdownMenuHeader>
                <DropdownMenuSeparator />
                <div className="max-h-[400px] overflow-y-auto">
                    {notifications.length === 0 ? (
                        <div className="py-8 text-center text-sm text-muted-foreground">
                            No notifications
                        </div>
                    ) : (
                        notifications.map((notification) => (
                            <DropdownMenuItem
                                key={notification.id}
                                className="flex flex-col items-start gap-1 p-3"
                                onClick={() => {
                                    if (!notification.is_read) {
                                        handleMarkAsRead(notification.id);
                                    }
                                    if (notification.document_id) {
                                        router.visit(show(notification.document_id).url);
                                    }
                                }}
                            >
                                <div className="flex w-full items-start justify-between gap-2">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <Badge variant={getNotificationVariant(notification.type)} className="text-xs">
                                                {notification.type.replace('_', ' ')}
                                            </Badge>
                                            {!notification.is_read && (
                                                <div className="size-2 rounded-full bg-primary" />
                                            )}
                                        </div>
                                        <p className="mt-1 text-sm font-medium">{notification.title}</p>
                                        <p className="text-xs text-muted-foreground">{notification.message}</p>
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        {formatTime(notification.created_at)}
                                    </span>
                                </div>
                            </DropdownMenuItem>
                        ))
                    )}
                </div>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={notificationsIndex().url} className="w-full text-center">
                        View all notifications
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
