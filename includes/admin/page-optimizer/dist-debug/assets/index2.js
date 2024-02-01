import{j as e,B as m}from"./index.js";import{u as x,T as b}from"./index3.js";import{r as d}from"./core-vendor.js";import"./vendor.js";import"./animations.js";function g({title:r,titleId:o,...a},t){return d.createElement("svg",Object.assign({xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",strokeWidth:1.5,stroke:"currentColor","aria-hidden":"true",ref:t,"aria-labelledby":o},a),r?d.createElement("title",{id:o},r):null,d.createElement("path",{strokeLinecap:"round",strokeLinejoin:"round",d:"M6 18L18 6M6 6l12 12"}))}const h=d.forwardRef(g),u=h,f=({content:r,currentStep:o,setIsOpen:a,setCurrentStep:t})=>{const{steps:s}=x();return e.jsxs("div",{className:"text-md flex flex-col px-4 pb-4 font-sans",children:[e.jsxs("div",{className:"flex justify-between items-center px-4 py-4 border-b dark:border-b-brand-700/40 -mx-[22px]",children:[e.jsx("span",{className:"text-lg font-semibold leading-none tracking-tight",children:r.header?r.header:"Let's Start"}),e.jsx("button",{className:"flex gap-2 items-center",onClick:n=>a(!1),children:e.jsx(u,{className:"w-5"})})]}),typeof r=="string"&&e.jsx("div",{className:"px-2 pt-2",children:r}),r.body&&e.jsx("div",{className:"pt-4",children:r.body}),e.jsxs("div",{className:"flex items-center justify-between pt-4",children:[e.jsxs("span",{className:"text-sm text-brand-500",children:[o+1," of ",s.length]}),e.jsx(m,{onClick:n=>{t(l=>l<s.length-1?l+1:(a(!1),0))},size:"sm",children:o<s.length-1?"Next":"Finish"})]})]})},c={top:"bottom",bottom:"top",right:"left",left:"right",custom:"custom"},j=(r,o,a,t)=>{(!r||r==="custom")&&(r="top",o="bottom",a="left");const s=18,n=10,l=t?"rgb(43, 43, 43, 0.65)":"rgb(255, 255, 255, .7)",i=r==="top"||r==="bottom",p=10;return{[`--rtp-arrow-${i?c[a]:o}`]:n+p+"px",[`--rtp-arrow-${c[r]}`]:-n+"px",[`--rtp-arrow-border-${i?"left":"top"}`]:`${s/2}px solid transparent`,[`--rtp-arrow-border-${i?"right":"bottom"}`]:`${s/2}px solid transparent`,[`--rtp-arrow-border-${r}`]:`${n}px solid ${l}`}},$=({children:r,isDark:o})=>{const a={popover:(t,s)=>({...t,borderRadius:"10px",padding:"0 8px",zIndex:15e4,backdropFilter:"blur(8px)",backgroundColor:"rgb(255, 255, 255, .5)",...o&&{backgroundColor:"rgb(43, 43, 43, .5)",color:"white"},...j(s.position,s.verticalAlign,s.horizontalAlign,o)}),maskArea:t=>({...t,rx:6}),maskWrapper:t=>({...t,color:o?"rgb(0, 0,0,.4)":"rgb(0,0,0,0.05)",opacity:1}),highlightedArea:(t,{x:s,y:n,width:l,height:i})=>({...t,display:"block",stroke:o?"#626874":"#0e172a",strokeWidth:2,width:l,height:i,rx:6,pointerEvents:"none"})};return e.jsx(b,{maskClassName:"rpo-titan-tour",maskId:"rpo-titan-tour-mask",padding:{popover:[25,25]},styles:a,onClickMask:()=>{},components:{Badge:()=>e.jsx(e.Fragment,{}),Close:()=>e.jsx(e.Fragment,{}),Content:t=>e.jsx(f,{...t}),Navigation:()=>e.jsx(e.Fragment,{})},steps:[],children:r})};export{$ as default};
//# sourceMappingURL=index2.js.map
