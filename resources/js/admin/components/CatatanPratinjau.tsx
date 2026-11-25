import { Copy } from 'lucide-react';
import { Fragment, type ReactNode } from 'react';
import { toast } from 'sonner';
import { Button } from './ui/button';

type Blok =
    | { tipe: 'judul'; teks: string }
    | { tipe: 'subjudul'; teks: string }
    | { tipe: 'paragraf'; baris: string[] }
    | { tipe: 'daftar'; urut: boolean; mulai: number; butir: string[] }
    | { tipe: 'kode'; baris: string[] }
    | { tipe: 'prompt'; teks: string };

/** A heading line: everything outside parentheses is uppercase ("STRUKTUR KAMPANYE", "PROMPT (bahasa Indonesia)"). */
function judulBagian(baris: string): boolean {
    const inti = baris
        .replace(/\([^)]*\)/g, '')
        .replace(/:$/, '')
        .trim();

    return inti.length >= 3 && !/[a-z]/.test(inti) && /[A-Z]{3}/.test(inti);
}

const BUTIR = /^\s*[-•]\s+(.*)$/;
const NOMOR = /^\s*(\d+)\.\s+(.*)$/;

/**
 * Turns the plain-text note into display blocks: headings, lists, copyable prompt boxes,
 * code snippets, and paragraphs. The text is never treated as HTML.
 */
export function uraiCatatan(isi: string): Blok[] {
    const blok: Blok[] = [];
    let paragraf: string[] = [];
    let daftar: { urut: boolean; mulai: number; butir: string[] } | null = null;
    let kode: string[] | null = null;
    // After headings like "PROMPT" or "TEKS UTAMA 1", everything up to the next heading is one copyable box.
    let salin: string[] | null = null;

    const tutupParagraf = () => {
        if (paragraf.length === 0) return;
        blok.push({ tipe: 'paragraf', baris: paragraf });
        paragraf = [];
    };
    const tutupDaftar = () => {
        if (daftar) blok.push({ tipe: 'daftar', ...daftar });
        daftar = null;
    };
    const tutupKode = () => {
        if (kode) blok.push({ tipe: 'kode', baris: kode });
        kode = null;
    };
    const tutupSalin = () => {
        if (salin === null) return;
        const teks = salin.join('\n').trim();
        if (teks !== '') blok.push({ tipe: 'prompt', teks });
        salin = null;
    };
    const tutupSemua = () => {
        tutupSalin();
        tutupParagraf();
        tutupDaftar();
        tutupKode();
    };

    for (const mentah of isi.replace(/\r\n?/g, '\n').split('\n')) {
        const baris = mentah.replace(/\s+$/, '');

        if (salin !== null && !judulBagian(baris.trim())) {
            salin.push(baris);
            continue;
        }

        if (baris.trim() === '') {
            tutupSemua();
            continue;
        }

        if (kode) {
            kode.push(baris);
            continue;
        }

        if (/^\s*</.test(baris)) {
            tutupSemua();
            kode = [baris];
            continue;
        }

        if (judulBagian(baris.trim())) {
            tutupSemua();
            blok.push({ tipe: 'judul', teks: baris.trim() });
            salin = /^((NEGATIVE )?PROMPT|TEKS UTAMA|PESAN PEMBUKA)\b/.test(baris.trim()) ? [] : null;
            continue;
        }

        const butir = BUTIR.exec(baris);
        const nomor = NOMOR.exec(baris);

        if (butir || nomor) {
            tutupParagraf();
            const urut = nomor !== null;
            if (daftar && daftar.urut !== urut) tutupDaftar();
            daftar ??= { urut, mulai: nomor ? Number(nomor[1]) : 1, butir: [] };
            daftar.butir.push((butir ?? nomor!)[butir ? 1 : 2]);
            continue;
        }

        tutupDaftar();
        paragraf.push(baris.trim());
    }

    tutupSemua();

    // "1. Kontraktor Futsal" followed by a bullet list is a group title, not a one-item numbered list.
    return blok.map((item, indeks) => {
        const berikut = blok[indeks + 1];

        return item.tipe === 'daftar' && item.urut && item.butir.length === 1 && berikut?.tipe === 'daftar' && !berikut.urut
            ? ({ tipe: 'subjudul', teks: `${item.mulai}. ${item.butir[0]}` } as Blok)
            : item;
    });
}

const URL_POLA = /(https?:\/\/[^\s<>"']+[^\s<>"'.,;:!?)])/g;

/** Plain text with http(s) links made clickable. Anything else stays text. */
function Inline({ teks }: { teks: string }): ReactNode {
    return teks.split(URL_POLA).map((bagian, indeks) =>
        indeks % 2 === 1 ? (
            <a key={indeks} href={bagian} target="_blank" rel="noopener noreferrer" className="break-all text-green underline underline-offset-2">
                {bagian}
            </a>
        ) : (
            <Fragment key={indeks}>{bagian}</Fragment>
        ),
    );
}

/** "Tujuan: Prospek" shows "Tujuan:" in bold. URLs ("https:") are left alone. */
function BarisBerlabel({ baris }: { baris: string }): ReactNode {
    const cocok = /^([A-Z][^:\n]{1,38}):\s+(.+)$/.exec(baris);

    if (!cocok || /^https?$/i.test(cocok[1])) return <Inline teks={baris} />;

    return (
        <>
            <strong className="font-semibold text-ink">{cocok[1]}:</strong> <Inline teks={cocok[2]} />
        </>
    );
}

async function salinTeks(teks: string, pesan: string) {
    try {
        await navigator.clipboard.writeText(teks);
        toast.success(pesan);
    } catch {
        toast.error('Gagal menyalin, salin manual dengan menyeleksi teksnya.');
    }
}

/** Read-only, comfortable-to-read view of a note. */
export default function CatatanPratinjau({ isi }: { isi: string }) {
    return (
        <article className="min-w-0 space-y-4 text-[15px] leading-7 text-ink-mid">
            {uraiCatatan(isi).map((item, indeks) => {
                switch (item.tipe) {
                    case 'judul':
                        return (
                            <h3 key={indeks} className="!mt-7 border-b border-border pb-1.5 font-display text-base font-semibold tracking-wide text-green first:!mt-0">
                                {item.teks}
                            </h3>
                        );
                    case 'subjudul':
                        return (
                            <h4 key={indeks} className="!mb-1 !mt-5 font-semibold text-ink">
                                {item.teks}
                            </h4>
                        );
                    case 'daftar': {
                        const Penanda = item.urut ? 'ol' : 'ul';

                        return (
                            <Penanda
                                key={indeks}
                                start={item.urut ? item.mulai : undefined}
                                className={item.urut ? 'list-decimal space-y-1 pl-6 marker:font-semibold marker:text-green' : 'list-disc space-y-1 pl-6 marker:text-green'}
                            >
                                {item.butir.map((butir, nomor) => (
                                    <li key={nomor} className="pl-1 [overflow-wrap:anywhere]">
                                        <Inline teks={butir} />
                                    </li>
                                ))}
                            </Penanda>
                        );
                    }
                    case 'prompt':
                        return (
                            <div key={indeks} className="min-w-0 rounded-xl border border-border bg-muted">
                                <div className="flex items-center justify-between gap-2 border-b border-border px-3 py-1.5">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Teks untuk disalin</span>
                                    <Button type="button" variant="ghost" size="sm" onClick={() => void salinTeks(item.teks, 'Teks disalin')}>
                                        <Copy />
                                        Salin
                                    </Button>
                                </div>
                                <p className="whitespace-pre-wrap px-4 [overflow-wrap:anywhere] py-3 text-sm leading-relaxed text-ink">{item.teks}</p>
                            </div>
                        );
                    case 'kode':
                        return (
                            <div key={indeks} className="min-w-0 rounded-xl border border-border bg-ink text-white">
                                <div className="flex items-center justify-between gap-2 border-b border-white/15 px-3 py-1.5">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-white/60">Kode</span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-white hover:bg-white/10 hover:text-white"
                                        onClick={() => void salinTeks(item.baris.join('\n'), 'Kode disalin')}
                                    >
                                        <Copy />
                                        Salin
                                    </Button>
                                </div>
                                <pre className="overflow-x-auto px-4 py-3 font-mono text-xs leading-relaxed">{item.baris.join('\n')}</pre>
                            </div>
                        );
                    default:
                        return (
                            <p key={indeks} className="[overflow-wrap:anywhere]">
                                {item.baris.map((baris, nomor) => (
                                    <Fragment key={nomor}>
                                        {nomor > 0 && <br />}
                                        <BarisBerlabel baris={baris} />
                                    </Fragment>
                                ))}
                            </p>
                        );
                }
            })}
        </article>
    );
}
