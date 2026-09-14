/** Shared base styles for every /v2 React page: Poppins headings, Inter body, scroll-reveal, premium micro-animations. */
export default function PageStyles() {
    return (
        <style>{`
            .reveal { opacity: 0; transform: translateY(22px); transition: opacity .65s ease, transform .65s ease; }
            .reveal.visible { opacity: 1; transform: translateY(0); }
            body { font-family: 'Inter', sans-serif; }
            h1, h2, h3, h4, h5, h6 { font-family: 'Poppins', sans-serif; }

            @keyframes float-y { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
            .float-anim { animation: float-y 4.2s ease-in-out infinite; }

            @keyframes pulse-glow { 0%, 100% { opacity: .35; transform: scale(1); } 50% { opacity: .65; transform: scale(1.1); } }
            .pulse-glow { animation: pulse-glow 3.2s ease-in-out infinite; }

            @keyframes blob-drift {
                0% { transform: translate(0, 0) scale(1); }
                33% { transform: translate(24px, -18px) scale(1.06); }
                66% { transform: translate(-18px, 14px) scale(0.96); }
                100% { transform: translate(0, 0) scale(1); }
            }
            .blob-drift { animation: blob-drift 14s ease-in-out infinite; }

            .draw-path { stroke-dasharray: 300; stroke-dashoffset: 300; transition: stroke-dashoffset 1.1s ease; }
            .reveal.visible .draw-path { stroke-dashoffset: 0; }
        `}</style>
    );
}
