import{c as o,j as e,C as f,d as x,A as v,T as b,B as j,e as g,f as p,g as w,h as C}from"./index.js";import{r as t}from"./core-vendor.js";import{L as M,d as N}from"./animations.js";import"./vendor.js";const F=o("Annoyed",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M8 15h8",key:"45n4r"}],["path",{d:"M8 9h2",key:"1g203m"}],["path",{d:"M14 9h2",key:"116p9w"}]]),S=o("Frown",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M16 16s-1.5-2-4-2-4 2-4 2",key:"epbg0q"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),A=o("Meh",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["line",{x1:"8",x2:"16",y1:"15",y2:"15",key:"1xb1d9"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),T=o("SmilePlus",[["path",{d:"M22 11v1a10 10 0 1 1-9-10",key:"ew0xw9"}],["path",{d:"M8 14s1.5 2 4 2 4-2 4-2",key:"1y1vjs"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}],["path",{d:"M16 5h6",key:"1vod17"}],["path",{d:"M19 2v6",key:"4bpg5p"}]]),L=o("Smile",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M8 14s1.5 2 4 2 4-2 4-2",key:"1y1vjs"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),_=()=>{const[a,c]=t.exports.useState(null),[i,d]=t.exports.useState(""),[r,l]=t.exports.useState(!1),[y,m]=t.exports.useState(!1),u=t.exports.useMemo(()=>[{Component:F,value:"annoyed"},{Component:S,value:"frown"},{Component:A,value:"meh"},{Component:L,value:"smile"},{Component:T,value:"smilePlus"}],[]),h=async()=>{if(!a)return;const s=new g;try{l(!0),await s.post("rapidload_titan_feedback",{smiley:a,detail:i}),p({description:e.exports.jsxs("div",{className:"flex w-full gap-2 text-center",children:["Thank you! Your feedback is sent. ",e.exports.jsx(w,{className:"w-5 text-green-600"})]})}),l(!1),c(""),d("")}catch(n){p({description:e.exports.jsxs("div",{className:"flex w-full gap-2 text-center",children:[n.message," ",e.exports.jsx(C,{className:"w-5 text-red-600"})]})}),l(!1)}};return t.exports.useEffect(()=>{setTimeout(()=>{m(!0)},30*1e3)},[]),y?e.exports.jsxs(f,{className:x("flex flex-col gap-4 px-6 py-5 xl:mb-12 backdrop-blur-md bg-brand-0/70",!a&&"pb-1"),children:[e.exports.jsxs("div",{className:"flex flex-col gap-0.5",children:[e.exports.jsx("div",{className:"text-sm font-medium",children:"Your Voice Matters"}),e.exports.jsx("div",{className:"text-xs text-brand-500",children:"Feedback helps us enhance and tailor the product just for you."})]}),e.exports.jsx("div",{className:"flex justify-start gap-4 select-none",children:u.map((s,n)=>e.exports.jsx(s.Component,{onClick:I=>c(k=>s.value!==k?s.value:null),className:x("w-8 h-8 cursor-pointer text-brand-400 hover:text-brand-500",a===s.value&&"text-brand-600 dark:text-brand-200")},n))}),e.exports.jsx("div",{children:e.exports.jsx(M,{features:N,children:e.exports.jsx(v,{isOpen:!!a,children:e.exports.jsxs("div",{className:"flex flex-col gap-2",children:[e.exports.jsx(b,{value:i,onChange:s=>d(s.target.value),placeholder:"Optional: Tell us more about your experience..."}),e.exports.jsx("div",{className:"flex justify-end",children:e.exports.jsx(j,{disabled:r,className:x("flex gap-1.5 pl-4",r&&"pl-2.5"),loading:r,onClick:s=>h(),children:"Submit"})})]})})})})]}):e.exports.jsx(e.exports.Fragment,{})};export{_ as default};
