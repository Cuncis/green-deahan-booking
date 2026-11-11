import { useState, type ReactNode } from 'react';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import CaraKerjaTimeline from './CaraKerjaTimeline';
import CompareTable from './CompareTable';
import IndonesiaMap from './IndonesiaMap';
import { waLink } from '../../../data/greendeahan';

interface Tab {
    id: string;
    label: string;
    /** What the left column shows for this tab */
    visual: ReactNode;
    title: string;
    text: string;
    points: string[];
    waMessage: string;
}

/** Tab content from the "Cara Kerja", "Kenapa Pilih Kami?" and "Cakupan Wilayah Layanan" sections. */
const tabs: Tab[] = [
    {
        id: 'cara-kerja',
        label: 'Cara Kerja',
        visual: <CaraKerjaTimeline />,
        title: 'Proses Mudah, Anda Cukup Duduk Santai',
        text: 'Kami handle semua proses dari awal sampai lapangan siap beroperasi, dengan transparansi penuh di setiap tahap.',
        points: ['Bayar DP di awal, pelunasan setelah lapangan selesai 100%', 'Risiko pengerjaan kami yang tanggung', 'Garansi resmi konstruksi & material'],
        waMessage: 'Halo Green Deahan, saya ingin konsultasi proses pembangunan lapangan.',
    },
    {
        id: 'kenapa-kami',
        label: 'Kenapa Kami',
        visual: <CompareTable />,
        title: 'Kenapa Pilih Green Deahan?',
        text: 'Survey, desain, perizinan, konstruksi, hingga finishing, semua kami urus. Anda tidak perlu pusing koordinasi dengan banyak kontraktor.',
        points: ['Pengalaman 16+ tahun, sejak 2010', '100+ proyek selesai di 15+ kota', 'Konsultasi, survey lokasi & RAB 100% gratis'],
        waMessage: 'Halo Green Deahan, saya ingin konsultasi gratis untuk pembangunan lapangan.',
    },
    {
        id: 'cakupan-wilayah',
        label: 'Cakupan Wilayah',
        visual: <IndonesiaMap />,
        title: 'Melayani Seluruh Indonesia',
        text: 'Tim kami siap bergerak ke mana pun Anda butuhkan. Lapangan futsal, mini soccer, padel, dan badminton sudah kami bangun di 15+ kota di Indonesia.',
        points: ['Jabodetabek, Jawa Tengah & Jawa Timur', 'Sumatera, Kalimantan, dan Sulawesi', 'Bali, Nusa Tenggara, Maluku & Papua'],
        waMessage: 'Halo Green Deahan, apakah melayani pembangunan lapangan di daerah saya?',
    },
];

/** Port of Fuzio's AboutTab: three tabs, each with a visual on the left and text plus a WhatsApp CTA on the right. */
export default function AboutTab() {
    const [activeTab, setActiveTab] = useState(tabs[0].id);

    return (
        <section className="about-tab sec-padding">
            <div className="container">
                <div className="row">
                    <div className="col-lg-12">
                        <ul className="nav nav-tabs" role="tablist">
                            {tabs.map((tab) => (
                                <li key={tab.id} className="nav-item" role="presentation">
                                    <button
                                        className={`nav-link ${activeTab === tab.id ? 'active' : ''}`}
                                        id={`${tab.id}-tab`}
                                        type="button"
                                        role="tab"
                                        aria-controls={tab.id}
                                        aria-selected={activeTab === tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                    >
                                        {tab.label}
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="tab-content pt-5">
                            {tabs.map((tab) => (
                                <div key={tab.id} className={`tab-pane fade ${activeTab === tab.id ? 'show active' : ''}`} id={tab.id} role="tabpanel" aria-labelledby={`${tab.id}-tab`}>
                                    <div className="row">
                                        <div className="col-lg-6">{tab.visual}</div>
                                        <div className="col-lg-6">
                                            <div className="tab-txt ps-lg-4">
                                                <h3>{tab.title}</h3>
                                                <p>{tab.text}</p>
                                                <ul>
                                                    {tab.points.map((point) => (
                                                        <li key={point}>{point}</li>
                                                    ))}
                                                </ul>
                                                <a href={waLink(tab.waMessage)} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-whatsapp mt-4">
                                                    <WhatsAppIcon className="me-2" />
                                                    Konsultasi Gratis
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
