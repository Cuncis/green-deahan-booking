/** Props shared with every admin page (HandleInertiaRequests::share()). */
export interface NavItem {
    label: string;
    href: string;
    ikon: string;
    rute: string | null;
    /** true: page is React, navigate without reload */
    inertia: boolean;
}

export interface NavGrup {
    grup: string | null;
    item: NavItem[];
}

export interface SharedProps {
    [key: string]: unknown;
    auth: { user: { name: string; email: string } | null };
    area: 'admin' | 'superadmin';
    tenant: { nama_bisnis: string; paket: string } | null;
    navigasi: NavGrup[];
    flash: {
        success: string | null;
        error: string | null;
        invitationLink: string | null;
        invitationTenant: string | null;
    };
    errors: Record<string, string>;
}
