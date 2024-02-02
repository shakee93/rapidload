import{c as t,d as f,j as e,C as b,e as c,A as i,L as d,f as g,g as v,X as w}from"./index.js";import{r as a}from"./core-vendor.js";import"./vendor.js";import"./animations.js";const j=t("Annoyed",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M8 15h8",key:"45n4r"}],["path",{d:"M8 9h2",key:"1g203m"}],["path",{d:"M14 9h2",key:"116p9w"}]]),M=t("Frown",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M16 16s-1.5-2-4-2-4 2-4 2",key:"epbg0q"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),N=t("Meh",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["line",{x1:"8",x2:"16",y1:"15",y2:"15",key:"1xb1d9"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),C=t("SmilePlus",[["path",{d:"M22 11v1a10 10 0 1 1-9-10",key:"ew0xw9"}],["path",{d:"M8 14s1.5 2 4 2 4-2 4-2",key:"1y1vjs"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}],["path",{d:"M16 5h6",key:"1vod17"}],["path",{d:"M19 2v6",key:"4bpg5p"}]]),F=t("Smile",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M8 14s1.5 2 4 2 4-2 4-2",key:"1y1vjs"}],["line",{x1:"9",x2:"9.01",y1:"9",y2:"9",key:"yxxnd0"}],["line",{x1:"15",x2:"15.01",y1:"9",y2:"9",key:"1p4y9e"}]]),S=t("ThumbsDown",[["path",{d:"M17 14V2",key:"8ymqnk"}],["path",{d:"M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z",key:"s6e0r"}]]),A=t("ThumbsUp",[["path",{d:"M7 10v12",key:"1qc93n"}],["path",{d:"M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z",key:"y3tblf"}]]),I=()=>{const[s,o]=a.exports.useState(null),[y,m]=a.exports.useState(""),[l,n]=a.exports.useState(!1),[h,x]=a.exports.useState(!1),{toast:p}=f();a.exports.useMemo(()=>[{Component:j,value:"annoyed"},{Component:M,value:"frown"},{Component:N,value:"meh"},{Component:F,value:"smile"},{Component:C,value:"smilePlus"}],[]);const u=async()=>{if(console.log(s),!s)return;const r=new g;try{n(!0),await r.post("rapidload_titan_feedback",{smiley:s,detail:y}),p({description:e.exports.jsxs("div",{className:"flex w-full gap-2 text-center",children:["Thank you! Your feedback is sent. ",e.exports.jsx(v,{className:"w-5 text-green-600"})]})}),n(!1),o(""),m(""),x(!1)}catch(k){p({description:e.exports.jsxs("div",{className:"flex w-full gap-2 text-center",children:[k.message," ",e.exports.jsx(w,{className:"w-5 text-red-600"})]})}),n(!1)}};return a.exports.useEffect(()=>{!s||u()},[s]),a.exports.useEffect(()=>{setTimeout(()=>{x(!0)},30*1)},[]),h?e.exports.jsxs(b,{className:c("flex flex-col gap-4 px-6 py-5 xl:mb-12 bg-brand-0/70"),children:[e.exports.jsxs("div",{className:"flex flex-col gap-0.5",children:[e.exports.jsx("div",{className:"text-sm font-medium",children:"How was the experience?"}),e.exports.jsx("div",{className:"text-xs text-brand-500",children:"Feedback helps us improve the product work better for you."})]}),e.exports.jsxs("div",{className:"flex gap-2",children:[e.exports.jsxs(i,{onClick:r=>{o("good")},className:c(s==="good"&&"bg-brand-300"),variant:"outline",children:[l&&s==="good"?e.exports.jsx(d,{className:"w-4 animate-spin"}):e.exports.jsx(A,{className:"w-4"}),"Good"]}),e.exports.jsxs(i,{onClick:r=>{o("bad")},className:c(s==="bad"&&"bg-brand-300"),variant:"outline",children:[l&&s==="bad"?e.exports.jsx(d,{className:"w-4 animate-spin"}):e.exports.jsx(S,{className:"w-4"}),"Bad"]})]})]}):e.exports.jsx(e.exports.Fragment,{})};export{I as default};
