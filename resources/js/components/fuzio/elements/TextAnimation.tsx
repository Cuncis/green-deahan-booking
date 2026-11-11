import { Children, cloneElement, isValidElement, type ReactElement, type ReactNode } from 'react';
import useInView from './useInView';

interface TextAnimationProps {
    children: ReactNode;
    className?: string;
    /** seconds between characters */
    stagger?: number;
}

/**
 * Port of Fuzio's TextAnimation default style ("s10", flip Y): every
 * character flips in from rotateY(-90deg), one after another, once the
 * block is 30% visible. Words never break mid-word.
 */
export default function TextAnimation({ children, className, stagger = 0.05 }: TextAnimationProps) {
    const [ref, inView] = useInView<HTMLDivElement>(0.3);
    let charIndex = 0;

    const charStyle = (index: number) => ({
        display: 'inline-block',
        opacity: inView ? 1 : 0,
        transform: inView ? 'none' : 'rotateY(-90deg)',
        transition: `opacity 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${index * stagger}s, transform 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${index * stagger}s`,
    });

    const splitText = (text: string): ReactNode =>
        text.split(/(\s+)/).map((token, tokenIndex) => {
            if (/^\s+$/.test(token)) {
                return (
                    <span key={`sp-${tokenIndex}`} style={{ display: 'inline-block', whiteSpace: 'pre' }}>
                        {token}
                    </span>
                );
            }

            return (
                <span key={`w-${tokenIndex}`} style={{ display: 'inline-block', whiteSpace: 'nowrap' }}>
                    {token.split('').map((char, index) => (
                        <span key={`c-${index}`} style={charStyle(charIndex++)}>
                            {char}
                        </span>
                    ))}
                </span>
            );
        });

    const processChildren = (node: ReactNode): ReactNode => {
        if (typeof node === 'string') {
            return splitText(node);
        }

        if (isValidElement(node)) {
            const element = node as ReactElement<{ children?: ReactNode }>;
            if (element.props.children === undefined) {
                return element;
            }

            return cloneElement(element, {}, Children.map(element.props.children, (child) => processChildren(child)));
        }

        if (Array.isArray(node)) {
            return node.map((child) => processChildren(child));
        }

        return node;
    };

    return (
        <div ref={ref} className={className}>
            {processChildren(children)}
        </div>
    );
}
