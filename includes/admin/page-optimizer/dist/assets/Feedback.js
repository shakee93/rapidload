import{c as n,j as e,C as f,d as r,A as v,T as b,B as j,e as g,f as y,g as w,h as C}from"./index.js";import{r as s}from"./core-vendor.js";import{L as M,d as N}from"./animations.js";import"./vendor.js";const F=n("Annoyed",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M8 15h8",key:"45n4r"}],["path",{d:"M8 9h2",key:"1g203m"}],["path",{d:"M14 9h2",key:"116p9w"}]]),S=n("Frown",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M16 16s-1.5-2-4-2-4 2-4 2",key:"epbg0q"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),A=n("Meh",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["line",{x1:"8",x2:"16",y1:"15",y2:"15",key:"1xb1d9"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),T=n("SmilePlus",[["path",{d:"M22 11v1a10 10 0 1 1-9-10",key:"ew0xw9"}],["path",{d:"M8 14s1.5 2 4 2 4-2 4-2",key:"1y1vjs"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}],["path",{d:"M16 5h6",key:"1vod17"}],["path",{d:"M19 2v6",key:"4bpg5p"}]]),L=n("Smile",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M8 14s1.5 2 4 2 4-2 4-2",key:"1y1vjs"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),Y=()=>{const[t,i]=s.useState(null),[d,x]=s.useState(""),[l,c]=s.useState(!1),[p,m]=s.useState(!1),u=s.useMemo(()=>[{Component:F,value:"annoyed"},{Component:S,value:"frown"},{Component:A,value:"meh"},{Component:L,value:"smile"},{Component:T,value:"smilePlus"}],[]),h=async()=>{if(!t)return;const a=new g;try{c(!0),await a.post("rapidload_titan_feedback",{smiley:t,detail:d}),y({description:e.jsxs("div",{className:"flex w-full gap-2 text-center",children:["Thank you! Your feedback is sent. ",e.jsx(w,{className:"w-5 text-green-600"})]})}),c(!1),i(""),x("")}catch(o){y({description:e.jsxs("div",{className:"flex w-full gap-2 text-center",children:[o.message," ",e.jsx(C,{className:"w-5 text-red-600"})]})}),c(!1)}};return s.useEffect(()=>{setTimeout(()=>{m(!0)},30*1e3)},[]),p?e.jsxs(f,{className:r("flex flex-col gap-4 px-6 py-5 mb-12 backdrop-blur-md bg-brand-0/70",!t&&"pb-1"),children:[e.jsxs("div",{className:"flex flex-col gap-0.5",children:[e.jsx("div",{className:"text-sm font-medium",children:"Your Voice Matters"}),e.jsx("div",{className:"text-xs text-brand-500",children:"Feedback helps us enhance and tailor the product just for you."})]}),e.jsx("div",{className:"flex justify-start gap-4 select-none",children:u.map((a,o)=>e.jsx(a.Component,{onClick:E=>i(k=>a.value!==k?a.value:null),className:r("w-8 h-8 cursor-pointer text-brand-400 hover:text-brand-500",t===a.value&&"text-brand-600 dark:text-brand-200")},o))}),e.jsx("div",{children:e.jsx(M,{features:N,children:e.jsx(v,{isOpen:!!t,children:e.jsxs("div",{className:"flex flex-col gap-2",children:[e.jsx(b,{value:d,onChange:a=>x(a.target.value),placeholder:"Optional: Tell us more about your experience..."}),e.jsx("div",{className:"flex justify-end",children:e.jsx(j,{disabled:l,className:r("flex gap-1.5 pl-4",l&&"pl-2.5"),loading:l,onClick:a=>h(),children:"Submit"})})]})})})})]}):e.jsx(e.Fragment,{})};export{Y as default};
