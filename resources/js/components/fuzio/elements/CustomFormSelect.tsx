import React, { useEffect, useRef, useCallback, useId } from "react";

export interface LocationOption {
    id: number;
    value: string;
    label: string;
}

interface CustomSelectProps {
    optionArray: LocationOption[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    "aria-label"?: string;
}


const CustomFormSelect: React.FC<CustomSelectProps> = ({
    optionArray ,
    value,
    onChange,
    placeholder,
    disabled = false,
    className = "",
    "aria-label": ariaLabel,
}) => {
    const [isOpen, setIsOpen] = React.useState(false);
    const [focusedIndex, setFocusedIndex] = React.useState<number>(-1);

    const uid = useId();
    const listboxId = `listbox-${uid}`;

    const containerRef = useRef<HTMLDivElement>(null);
    const listRef = useRef<HTMLUListElement>(null);

    // Derive selected from controlled value, fallback to first option
    const selectedValue = value ?? optionArray[0]?.value ?? "";
    const selectedLabel =
        optionArray.find((o) => o.value === selectedValue)?.label ??
        placeholder ??
        "";

    const close = useCallback(() => {
        setIsOpen(false);
        setFocusedIndex(-1);
    }, []);

    const open = useCallback(() => {
        if (disabled) return;
        setIsOpen(true);
        const idx = optionArray.findIndex((o) => o.value === selectedValue);
        setFocusedIndex(idx >= 0 ? idx : 0);
    }, [disabled, optionArray, selectedValue]);

    const toggle = useCallback(() => {
        if (isOpen) {
            close();
        } else {
            open();
        }
    }, [isOpen, open, close]);

    const handleSelect = useCallback(
        (val: string) => {
            onChange?.(val);
            close();
            containerRef.current?.focus();
        },
        [onChange, close]
    );

    // Close on outside click
    useEffect(() => {
        if (!isOpen) return;
        const handleOutsideClick = (e: MouseEvent) => {
            if (!containerRef.current?.contains(e.target as Node)) close();
        };
        document.addEventListener("mousedown", handleOutsideClick);
        return () => document.removeEventListener("mousedown", handleOutsideClick);
    }, [isOpen, close]);

    // Scroll focused item into view
    useEffect(() => {
        if (!isOpen || focusedIndex < 0) return;
        const item = listRef.current?.children[focusedIndex] as HTMLElement;
        item?.scrollIntoView({ block: "nearest" });
    }, [isOpen, focusedIndex]);

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (disabled) return;
            switch (e.key) {
                case "Enter":
                case " ":
                    e.preventDefault();
                    if (isOpen && focusedIndex >= 0) {
                        handleSelect(optionArray[focusedIndex].value);
                    } else {
                        toggle();
                    }
                    break;
                case "ArrowDown":
                    e.preventDefault();
                    if (!isOpen) {
                        open();
                    } else {
                        setFocusedIndex((i) => Math.min(i + 1, optionArray.length - 1));
                    }
                    break;
                case "ArrowUp":
                    e.preventDefault();
                    if (!isOpen) {
                        open();
                    } else {
                        setFocusedIndex((i) => Math.max(i - 1, 0));
                    }
                    break;
                case "Home":
                    e.preventDefault();
                    setFocusedIndex(0);
                    break;
                case "End":
                    e.preventDefault();
                    setFocusedIndex(optionArray.length - 1);
                    break;
                case "Escape":
                    close();
                    break;
                case "Tab":
                    close();
                    break;
            }
        },
        [disabled, isOpen, focusedIndex, optionArray, handleSelect, open, close, toggle]
    );

    return (
        <div
            ref={containerRef}
            role="combobox"
            aria-haspopup="listbox"
            aria-expanded={isOpen}
            aria-controls={listboxId}
            aria-label={ariaLabel}
            aria-disabled={disabled}
            tabIndex={disabled ? -1 : 0}
            className={[
                "nice-select tv-select",
                isOpen ? "open" : "",
                disabled ? "disabled" : "",
                className,
            ]
                .filter(Boolean)
                .join(" ")}
            onClick={toggle}
            onKeyDown={handleKeyDown}
        >
            <span className="current">{selectedLabel}</span>

            {isOpen && (
                <ul
                    ref={listRef}
                    id={listboxId}
                    role="listbox"
                    aria-label={ariaLabel}
                    className="list"
                >
                    {optionArray.map((item, index) => {
                        const isSelected = selectedValue === item.value;
                        const isFocused = focusedIndex === index;

                        return (
                            <li
                                key={item.id ?? item.value}
                                id={`${listboxId}-option-${index}`}
                                role="option"
                                aria-selected={isSelected}
                                data-value={item.value}
                                className={[
                                    "option",
                                    isSelected ? "selected" : "",
                                    isFocused ? "focus" : "",
                                ]
                                    .filter(Boolean)
                                    .join(" ")}
                                onMouseDown={(e) => {
                                    // Prevent blur before click registers
                                    e.preventDefault();
                                    handleSelect(item.value);
                                }}
                                onMouseEnter={() => setFocusedIndex(index)}
                            >
                                {item.label}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
};

export default CustomFormSelect;