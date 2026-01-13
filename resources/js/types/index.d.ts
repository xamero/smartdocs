import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    role?: 'admin' | 'encoder' | 'approver' | 'viewer';
    office_id?: number | null;
    is_active?: boolean;
    last_login_at?: string | null;
    last_login_ip?: string | null;
    office?: Office | null;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Office {
    id: number;
    name: string;
    code?: string;
    description?: string;
    parent_id?: number;
    is_active: boolean;
    sort_order: number;
    parent?: Office;
    children?: Office[];
    documents_total_count?: number;
    documents_in_transit_count?: number;
    documents_in_action_count?: number;
    documents_completed_count?: number;
    documents_archived_count?: number;
}

export interface Document {
    id: number;
    tracking_number: string;
    title: string;
    description?: string;
    document_type: 'incoming' | 'outgoing' | 'internal';
    source?: string;
    priority: 'low' | 'normal' | 'high' | 'urgent';
    confidentiality: 'public' | 'confidential' | 'restricted';
    status: 'draft' | 'registered' | 'in_transit' | 'received' | 'in_action' | 'completed' | 'archived' | 'returned';
    current_office_id?: number;
    receiving_office_id?: number;
    created_by: number;
    registered_by?: number;
    date_received?: string;
    date_due?: string;
    is_merged: boolean;
    is_archived: boolean;
    archived_at?: string;
    created_at: string;
    updated_at: string;
    parent_document_id?: number;
    is_copy: boolean;
    copy_number?: number;
    current_office?: Office;
    receiving_office?: Office;
    creator?: User;
    registrar?: User;
    routings?: DocumentRouting[];
    actions?: DocumentAction[];
    attachments?: DocumentAttachment[];
    qr_code?: QRCode;
    parent_document?: Document;
    copies?: Document[];
    is_originating_office?: boolean;
    is_incoming_to_office?: boolean;
    receivableRouting?: DocumentRouting | null;
}

export interface DocumentRouting {
    id: number;
    document_id: number;
    from_office_id?: number;
    to_office_id: number;
    routed_by: number;
    received_by?: number;
    remarks?: string;
    status: 'pending' | 'in_transit' | 'received' | 'returned';
    routed_at: string;
    received_at?: string;
    returned_at?: string;
    sequence: number;
    from_office?: Office;
    to_office?: Office;
    routed_by_user?: User;
    received_by_user?: User;
}

export interface DocumentAction {
    id: number;
    document_id: number;
    office_id: number;
    action_by: number;
    action_type: 'approve' | 'note' | 'comply' | 'sign' | 'return' | 'forward';
    remarks?: string;
    memo_file_path?: string;
    is_office_head_approval: boolean;
    action_at: string;
    office?: Office;
    action_by_user?: User;
}

export interface DocumentAttachment {
    id: number;
    document_id: number;
    file_name: string;
    original_name: string;
    file_path: string;
    mime_type?: string;
    file_size?: number;
    uploaded_by: number;
    uploaded_by_user?: User;
}

export interface QRCode {
    id: number;
    document_id: number;
    code: string;
    hash: string;
    verification_url: string;
    image_path?: string;
    is_active: boolean;
    scan_count: number;
    last_scanned_at?: string;
}

export interface SmartdocNotification {
    id: number;
    user_id: number;
    office_id?: number;
    document_id?: number;
    type: 'routing' | 'action_required' | 'overdue' | 'qr_scan' | 'priority_escalation';
    title: string;
    message: string;
    data?: Record<string, unknown>;
    is_read: boolean;
    read_at?: string;
    is_email_sent: boolean;
    email_sent_at?: string;
    created_at: string;
    updated_at: string;
    document?: Document;
    office?: Office;
}
