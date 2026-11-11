import FaqAccordion from '../elements/FaqAccordion';
import TextAnimation from '../elements/TextAnimation';
import { kontakFaqs } from '../../../data/kontak';

/** Kontak FAQ, below the form section, in Fuzio's FAQ page layout (two columns). */
export default function KontakFaqSec() {
    return (
        <section className="faq-page kontak-faq-sec sec-padding pt-0">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">FAQ</h2>
                </TextAnimation>
                <FaqAccordion items={kontakFaqs} idPrefix="kontak-faq" twoColumns />
            </div>
        </section>
    );
}
