import { useState } from 'react';

interface FaqItem {
    id: string;
    question: string;
    answer: string;
}

interface FaqAccordionProps {
    items: FaqItem[];
    /** prefix for the element ids, so two accordions on one page don't clash */
    idPrefix: string;
    /** split the items over two side-by-side columns on large screens (Fuzio's FAQ page layout) */
    twoColumns?: boolean;
}

/**
 * Port of Fuzio's FAQ accordion (features/faq/FaqPage.tsx): one answer open
 * at a time (across both columns), the first one open by default.
 */
export default function FaqAccordion({ items, idPrefix, twoColumns = false }: FaqAccordionProps) {
    const [openId, setOpenId] = useState(items[0]?.id ?? '');

    const renderItems = (columnItems: FaqItem[]) => (
        <div className="accordion">
            {columnItems.map((item) => {
                const isOpen = openId === item.id;

                return (
                    <div className="faq-item bg-mute" key={item.id}>
                        <h3 className="accordion-header" id={`${idPrefix}-heading-${item.id}`}>
                            <button
                                className={`accordion-button bg-mute ${isOpen ? '' : 'collapsed'}`}
                                type="button"
                                aria-expanded={isOpen}
                                aria-controls={`${idPrefix}-collapse-${item.id}`}
                                onClick={() => setOpenId(isOpen ? '' : item.id)}
                            >
                                {item.question}
                            </button>
                        </h3>
                        <div
                            id={`${idPrefix}-collapse-${item.id}`}
                            className={`accordion-collapse collapse ${isOpen ? 'show' : ''}`}
                            role="region"
                            aria-labelledby={`${idPrefix}-heading-${item.id}`}
                        >
                            <div className="accordion-body">
                                <p>{item.answer}</p>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );

    if (!twoColumns) {
        return renderItems(items);
    }

    const half = Math.ceil(items.length / 2);

    return (
        <div className="row">
            <div className="col-lg-6">{renderItems(items.slice(0, half))}</div>
            <div className="col-lg-6">{renderItems(items.slice(half))}</div>
        </div>
    );
}
