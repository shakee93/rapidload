import {j as e, B as c} from "./index.min.js";
import {u as m, T as b} from "./index2.js";
import {r as x} from "./core-vendor.js";
import "./vendor.js";
import "./animations.js";

function g({title: r, titleId: o, ...a}, t) {
    return x.exports.createElement("svg", Object.assign({
        xmlns: "http://www.w3.org/2000/svg",
        fill: "none",
        viewBox: "0 0 24 24",
        strokeWidth: 1.5,
        stroke: "currentColor",
        "aria-hidden": "true",
        ref: t,
        "aria-labelledby": o
    }, a), r ? x.exports.createElement("title", {id: o}, r) : null, x.exports.createElement("path", {
        strokeLinecap: "round",
        strokeLinejoin: "round",
        d: "M6 18L18 6M6 6l12 12"
    }))
}

const h = x.exports.forwardRef(g), u = h, f = ({content: r, currentStep: o, setIsOpen: a, setCurrentStep: t}) => {
    const {steps: s} = m();
    return e.exports.jsxs("div", {
        className: "text-md flex flex-col px-4 pb-4 font-sans",
        children: [e.exports.jsxs("div", {
            className: "flex justify-between items-center px-4 py-4 border-b dark:border-b-brand-700/40 -mx-[22px]",
            children: [e.exports.jsx("span", {
                className: "text-lg font-semibold leading-none tracking-tight",
                children: r.header ? r.header : "Let's Start"
            }), e.exports.jsx("button", {
                className: "flex gap-2 items-center",
                onClick: n => a(!1),
                children: e.exports.jsx(u, {className: "w-5"})
            })]
        }), typeof r == "string" && e.exports.jsx("div", {
            className: "px-2 pt-2",
            children: r
        }), r.body && e.exports.jsx("div", {
            className: "pt-4",
            children: r.body
        }), e.exports.jsxs("div", {
            className: "flex items-center justify-between pt-4",
            children: [e.exports.jsxs("span", {
                className: "text-sm text-brand-500",
                children: [o + 1, " of ", s.length]
            }), e.exports.jsx(c, {
                onClick: n => {
                    t(p => p < s.length - 1 ? p + 1 : (a(!1), 0))
                }, size: "sm", children: o < s.length - 1 ? "Next" : "Finish"
            })]
        })]
    })
}, i = {top: "bottom", bottom: "top", right: "left", left: "right", custom: "custom"}, j = (r, o, a, t) => {
    (!r || r === "custom") && (r = "top", o = "bottom", a = "left");
    const s = 18, n = 10, p = t ? "rgb(43, 43, 43, 0.65)" : "rgb(255, 255, 255, .7)", l = r === "top" || r === "bottom",
        d = 10;
    return {
        [`--rtp-arrow-${l ? i[a] : o}`]: n + d + "px",
        [`--rtp-arrow-${i[r]}`]: -n + "px",
        [`--rtp-arrow-border-${l ? "left" : "top"}`]: `${s / 2}px solid transparent`,
        [`--rtp-arrow-border-${l ? "right" : "bottom"}`]: `${s / 2}px solid transparent`,
        [`--rtp-arrow-border-${r}`]: `${n}px solid ${p}`
    }
}, $ = ({children: r, isDark: o}) => {
    const a = {
        popover: (t, s) => ({
            ...t,
            borderRadius: "10px",
            padding: "0 8px",
            zIndex: 15e4,
            backdropFilter: "blur(8px)",
            backgroundColor: "rgb(255, 255, 255, .5)", ...o && {
                backgroundColor: "rgb(43, 43, 43, .5)",
                color: "white"
            }, ...j(s.position, s.verticalAlign, s.horizontalAlign, o)
        }),
        maskArea: t => ({...t, rx: 6}),
        maskWrapper: t => ({...t, color: o ? "rgb(0, 0,0,.4)" : "rgb(0,0,0,0.05)", opacity: 1}),
        highlightedArea: (t, {x: s, y: n, width: p, height: l}) => ({
            ...t,
            display: "block",
            stroke: o ? "#626874" : "#0e172a",
            strokeWidth: 2,
            width: p,
            height: l,
            rx: 6,
            pointerEvents: "none"
        })
    };
    return e.exports.jsx(b, {
        maskClassName: "rpo-titan-tour",
        maskId: "rpo-titan-tour-mask",
        padding: {popover: [25, 25]},
        styles: a,
        onClickMask: () => {
        },
        components: {
            Badge: () => e.exports.jsx(e.exports.Fragment, {}),
            Close: () => e.exports.jsx(e.exports.Fragment, {}),
            Content: t => e.exports.jsx(f, {...t}),
            Navigation: () => e.exports.jsx(e.exports.Fragment, {})
        },
        steps: [],
        children: r
    })
};
export {$ as default};
