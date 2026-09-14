/** Slow-drifting blurred gradient blobs used as ambient hero background motion. Purely decorative. */
export default function GradientBlobs({ className = '' }: { className?: string }) {
    return (
        <div aria-hidden className={`pointer-events-none absolute inset-0 overflow-hidden ${className}`}>
            <div className="blob-drift absolute -left-24 -top-24 h-72 w-72 rounded-full bg-brand-300/30 blur-3xl" />
            <div className="blob-drift absolute -right-16 top-6 h-64 w-64 rounded-full bg-brand-400/20 blur-3xl" style={{ animationDelay: '3s' }} />
            <div className="blob-drift absolute -bottom-24 left-1/3 h-80 w-80 rounded-full bg-brand/10 blur-3xl" style={{ animationDelay: '6s' }} />
        </div>
    );
}
