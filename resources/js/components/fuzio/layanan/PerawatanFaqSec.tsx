import FaqAccordion from '../elements/FaqAccordion';
import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { waLink } from '../../../data/greendeahan';
import { perawatanFaqs } from '../../../data/perawatan';

export default function PerawatanFaqSec() {
    return (
        <section className="faq-page kontak-faq-sec sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Pertanyaan Seputar Perawatan</h2>
                </TextAnimation>
                <FaqAccordion items={perawatanFaqs} idPrefix="perawatan-faq" twoColumns />
                <div className="text-center mt-5">
                    <p className="mb-3">Masih ada pertanyaan? Tim kami respon dalam 1 jam.</p>
                    <a href={waLink('Halo Green Deahan, saya punya pertanyaan soal perawatan lapangan.')} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-whatsapp">
                        <WhatsAppIcon className="me-2" />
                        Chat WhatsApp
                    </a>
                </div>
            </div>
        </section>
    );
}
