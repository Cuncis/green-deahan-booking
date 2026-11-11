import { useEffect, useState, type FormEvent } from 'react';
import CustomFormSelect from '../elements/CustomFormSelect';
import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { contactFormBudgets, contactFormSubjects, contactFormSuccess, contactFormText, contactWaMessage, googleMapsUrl } from '../../../data/kontak';

/**
 * Port of Fuzio's Contact2Sec: Google Map plus a form that opens WhatsApp
 * with the details filled in. The SweetAlert popup is replaced by an inline
 * success message (no extra package).
 */
export default function ContactFormSec() {
    const [selectedSubject, setSelectedSubject] = useState<string>(contactFormSubjects[0].value);
    const [selectedBudget, setSelectedBudget] = useState<string>(contactFormBudgets[0].value);
    const [isSent, setIsSent] = useState(false);

    useEffect(() => {
        if (!isSent) {
            return;
        }

        const timer = window.setTimeout(() => setIsSent(false), 6000);

        return () => window.clearTimeout(timer);
    }, [isSent]);

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);

        const name = ((formData.get('name') as string) || '').trim();
        const city = ((formData.get('city') as string) || '').trim();
        const message = ((formData.get('message') as string) || '').trim();

        if (!name || !city) {
            return;
        }

        // Open WhatsApp right away (inside the click) so pop-up blockers allow it.
        window.open(contactWaMessage({ name, lapangan: selectedSubject, city, budget: selectedBudget, message }), '_blank', 'noopener,noreferrer');
        setIsSent(true);
        form.reset();
        setSelectedSubject(contactFormSubjects[0].value);
        setSelectedBudget(contactFormBudgets[0].value);
    };

    return (
        <>
            <iframe
                className="google-map"
                title="Lokasi Green Deahan di Google Maps"
                src={googleMapsUrl}
                style={{ border: 0 }}
                allowFullScreen
                loading="lazy"
                referrerPolicy="strict-origin-when-cross-origin"
            ></iframe>

            <section className="contact2-sec sec-padding">
                <div className="container">
                    <div className="row">
                        <div className="col-lg-6">
                            <div className="contact-txt">
                                <TextAnimation>
                                    <h2 className="sec-title primary line-left">{contactFormText.title}</h2>
                                </TextAnimation>
                                <p>{contactFormText.description}</p>
                            </div>
                        </div>
                        <div className="col-lg-6">
                            <div className="contact-form">
                                <form id="contact-form" className="row" onSubmit={handleSubmit}>
                                    <div className="col-lg-6 form-group">
                                        <i className="feather-icon icon-user"></i>
                                        <input className="form-control" name="name" type="text" placeholder="Nama Lengkap *" aria-label="Nama lengkap" autoComplete="name" required />
                                    </div>
                                    <div className="col-lg-6 form-group">
                                        <i className="feather-icon icon-map-pin"></i>
                                        <input className="form-control" name="city" type="text" placeholder="Kota / Lokasi Proyek *" aria-label="Kota atau lokasi proyek" required />
                                    </div>
                                    <div className="col-12 form-group">
                                        <i className="feather-icon icon-clipboard"></i>
                                        <CustomFormSelect optionArray={contactFormSubjects} value={selectedSubject} onChange={setSelectedSubject} aria-label="Jenis lapangan yang diminati" />
                                    </div>
                                    <div className="col-12 form-group">
                                        <i className="feather-icon icon-credit-card"></i>
                                        <CustomFormSelect optionArray={contactFormBudgets} value={selectedBudget} onChange={setSelectedBudget} aria-label="Estimasi budget" />
                                    </div>
                                    <div className="col-lg-12 form-group">
                                        <textarea className="form-control" name="message" rows={6} placeholder="Pesan / detail kebutuhan (opsional)" aria-label="Pesan atau detail kebutuhan"></textarea>
                                    </div>
                                    <div className="col-lg-12">
                                        <button type="submit" className="btn btn-primary btn-whatsapp mt-4">
                                            <WhatsAppIcon className="me-2" />
                                            Kirim via WhatsApp
                                        </button>
                                        {isSent && (
                                            <div className="alert alert-success mt-4 mb-0" role="status">
                                                <strong>{contactFormSuccess.title}</strong> {contactFormSuccess.message}
                                            </div>
                                        )}
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
