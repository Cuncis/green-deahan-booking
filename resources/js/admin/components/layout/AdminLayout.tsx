import { Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    Bell,
    BellRing,
    Bug,
    Building,
    CalendarClock,
    ChartColumn,
    ChartLine,
    ChevronDown,
    ClipboardList,
    Copy,
    Crown,
    FolderOpen,
    Globe,
    Goal,
    Images,
    LayoutDashboard,
    LogOut,
    Mail,
    Menu,
    Newspaper,
    Megaphone,
    SearchCheck,
    Settings,
    TicketPercent,
    UserPlus,
    UserRound,
    Users,
    type LucideIcon,
    Package,
    ShoppingBag,
    Truck,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { cn } from '../../lib/utils';
import type { NavGrup, NavItem, SharedProps } from '../../types';
import { Badge, varianPaket } from '../ui/badge';
import { Button } from '../ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '../ui/dropdown-menu';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '../ui/sheet';
import { Toaster } from '../ui/sonner';

const IKON: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    'clipboard-list': ClipboardList,
    goal: Goal,
    'calendar-clock': CalendarClock,
    'chart-line': ChartLine,
    'ticket-percent': TicketPercent,
    users: Users,
    crown: Crown,
    'bell-ring': BellRing,
    'chart-column': ChartColumn,
    settings: Settings,
    globe: Globe,
    building: Building,
    'user-plus': UserPlus,
    images: Images,
    newspaper: Newspaper,
    'folder-open': FolderOpen,
    'search-check': SearchCheck,
    megaphone: Megaphone,
    'shopping-bag': ShoppingBag,
    package: Package,
    truck: Truck,
    activity: Activity,
    bug: Bug,
    mail: Mail,
};

/** Tenant admin uses a light sidebar, superadmin keeps its plum sidebar. */
const TEMA = {
    admin: {
        sidebar: 'border-r border-border bg-card text-ink-mid',
        judul: 'text-green',
        sub: 'text-muted-foreground',
        grup: 'text-muted-foreground',
        aktif: 'bg-green-pale font-bold text-green',
        biasa: 'hover:bg-accent hover:text-foreground',
        garis: 'border-border',
        tutup: 'text-ink-mid hover:bg-accent',
    },
    superadmin: {
        sidebar: 'bg-plum text-white/75',
        judul: 'text-white',
        sub: 'text-white/60',
        grup: 'text-white/45',
        aktif: 'bg-white/15 font-bold text-white',
        biasa: 'hover:bg-white/10 hover:text-white',
        garis: 'border-white/15',
        tutup: 'text-white hover:bg-white/10',
    },
};

function cocok(item: NavItem, url: string): boolean {
    if (!item.inertia || item.href.includes('#')) {
        return false;
    }
    const path = new URL(item.href, window.location.origin).pathname;
    const sekarang = url.split('?')[0];

    return sekarang === path || (path !== '/admin' && path !== '/superadmin' && sekarang.startsWith(`${path}/`));
}

/**
 * The menu item for the current page. When several match (e.g. "Semua Tenant"
 * and "Tambah Tenant" on /superadmin/tenants/buat), the longest path wins.
 */
function hrefAktif(navigasi: NavGrup[], url: string): string | null {
    const kandidat = navigasi.flatMap((grup) => grup.item).filter((item) => cocok(item, url));
    kandidat.sort((a, b) => b.href.length - a.href.length);

    return kandidat[0]?.href ?? null;
}

function TautanNav({ item, aktif, tema, onPilih }: { item: NavItem; aktif: boolean; tema: (typeof TEMA)['admin']; onPilih?: () => void }) {
    const Ikon = IKON[item.ikon] ?? LayoutDashboard;
    const kelas = cn('flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm transition-colors md:min-h-10', aktif ? tema.aktif : tema.biasa);
    const isi = (
        <>
            <Ikon className="size-[18px] shrink-0" aria-hidden="true" />
            <span className="truncate">{item.label}</span>
        </>
    );

    // React pages navigate without a full reload; Blade pages (not ported yet) reload normally.
    return item.inertia ? (
        <Link href={item.href} prefetch className={kelas} onClick={onPilih} aria-current={aktif ? 'page' : undefined}>
            {isi}
        </Link>
    ) : (
        <a href={item.href} className={kelas} onClick={onPilih}>
            {isi}
        </a>
    );
}

function IsiSidebar({ navigasi, url, onPilih }: { navigasi: NavGrup[]; url: string; onPilih?: () => void }) {
    const { area, tenant } = usePage<SharedProps>().props;
    const tema = TEMA[area];
    const aktifSekarang = hrefAktif(navigasi, url);

    return (
        <div className="flex h-full flex-col">
            <div className={cn('flex min-h-16 items-center justify-between gap-2 border-b px-5 pr-14 md:pr-5', tema.garis)}>
                <div className="min-w-0">
                    <div className={cn('line-clamp-2 font-display text-sm font-semibold leading-snug', tema.judul)}>{tenant?.nama_bisnis ?? 'Green Deahan Sport'}</div>
                    {area === 'superadmin' && <div className={cn('text-[0.65rem] font-extrabold uppercase tracking-wide', tema.sub)}>Platform</div>}
                </div>
                {tenant && <Badge variant={varianPaket(tenant.paket)}>{tenant.paket}</Badge>}
            </div>
            <nav className="flex-1 space-y-4 overflow-y-auto px-3 py-4" aria-label="Menu admin">
                {navigasi.map((grup, index) => (
                    <div key={grup.grup ?? index} className={cn(index > 0 && 'border-t pt-4', tema.garis)}>
                        {grup.grup && <div className={cn('px-3 pb-1.5 text-[0.65rem] font-extrabold uppercase tracking-wide', tema.grup)}>{grup.grup}</div>}
                        <div className="space-y-0.5">
                            {grup.item.map((item) => (
                                <TautanNav key={item.href} item={item} aktif={item.href === aktifSekarang} tema={tema} onPilih={onPilih} />
                            ))}
                        </div>
                    </div>
                ))}
            </nav>
            {tenant && (
                <div className={cn('border-t px-5 py-3.5 text-xs', tema.garis, tema.sub)}>
                    Paket <strong className="capitalize text-foreground">{tenant.paket}</strong>
                </div>
            )}
        </div>
    );
}

/**
 * Logout leaves the React app (redirects to a Blade page), so submit a real
 * form with the CSRF token instead of an Inertia request.
 */
function keluar(): void {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    form.appendChild(token);
    document.body.appendChild(form);
    form.submit();
}

function MenuAkun() {
    const { auth } = usePage<SharedProps>().props;
    const nama = auth.user?.name ?? 'Akun';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-11 gap-2 px-2 md:h-10" aria-label={`Menu akun ${nama}`}>
                    <span className="flex size-8 items-center justify-center rounded-full bg-green-pale text-xs font-bold uppercase text-green">{nama.charAt(0)}</span>
                    <span className="hidden max-w-[10rem] truncate font-semibold sm:inline">{nama}</span>
                    <ChevronDown className="text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <DropdownMenuLabel>
                    <div className="truncate font-semibold">{nama}</div>
                    <div className="truncate text-xs font-normal text-muted-foreground">{auth.user?.email}</div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <a href="/profile">
                        <UserRound />
                        Profil & Kata Sandi
                    </a>
                </DropdownMenuItem>
                <DropdownMenuItem className="text-destructive focus:bg-danger-pale focus:text-destructive" onSelect={keluar}>
                    <LogOut />
                    Keluar
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/** Live clock in the app timezone (WIB), ticking every second. */
function JamSekarang() {
    const [sekarang, setSekarang] = useState(() => new Date());

    useEffect(() => {
        const timer = window.setInterval(() => setSekarang(new Date()), 1000);

        return () => window.clearInterval(timer);
    }, []);

    const zona = { timeZone: 'Asia/Jakarta' } as const;
    const tanggal = new Intl.DateTimeFormat('id-ID', { ...zona, weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }).format(sekarang);
    const jam = new Intl.DateTimeFormat('id-ID', { ...zona, hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }).format(sekarang).replace(/\./g, ':');

    return (
        <div className="flex flex-col items-end leading-tight" aria-label={`Waktu sekarang ${tanggal} ${jam} WIB`}>
            <span className="font-mono text-sm font-semibold tabular-nums">
                {jam} <span className="text-[0.65rem] font-medium text-muted-foreground">WIB</span>
            </span>
            <span className="hidden text-[0.7rem] text-muted-foreground sm:block">{tanggal}</span>
        </div>
    );
}

/** Superadmin bell: unread count, the latest notifications, and a link to open each one. */
function Lonceng() {
    const { notifikasi } = usePage<SharedProps>().props;

    if (!notifikasi) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative size-11 md:size-10" aria-label={`Notifikasi, ${notifikasi.belumDibaca} belum dibaca`}>
                    <Bell className="!size-5" />
                    {notifikasi.belumDibaca > 0 && (
                        <span className="absolute right-1 top-1 flex min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[0.65rem] font-bold leading-4 text-white">
                            {notifikasi.belumDibaca > 99 ? '99+' : notifikasi.belumDibaca}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-[min(22rem,calc(100vw-1.5rem))]">
                <div className="flex items-center justify-between gap-3 px-2 py-1.5">
                    <span className="text-sm font-semibold">Notifikasi</span>
                    {notifikasi.belumDibaca > 0 && (
                        <button type="button" className="text-xs underline" onClick={() => router.post(notifikasi.bacaSemuaUrl, {}, { preserveScroll: true })}>
                            Tandai semua dibaca
                        </button>
                    )}
                </div>
                <DropdownMenuSeparator />
                {notifikasi.terbaru.length === 0 ? (
                    <p className="px-2 py-6 text-center text-sm text-muted-foreground">Belum ada notifikasi.</p>
                ) : (
                    notifikasi.terbaru.map((item) => (
                        <DropdownMenuItem key={item.id} className="items-start gap-2 py-2.5" onSelect={() => router.post(item.bukaUrl)}>
                            <span className={cn('mt-1.5 size-2 shrink-0 rounded-full', item.dibaca ? 'bg-transparent' : 'bg-danger')} aria-hidden="true" />
                            <span className="min-w-0">
                                <span className={cn('block truncate text-sm', !item.dibaca && 'font-bold')}>{item.judul}</span>
                                <span className="line-clamp-2 break-words text-xs text-muted-foreground">{item.pesan}</span>
                                <span className="block text-[0.7rem] text-muted-foreground">{item.waktu}</span>
                            </span>
                        </DropdownMenuItem>
                    ))
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function TautanUndangan() {
    const { flash } = usePage<SharedProps>().props;
    if (!flash.invitationLink) {
        return null;
    }

    return (
        <div className="mb-5 rounded-xl border border-gold/30 bg-gold-pale px-4 py-3 text-sm">
            <div className="mb-1.5 font-bold">Link undangan owner untuk {flash.invitationTenant}</div>
            <div className="flex gap-2">
                <input
                    readOnly
                    value={flash.invitationLink}
                    onFocus={(event) => event.currentTarget.select()}
                    className="min-w-0 flex-1 rounded-lg border border-border bg-card px-3 py-2 text-xs text-ink-mid"
                />
                <Button variant="outline" size="sm" onClick={() => navigator.clipboard?.writeText(flash.invitationLink ?? '').then(() => toast.success('Link disalin'))}>
                    <Copy />
                    Salin
                </Button>
            </div>
            <p className="mt-1.5 text-xs text-muted-foreground">Kirim manual ke klien lewat WhatsApp atau email. Berlaku 7 hari.</p>
        </div>
    );
}

interface AdminLayoutProps {
    judul: string;
    deskripsi?: string;
    /** Buttons shown next to the page title */
    aksi?: ReactNode;
    children: ReactNode;
}

/**
 * Shell for every admin page: sidebar on desktop, slide-in menu on phones,
 * sticky top bar with the account menu, flash messages as toasts.
 */
export default function AdminLayout({ judul, deskripsi, aksi, children }: AdminLayoutProps) {
    const { navigasi, flash, area } = usePage<SharedProps>().props;
    const { url } = usePage();
    const [menuTerbuka, setMenuTerbuka] = useState(false);
    const tema = TEMA[area];

    // Keyed on the flash object, which is new on every server response, so the
    // same message twice in a row (e.g. two slots deactivated) still shows.
    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return (
        <div className="min-h-dvh bg-background md:flex">
            <aside className={cn('sticky top-0 hidden h-dvh w-64 shrink-0 md:block', tema.sidebar)}>
                <IsiSidebar navigasi={navigasi} url={url} />
            </aside>

            <Sheet open={menuTerbuka} onOpenChange={setMenuTerbuka}>
                <SheetContent side="left" className={cn('p-0', tema.sidebar)} tutupClassName={tema.tutup}>
                    <SheetTitle className="sr-only">Menu</SheetTitle>
                    <SheetDescription className="sr-only">Navigasi halaman admin</SheetDescription>
                    <IsiSidebar navigasi={navigasi} url={url} onPilih={() => setMenuTerbuka(false)} />
                </SheetContent>
            </Sheet>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-border bg-background/90 px-3 backdrop-blur md:px-8">
                    <Button variant="ghost" size="icon" className="md:hidden" onClick={() => setMenuTerbuka(true)} aria-label="Buka menu">
                        <Menu className="!size-5" />
                    </Button>
                    <div className="min-w-0 flex-1 truncate font-display text-base font-semibold md:hidden">{judul}</div>
                    <div className="hidden flex-1 md:block" />
                    <JamSekarang />
                    <Lonceng />
                    <MenuAkun />
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] md:px-8 md:py-7">
                    <div className="mb-5 flex flex-wrap items-end justify-between gap-3 md:mb-6">
                        <div className="min-w-0">
                            <h1 className="hidden font-display text-xl font-semibold md:block">{judul}</h1>
                            {deskripsi && <p className="text-sm text-muted-foreground md:mt-0.5">{deskripsi}</p>}
                        </div>
                        {aksi && <div className="flex w-full flex-wrap gap-2 sm:w-auto">{aksi}</div>}
                    </div>
                    <TautanUndangan />
                    {children}
                </main>
            </div>

            <Toaster />
        </div>
    );
}
