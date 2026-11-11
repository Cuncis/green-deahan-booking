import { useEffect, useId, useRef, useState, type KeyboardEvent } from 'react';

export interface ComboOption {
    value: string;
    label: string;
}

interface ComboFieldProps {
    id: string;
    label: string;
    /** Feather icon class shown inside the field, e.g. "icon-tag" */
    icon: string;
    value: string;
    onChange: (value: string) => void;
    options: ComboOption[];
    /** true: the visitor can type any value or pick one; false: pick only (a select) */
    editable?: boolean;
    /** How the typed value is shown in the field (e.g. thousand separators) */
    format?: (value: string) => string;
    /** Clean what the visitor typed before it is stored (e.g. digits only) */
    parse?: (typed: string) => string;
    inputMode?: 'numeric' | 'text';
    maxLength?: number;
    className?: string;
}

function Chevron() {
    return (
        <svg viewBox="0 0 24 24" width={18} height={18} fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function Check() {
    return (
        <svg viewBox="0 0 24 24" width={18} height={18} fill="none" stroke="currentColor" strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M5 12.5l4.5 4.5L19 7.5" />
        </svg>
    );
}

/**
 * Dropdown field for the Kalkulator: a floating list of choices under a
 * Fuzio-style rounded field. With `editable` it is a combobox (type a value
 * or pick one); without, it is a plain select. Keyboard: arrow keys move,
 * Enter picks, Esc closes. Styles: .gd-dd in gd-react.css.
 */
export default function ComboField({
    id,
    label,
    icon,
    value,
    onChange,
    options,
    editable = false,
    format = (raw) => raw,
    parse = (typed) => typed,
    inputMode = 'text',
    maxLength,
    className = '',
}: ComboFieldProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const listRef = useRef<HTMLUListElement>(null);
    const listId = `${id}-list${useId().replace(/:/g, '')}`;
    const selectedIndex = options.findIndex((option) => option.value === value);

    const open = () => {
        setIsOpen(true);
        setActiveIndex(selectedIndex >= 0 ? selectedIndex : 0);
    };
    const close = () => {
        setIsOpen(false);
        setActiveIndex(-1);
    };
    const choose = (option: ComboOption) => {
        onChange(option.value);
        close();
    };

    // Close when clicking or tapping outside the field.
    useEffect(() => {
        if (!isOpen) {
            return;
        }
        const onPointerDown = (event: PointerEvent) => {
            if (!wrapperRef.current?.contains(event.target as Node)) {
                close();
            }
        };
        document.addEventListener('pointerdown', onPointerDown);

        return () => document.removeEventListener('pointerdown', onPointerDown);
    }, [isOpen]);

    // Keep the highlighted option visible while moving with the keyboard.
    useEffect(() => {
        if (isOpen && activeIndex >= 0) {
            (listRef.current?.children[activeIndex] as HTMLElement | undefined)?.scrollIntoView({ block: 'nearest' });
        }
    }, [isOpen, activeIndex]);

    const onKeyDown = (event: KeyboardEvent) => {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                if (!isOpen) {
                    open();
                } else {
                    setActiveIndex((index) => Math.min(index + 1, options.length - 1));
                }
                break;
            case 'ArrowUp':
                event.preventDefault();
                if (isOpen) {
                    setActiveIndex((index) => Math.max(index - 1, 0));
                }
                break;
            case 'Enter':
                if (isOpen && activeIndex >= 0) {
                    event.preventDefault();
                    choose(options[activeIndex]);
                } else if (!editable) {
                    event.preventDefault();
                    open();
                }
                break;
            case ' ':
                if (!editable) {
                    event.preventDefault();
                    if (isOpen) {
                        close();
                    } else {
                        open();
                    }
                }
                break;
            case 'Escape':
            case 'Tab':
                close();
                break;
        }
    };

    const comboProps = {
        id,
        role: 'combobox' as const,
        'aria-expanded': isOpen,
        'aria-controls': listId,
        'aria-activedescendant': isOpen && activeIndex >= 0 ? `${listId}-${activeIndex}` : undefined,
        onKeyDown,
    };

    return (
        <div ref={wrapperRef} className={`${className} form-group gd-dd${isOpen ? ' is-open' : ''}`}>
            <label className="calc-label" htmlFor={id}>
                {label}
            </label>
            <i className={`feather-icon ${icon}`} aria-hidden="true"></i>

            {editable ? (
                <input
                    {...comboProps}
                    className="form-control gd-dd-input"
                    type="text"
                    inputMode={inputMode}
                    maxLength={maxLength}
                    autoComplete="off"
                    aria-autocomplete="none"
                    value={format(value)}
                    onChange={(event) => onChange(parse(event.target.value))}
                />
            ) : (
                <button {...comboProps} type="button" className="form-control gd-dd-trigger" aria-haspopup="listbox" onClick={() => (isOpen ? close() : open())}>
                    {options[selectedIndex]?.label ?? ''}
                </button>
            )}

            {editable ? (
                <button type="button" className="gd-dd-toggle" tabIndex={-1} aria-label={`Pilihan ${label.toLowerCase()}`} onClick={() => (isOpen ? close() : open())}>
                    <Chevron />
                </button>
            ) : (
                <span className="gd-dd-toggle" aria-hidden="true">
                    <Chevron />
                </span>
            )}

            {isOpen && (
                <ul ref={listRef} id={listId} role="listbox" aria-label={label} className="gd-dd-list">
                    {options.map((option, index) => {
                        const isSelected = index === selectedIndex;

                        return (
                            <li
                                key={option.value}
                                id={`${listId}-${index}`}
                                role="option"
                                aria-selected={isSelected}
                                className={`gd-dd-option${isSelected ? ' is-selected' : ''}${index === activeIndex ? ' is-active' : ''}`}
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                    choose(option);
                                }}
                                onMouseEnter={() => setActiveIndex(index)}
                            >
                                <span>{option.label}</span>
                                {isSelected && <Check />}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
