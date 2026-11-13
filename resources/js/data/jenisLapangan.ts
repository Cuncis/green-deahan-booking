import SoccerWhite from '../../fuzio/images/icons/soccer-w.png';
import MiniSoccerIcon from '../../fuzio/images/mini-soccer.png';
import PadelIcon from '../../fuzio/images/padel.png';
import BadmintonIcon from '../../fuzio/images/badminton.png';

export interface JenisLapangan {
    nama: string;
    icon: string;
    /** soccer-w.png is white, so it is inverted to match the other (black) icons. */
    invert: boolean;
    delay: number;
    /** Used in the WhatsApp message: "Halo, saya ingin konsultasi {topik}". */
    topik: string;
    deskripsi: string;
}

/** The 4 court types Green Deahan builds (homepage FeatureSec and /bangun-lapangan). */
export const jenisLapangan: JenisLapangan[] = [
    { nama: 'Futsal', icon: SoccerWhite, invert: true, delay: 100, topik: 'pembuatan lapangan futsal', deskripsi: 'Lantai interlock atau rumput sintetis, lengkap dengan LED, pagar, gawang & net.' },
    { nama: 'Mini Soccer', icon: MiniSoccerIcon, invert: false, delay: 200, topik: 'pembuatan lapangan mini soccer', deskripsi: 'Rumput sintetis premium grade A dengan sistem drainase profesional.' },
    { nama: 'Padel', icon: PadelIcon, invert: false, delay: 300, topik: 'pembuatan lapangan padel', deskripsi: 'Standar internasional IFF: rangka baja galvanis & kaca tempered.' },
    { nama: 'Badminton', icon: BadmintonIcon, invert: false, delay: 400, topik: 'pembuatan lapangan badminton', deskripsi: 'Lantai interlock atau kayu keras, non-slip, LED anti-silau.' },
];
