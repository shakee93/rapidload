import{j as r}from"./vendor.T3CYoFTJ.js";import{u as c,T as m}from"./index.Be5yF97N.js";import{F as x,B as b}from"./index.B9V42R5v.js";import"./core-vendor.V80Ff32-.js";import"./animations.CVpoelRy.js";const g=({content:e,currentStep:s,setIsOpen:a,setCurrentStep:t})=>{const{steps:o}=c();return r.jsxs("div",{className:"text-md flex flex-col px-4 pb-4 font-sans",children:[r.jsxs("div",{className:"flex justify-between items-center px-4 py-4 border-b dark:border-b-brand-700/40 -mx-[22px]",children:[r.jsx("span",{className:"text-lg font-semibold leading-none tracking-tight",children:e.header?e.header:"Let's Start"}),r.jsx("button",{className:"flex gap-2 items-center",onClick:n=>a(!1),children:r.jsx(x,{className:"w-5"})})]}),typeof e=="string"&&r.jsx("div",{className:"px-2 pt-2",children:e}),e.body&&r.jsx("div",{className:"pt-4",children:e.body}),r.jsxs("div",{className:"flex items-center justify-between pt-4",children:[r.jsxs("span",{className:"text-sm text-brand-500",children:[s+1," of ",o.length]}),r.jsx(b,{onClick:n=>{t(l=>l<o.length-1?l+1:(a(!1),0))},size:"sm",children:s<o.length-1?"Next":"Finish"})]})]})},i={top:"bottom",bottom:"top",right:"left",left:"right",custom:"custom"},h=(e,s,a,t)=>{(!e||e==="custom")&&(e="top",s="bottom",a="left");const o=18,n=10,l=t?"rgb(43, 43, 43, 0.65)":"rgb(255, 255, 255, .7)",d=e==="top"||e==="bottom",p=10;return{[`--rtp-arrow-${d?i[a]:s}`]:n+p+"px",[`--rtp-arrow-${i[e]}`]:-n+"px",[`--rtp-arrow-border-${d?"left":"top"}`]:`${o/2}px solid transparent`,[`--rtp-arrow-border-${d?"right":"bottom"}`]:`${o/2}px solid transparent`,[`--rtp-arrow-border-${e}`]:`${n}px solid ${l}`}},v=({children:e,isDark:s})=>{const a={popover:(t,o)=>({...t,borderRadius:"10px",padding:"0 8px",zIndex:15e4,backdropFilter:"blur(8px)",backgroundColor:"rgb(255, 255, 255, .5)",...s&&{backgroundColor:"rgb(43, 43, 43, .5)",color:"white"},...h(o.position,o.verticalAlign,o.horizontalAlign,s)}),maskArea:t=>({...t,rx:6}),maskWrapper:t=>({...t,color:s?"rgb(0, 0,0,.4)":"rgb(0,0,0,0.05)",opacity:1}),highlightedArea:(t,{x:o,y:n,width:l,height:d})=>({...t,display:"block",stroke:s?"#626874":"#0e172a",strokeWidth:2,width:l,height:d,rx:6,pointerEvents:"none"})};return r.jsx(m,{maskClassName:"rpo-titan-tour",maskId:"rpo-titan-tour-mask",padding:{popover:[25,25]},styles:a,onClickMask:()=>{},components:{Badge:()=>r.jsx(r.Fragment,{}),Close:()=>r.jsx(r.Fragment,{}),Content:t=>r.jsx(g,{...t}),Navigation:()=>r.jsx(r.Fragment,{})},steps:[],children:e})};export{v as default};
//# sourceMappingURL=index.L-FdtL5C.js.map
