import { GD_EMAIL, GD_HOURS, GD_PHONE_DISPLAY, GD_PHONE_TEL, menuData, socialLinks } from '../../data/greendeahan';

/** Port of Fuzio's Footer, as customized in the Fuzio clone. */
export default function Footer() {
    return (
        <footer className="footer bg jarallax bg-cover">
            <div className="container">
                <div className="row">
                    <div className="col-lg-3 col-sm-6">
                        <div className="footer-widget">
                            <h3 className="widget-title text-uppercase text-white">Hubungi Kami</h3>
                            <p>Jasa pembuatan lapangan futsal, mini soccer, padel &amp; badminton sejak 2010.</p>
                            <ul className="mt-4">
                                <li className="d-flex align-items-center">
                                    <span>
                                        <i className="feather-icon icon-phone-call"></i>
                                    </span>
                                    <a href={`tel:${GD_PHONE_TEL}`}>{GD_PHONE_DISPLAY}</a>
                                </li>
                                <li className="d-flex align-items-center">
                                    <span>
                                        <i className="feather-icon icon-mail"></i>
                                    </span>
                                    <a href={`mailto:${GD_EMAIL}`}>{GD_EMAIL}</a>
                                </li>
                                <li className="d-flex align-items-center">
                                    <span>
                                        <i className="feather-icon icon-map-pin"></i>
                                    </span>
                                    {GD_HOURS}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div className="col-lg-3 offset-lg-1 col-sm-6">
                        <div className="footer-widget">
                            <h3 className="widget-title text-uppercase text-white">Halaman</h3>
                            <ul>
                                {menuData.map((item) => (
                                    <li key={item.path}>
                                        <a href={item.path}>{item.label}</a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                    <div className="col-lg-3 offset-lg-1 col-sm-6">
                        <div className="footer-widget">
                            <h3 className="widget-title text-uppercase text-white">Media Sosial</h3>
                            <ul>
                                {socialLinks.map((social) => (
                                    <li key={social.href} className="d-flex align-items-center">
                                        <span>
                                            <i>
                                                <svg viewBox="0 0 24 24" width={18} height={18} fill="currentColor" aria-hidden="true">
                                                    <path d={social.iconPath} />
                                                </svg>
                                            </i>
                                        </span>
                                        <a href={social.href} target="_blank" rel="noopener noreferrer" aria-label={social.label}>
                                            {social.handle}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
                <div className="row pt-5 my-0 mt-5">
                    <div className="col-lg-12 pt-4 copy-right">
                        <p className="m-0 text-center">Copyright © 2026 Green Deahan Sport. Seluruh hak cipta dilindungi.</p>
                    </div>
                </div>
            </div>
        </footer>
    );
}
