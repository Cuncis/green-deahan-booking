import { contactCards } from '../../../data/kontak';

/** Port of Fuzio's ContactCardSec: phone/WhatsApp, email and location cards. */
export default function ContactCardSec() {
    return (
        <section className="contact-card-sec sec-padding">
            <div className="container">
                <div className="row gy-3 gy-md-0">
                    {contactCards.map((card) => (
                        <div key={card.id} className="col-md-4">
                            <div className="contact-card text-center rounded-3 h-100">
                                <span className="icon rounded-pill">
                                    <i className={`feather-icon ${card.icon}`}></i>
                                </span>
                                <h3>{card.title}</h3>
                                {card.items ? (
                                    <ul className="list-unstyled">
                                        {card.items.map((item) => (
                                            <li key={item.label}>
                                                {item.href ? (
                                                    <a
                                                        href={item.href}
                                                        className={item.underline ? 'gd-link-underline' : undefined}
                                                        {...(item.href.startsWith('http') ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                                                    >
                                                        {item.label}
                                                    </a>
                                                ) : (
                                                    <span className="contact-card-text">{item.label}</span>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p>{card.content}</p>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
