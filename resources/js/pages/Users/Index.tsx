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
import { create, index } from '@/routes/users';
import { type BreadcrumbItem, type Office, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, User as UserIcon } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface UsersIndexProps {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    offices: Office[];
    filters: {
        search?: string;
        role?: string;
        office_id?: string;
        is_active?: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
];

export default function UsersIndex({ users: usersData, offices, filters }: UsersIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [role, setRole] = useState(filters.role || '');
    const [officeId, setOfficeId] = useState(filters.office_id || '');
    const [isActive, setIsActive] = useState(filters.is_active !== undefined ? String(filters.is_active) : 'all');

    const handleFilter = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            index().url,
            {
                search: search || undefined,
                role: role === 'all' ? undefined : role || undefined,
                office_id: officeId === 'all' ? undefined : officeId || undefined,
                is_active: isActive === 'all' ? undefined : isActive === 'true',
            },
            { preserveState: true, replace: true }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setRole('');
        setOfficeId('');
        setIsActive('all');
        router.get(index().url, {}, { preserveState: true, replace: true });
    };

    const getRoleBadgeVariant = (role: User['role']) => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
            admin: 'destructive',
            encoder: 'default',
            approver: 'secondary',
            viewer: 'outline',
        };
        return variants[role || 'viewer'] || 'outline';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Users</h1>
                        <p className="text-muted-foreground text-sm">
                            Manage users and their office assignments
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="size-4 mr-2" />
                            New User
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleFilter} className="grid gap-4 md:grid-cols-5">
                            <div className="grid gap-2">
                                <Input
                                    placeholder="Search by name or email..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Select value={role} onValueChange={setRole}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Roles" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Roles</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="encoder">Encoder</SelectItem>
                                        <SelectItem value="approver">Approver</SelectItem>
                                        <SelectItem value="viewer">Viewer</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Select value={officeId} onValueChange={setOfficeId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Offices" />
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
                            </div>
                            <div className="grid gap-2">
                                <Select value={isActive} onValueChange={setIsActive}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="true">Active</SelectItem>
                                        <SelectItem value="false">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" variant="default" className="flex-1">
                                    <Search className="size-4 mr-2" />
                                    Search
                                </Button>
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
                            Users ({usersData.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {usersData.data.length === 0 ? (
                            <div className="text-center py-12">
                                <UserIcon className="size-12 mx-auto text-muted-foreground mb-4" />
                                <p className="text-muted-foreground">No users found</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted/50">
                                        <tr>
                                            <th className="px-4 py-2 text-left font-medium">Name</th>
                                            <th className="px-4 py-2 text-left font-medium">Email</th>
                                            <th className="px-4 py-2 text-left font-medium">Role</th>
                                            <th className="px-4 py-2 text-left font-medium">Office</th>
                                            <th className="px-4 py-2 text-left font-medium">Status</th>
                                            <th className="px-4 py-2 text-left font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {usersData.data.map((user) => (
                                            <tr
                                                key={user.id}
                                                className="border-t hover:bg-muted/40 transition-colors"
                                            >
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-medium">
                                                            {user.name.charAt(0).toUpperCase()}
                                                        </div>
                                                        <span className="font-medium">{user.name}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {user.email}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={getRoleBadgeVariant(user.role)}
                                                        className="text-xs capitalize"
                                                    >
                                                        {user.role || 'viewer'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {user.office?.name || '—'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={user.is_active ? 'outline' : 'destructive'}
                                                        className="text-xs"
                                                    >
                                                        {user.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link href={`/users/${user.id}/edit`}>
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {usersData.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-center gap-2">
                                {usersData.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url || link.active}
                                        asChild={!!link.url && !link.active}
                                    >
                                        {link.url && !link.active ? (
                                            <Link href={link.url}>{link.label.replace('&laquo;', '«').replace('&raquo;', '»')}</Link>
                                        ) : (
                                            <span>{link.label.replace('&laquo;', '«').replace('&raquo;', '»')}</span>
                                        )}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
