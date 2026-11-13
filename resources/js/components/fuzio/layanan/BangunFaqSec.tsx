import FaqAccordion from '../elements/FaqAccordion';
import TextAnimation from '../elements/TextAnimation';
import { kontakFaqs } from '../../../data/kontak';

/** The building-related questions from the Kontak FAQ (same answers, one source). */
const PERTANYAAN = ['biaya', 'lama-proses', 'pembayaran', 'garansi', 'luar-kota', 'pilih-lapangan'];

export default function BangunFaqSec() {
    const faq = PERTANYAAN.map((id) => kontakFaqs.find((item) => item.id === id)).filter((item) => item !== undefined);

    return (
        <section className="faq-page kontak-faq-sec sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Pertanyaan Seputar Bangun Lapangan</h2>
                </TextAnimation>
                <FaqAccordion items={faq} idPrefix="bangun-faq" twoColumns />
            </div>
        </section>
    );
}
