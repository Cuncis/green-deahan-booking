import { router, useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Button } from '../ui/button';
import { Card, CardHeader, CardTitle } from '../ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../ui/dialog';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';

export interface StafRingkas {
    id: number;
    nama: string;
    email: string;
    role: string;
    ubahRoleUrl: string;
}

const ROLE = [
    { value: 'owner', label: 'Owner' },
    { value: 'manager', label: 'Manager' },
    { value: 'staff', label: 'Staff' },
];

function PilihRole({ staf }: { staf: StafRingkas }) {
    return (
        <Select value={staf.role} onValueChange={(role) => router.put(staf.ubahRoleUrl, { role }, { preserveScroll: true })}>
            <SelectTrigger className="w-full md:w-36" aria-label={`Role ${staf.nama}`}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {ROLE.map((role) => (
                    <SelectItem key={role.value} value={role.value}>
                        {role.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

/** "Staf & Operator" (Premium): list with inline role change, add dialog. */
export default function PanelStaf({ staf }: { staf: StafRingkas[] }) {
    const [dialogTerbuka, setDialogTerbuka] = useState(false);
    const form = useForm({ email: '', role: 'staff' });

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/staf', {
            preserveScroll: true,
            onSuccess: () => {
                setDialogTerbuka(false);
                form.reset();
            },
        });
    };

    return (
        <Card id="staf" className="mb-6 scroll-mt-24 overflow-hidden">
            <CardHeader className="flex-row items-center justify-between gap-3 border-b border-border py-3">
                <CardTitle className="text-xs uppercase tracking-wide text-green">Staf & Operator</CardTitle>
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() => {
                        form.reset();
                        form.clearErrors();
                        setDialogTerbuka(true);
                    }}
                >
                    <UserPlus />
                    Tambah Staf
                </Button>
            </CardHeader>

            {staf.length === 0 ? (
                <p className="px-5 py-6 text-center text-sm text-muted-foreground">Belum ada staf terdaftar.</p>
            ) : (
                <ul className="divide-y divide-cream">
                    {staf.map((item) => (
                        <li key={item.id} className="flex flex-col gap-3 px-4 py-3.5 md:flex-row md:items-center md:justify-between md:px-5">
                            <div className="min-w-0">
                                <div className="truncate font-bold">{item.nama}</div>
                                <div className="truncate text-sm text-muted-foreground">{item.email}</div>
                            </div>
                            <PilihRole staf={item} />
                        </li>
                    ))}
                </ul>
            )}

            <Dialog open={dialogTerbuka} onOpenChange={setDialogTerbuka}>
                <DialogContent>
                    <form onSubmit={simpan} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>Tambah Staf</DialogTitle>
                            <DialogDescription>Orangnya harus sudah punya akun. Masukkan email akunnya dan pilih role.</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-1.5">
                            <Label htmlFor="staf-email">Email akun</Label>
                            <Input
                                id="staf-email"
                                type="email"
                                inputMode="email"
                                autoComplete="off"
                                placeholder="nama@email.com"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                aria-invalid={form.errors.email ? true : undefined}
                                required
                            />
                            {form.errors.email && <p className="text-sm text-destructive">{form.errors.email}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="staf-role">Role</Label>
                            <Select value={form.data.role} onValueChange={(role) => form.setData('role', role)}>
                                <SelectTrigger id="staf-role">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {ROLE.map((role) => (
                                        <SelectItem key={role.value} value={role.value}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Simpan Staf
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </Card>
    );
}
