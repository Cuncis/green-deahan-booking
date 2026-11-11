import { useEffect, useState, useCallback } from "react"; 

function throttle<T extends (...args: unknown[]) => void>(
    fn: T,
    delay: number
): (...args: Parameters<T>) => void {
    let last = 0;

    return (...args: Parameters<T>) => {
        const now = Date.now();
        if (now - last >= delay) {
            last = now;
            fn(...args);
        }
    };
}

const ScrollToTop = () => {
    const [visible, setVisible] = useState(false);

    const toggleVisibility = useCallback(() => {
        if (window.scrollY > 200) {
            setVisible(true);
        } else {
            setVisible(false);
        }
    }, []);
 

    useEffect(() => {
        const throttledScroll = throttle(toggleVisibility, 200);

        window.addEventListener("scroll", throttledScroll);
        return () => {
            window.removeEventListener("scroll", throttledScroll);
        };
    }, [toggleVisibility]);

    const scrollToTop = (e: React.MouseEvent<HTMLDivElement>) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    return (
        <div onClick={scrollToTop} className={`back-top ${visible ? "back-top-show" : ""}`}><i className="feather-icon icon-chevron-up"></i></div>
    );
};

export default ScrollToTop;