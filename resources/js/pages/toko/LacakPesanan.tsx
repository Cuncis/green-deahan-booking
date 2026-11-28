import "../../../fuzio/css/fuzio.css";
import { useForm } from "@inertiajs/react";
import type { FormEvent } from "react";
import Footer from "../../components/fuzio/Footer";
import Header from "../../components/fuzio/Header";
import PageHeader from "../../components/fuzio/PageHeader";
import ScrollToTop from "../../components/fuzio/elements/ScrollToTop";
import { GD_CDN } from "../../data/greendeahan";

/**
 * /lacak-pesanan: the shop has no customer accounts, so a customer who lost
 * the status link finds the order with its code plus the email or phone used
 * at checkout. The server redirects to the order's status page.
 */
export default function LacakPesanan() {
    const form = useForm({ kode: "", kontak: "" });

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        form.post("/lacak-pesanan", { preserveScroll: true });
    };

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader
                title="Lacak Pesanan"
                imgSrc={`${GD_CDN}/padel1-img.png`}
                crumbs={[
                    { label: "Toko", href: "/toko" },
                    { label: "Lacak Pesanan" },
                ]}
            />

            <section className="sec-padding gd-toko">
                <div className="container">
                    <div className="row justify-content-center">
                        <div className="col-lg-6 col-md-8">
                            <form
                                className="form checkout-form gd-form-toko"
                                onSubmit={kirim}
                                noValidate
                            >
                                <div className="gd-kartu-form rounded p-lg-5 p-3">
                                    <h2 className="h4 mb-2">
                                        Cek status pesananmu
                                    </h2>
                                    <p className="mb-4">
                                        Masukkan kode pesanan (contoh:{" "}
                                        <span className="text-nowrap">
                                            GD-20261127-K7M2QX
                                        </span>
                                        ) dan email atau nomor HP yang kamu
                                        pakai saat checkout. Kode pesanan ada di
                                        email konfirmasi pembayaran.
                                    </p>

                                    <div className="mb-3">
                                        <label htmlFor="kode">
                                            Kode pesanan*
                                        </label>
                                        <input
                                            id="kode"
                                            name="kode"
                                            value={form.data.kode}
                                            onChange={(event) =>
                                                form.setData(
                                                    "kode",
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="GD-20261127-K7M2QX"
                                            autoComplete="off"
                                            autoCapitalize="characters"
                                            spellCheck={false}
                                            className={
                                                form.errors.kode
                                                    ? "is-invalid"
                                                    : ""
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.kode,
                                            )}
                                            aria-describedby={
                                                form.errors.kode
                                                    ? "kode-galat"
                                                    : undefined
                                            }
                                            required
                                        />
                                        {form.errors.kode && (
                                            <div
                                                id="kode-galat"
                                                className="galat"
                                                role="alert"
                                            >
                                                {form.errors.kode}
                                            </div>
                                        )}
                                    </div>

                                    <div className="mb-3">
                                        <label htmlFor="kontak">
                                            Email atau nomor HP*
                                        </label>
                                        <input
                                            id="kontak"
                                            name="kontak"
                                            value={form.data.kontak}
                                            onChange={(event) =>
                                                form.setData(
                                                    "kontak",
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="nama@email.com atau 08xxxxxxxxxx"
                                            autoComplete="email"
                                            className={
                                                form.errors.kontak
                                                    ? "is-invalid"
                                                    : ""
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.kontak,
                                            )}
                                            aria-describedby={
                                                form.errors.kontak
                                                    ? "kontak-galat"
                                                    : undefined
                                            }
                                            required
                                        />
                                        {form.errors.kontak && (
                                            <div
                                                id="kontak-galat"
                                                className="galat"
                                                role="alert"
                                            >
                                                {form.errors.kontak}
                                            </div>
                                        )}
                                    </div>

                                    <button
                                        type="submit"
                                        className="btn btn-primary btn-cta d-block w-100 mt-2"
                                        disabled={form.processing}
                                    >
                                        {form.processing
                                            ? "Mencari..."
                                            : "Lacak Pesanan"}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
