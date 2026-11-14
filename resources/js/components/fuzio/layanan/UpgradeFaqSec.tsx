import FaqAccordion from '../elements/FaqAccordion';
import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { waLink } from '../../../data/greendeahan';
import { upgradeFaqs } from '../../../data/upgradeLapangan';

export default function UpgradeFaqSec() {
    return (
        <section className="faq-page kontak-faq-sec sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Pertanyaan Seputar Upgrade</h2>
                </TextAnimation>
                <FaqAccordion items={upgradeFaqs} idPrefix="upgrade-faq" twoColumns />
                <div className="text-center mt-5">
                    <p className="mb-3">Masih ada pertanyaan? Tim kami respon dalam 1 jam.</p>
                    <a
                        href={waLink('Halo Green Deahan, saya punya pertanyaan soal upgrade lapangan.')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-primary btn-whatsapp"
                    >
                        <WhatsAppIcon className="me-2" />
                        Chat WhatsApp
                    </a>
                </div>
            </div>
        </section>
    );
}
