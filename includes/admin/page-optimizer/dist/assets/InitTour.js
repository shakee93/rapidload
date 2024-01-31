import{c as E,j as t,t as D,u as O,s as x,a as F,o as I,b as R}from"./index.min.js";import{r as c}from"./core-vendor.js";import{u as M}from"./index2.js";import"./vendor.js";import"./animations.js";const g=E("MousePointerClick",[["path",{d:"m9 9 5 12 1.774-5.226L21 14 9 9z",key:"1qd44z"}],["path",{d:"m16.071 16.071 4.243 4.243",key:"wfhsjb"}],["path",{d:"m7.188 2.239.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656-2.12 2.122",key:"1bk8fz"}]]),P=({audit:o})=>{const{dispatch:e,openAudits:i,activeTab:a}=O();return c.exports.useEffect(()=>{i.includes(o.id)||e(x("openAudits",[...i,o.id])),(o.type==="opportunity"?"opportunities":o.type)!==a&&e(x("activeTab",o.type==="opportunity"?"opportunities":o.type))},[]),t.exports.jsxs(t.exports.Fragment,{children:[t.exports.jsx(g,{onClick:()=>{},className:"mb-2"}),"Click the ",t.exports.jsx("span",{className:"text-purple-750",children:'"Show Actions"'})," button to view detailed information on each performance audit and dive deeper."]})},Y=o=>{var r,n;let e=o.id;const i=!!((n=(r=o.files)==null?void 0:r.headings)!=null&&n.find(d=>d.valueType==="controls"));let a=o.settings.filter(d=>{var m,h;return!((h=(m=o.files)==null?void 0:m.grouped_items)!=null&&h.map(l=>D(o,l.type)).includes(d.category))});return[{selector:`[data-tour="audit-${e}"]`,content:{header:"Explore Individual Audits",body:t.exports.jsx(P,{audit:o})},position:"left",resizeObservables:[`[data-tour="audit-${e}"]`,`[data-tour="audit-${e}-group-0"]`,`[data-tour="audit-${e}"] .audit-content`]},...o.settings.length>0&&a.length>0?[{selector:`[data-tour="${e}-recommended-settings"]`,content:{header:"Tailored Recommendations",body:t.exports.jsx(t.exports.Fragment,{children:"Discover our suggestions for features. Toggle them on or off to fit your preferences seamlessly."})},position:"top"}]:[],...o.files.items.length>0?[{selector:`[data-tour="${e}-group-0"]`,content:{header:"Streamlined Audit Insights",body:t.exports.jsx(t.exports.Fragment,{children:"Within every audit, discover thoughtfully arranged details and actions. Clarity, made simple."})},position:"left",resizeObservables:[`[data-tour="${e}-group-0"]`]}]:[],...o.settings.length>0&&a.length===0?[{selector:`[data-tour="${e}-group-0-settings"]`,content:{header:"Tailored Recommendations",body:t.exports.jsx(t.exports.Fragment,{children:"Discover our suggestions for features. Toggle them on or off to fit your preferences seamlessly."})},position:"top"}]:[],{selector:`[data-tour="${e}-group-0-table"]`,content:{header:"Organizing Your Assets",body:t.exports.jsx(t.exports.Fragment,{children:"Explore the table to find resources and their associated actions. Properly organized to help improve your page performance."})},position:"top"},...i?[{selector:`[data-tour="${e}-file-action-0"]`,content:{header:"Adjusting File Actions",body:t.exports.jsxs(t.exports.Fragment,{children:[t.exports.jsx(g,{className:"mb-2"}),"Click on the actions dropdown to change how each file behave. Adjust as needed to fine-tune your page's performance."]})},position:"top",resizeObservables:["[data-radix-popper-content-wrapper]",`[data-tour="${e}-file-action-0"]`],highlightedSelectors:["[data-radix-popper-content-wrapper]",`[data-tour="${e}-file-action-0"]`]}]:[]]},B=[{selector:'[data-tour="switch-report-strategy"]',content:{header:"Select Mobile or Desktop",body:"Pick a device to analyze and optimize the page."}},{selector:'[data-tour="analyze"]',content:{header:"Refresh Speed Again",body:t.exports.jsxs(t.exports.Fragment,{children:[" ",t.exports.jsx(g,{className:"mb-2"})," Click to re-analyze the page speed using Google PageSpeed Insights."]})}},{selector:'[data-tour="speed-insights"]',content:{header:"Your Speed Insights",body:"See your overall website's speed rating, along with a breakdown of key metrics and performance scores."},position:"right"},{selector:'[data-tour="metrics"]',content:{header:"Dive Deeper into Metrics",body:t.exports.jsxs(t.exports.Fragment,{children:[" ",t.exports.jsx(g,{className:"mb-2"}),"Click on individual metrics to uncover insights and get recommendations for enhancement."]})},position:"right"},{selector:'[data-tour="audits"]',content:{header:"Performance Audits & Actions",body:t.exports.jsx(t.exports.Fragment,{children:"Discover the top audits needing attention and follow our recommended actions to enhance your page's performance."})},position:"left"},{selector:'[data-tour="audit-groups"]',content:{header:"Dive into Audit Groups",body:t.exports.jsxs("div",{className:"flex flex-col gap-2 ",children:[t.exports.jsx(g,{}),t.exports.jsx("div",{className:"text-md border-b pb-4",children:"Click on each audit group to explore detailed insights and actions."}),t.exports.jsx("div",{className:"text-sm text-brand-600",children:t.exports.jsxs("ul",{className:"dark:text-brand-200 text-brand-800 flex flex-col gap-2 [&>*]:flex [&>*]:flex-col [&>*]:gap-1",children:[t.exports.jsxs("li",{children:[t.exports.jsx("span",{className:"font-bold ",children:"Opportunities"})," Recommendations from Google to enhance your page's speed and efficiency."]}),t.exports.jsxs("li",{children:[t.exports.jsx("span",{className:"font-bold d",children:"Diagnostics"})," In-depth feedback about your site's performance and potential issues."]}),t.exports.jsxs("li",{children:[t.exports.jsx("span",{className:"font-bold ",children:"Passed Audits"})," Areas where your website meets or exceeds performance standards."]})]})})]})},position:"left"}],q=[{selector:'[data-tour="save-changes"]',content:{header:"Saving Your Optimizations",body:t.exports.jsxs(t.exports.Fragment,{children:[t.exports.jsx(g,{className:"mb-2"}),"After making changes, remember to save. Tap the ",t.exports.jsx("span",{className:"text-purple-750",children:'"Save Changes"'})," button to ensure all your tweaks are updated."]})},position:(o,e)=>{var r;let i=document.getElementById("rapidload-optimizer-shadow-dom"),a=(r=i==null?void 0:i.shadowRoot)==null?void 0:r.querySelector('[data-tour="save-changes"]');if(a){let n=a.getBoundingClientRect();return[Number(n.x+n.width)-o.width,n.y-o.height-25]}return"top"},padding:{popover:[100,25]}}],U=({mode:o})=>{const{data:e,loading:i}=F(I),{setIsOpen:a,isOpen:r,setSteps:n,currentStep:d,setCurrentStep:m}=M(),{activeTab:h,isTourOpen:l,activeMetric:$,dispatch:f}=O(),{activeReport:y,mobile:G,desktop:L}=F(s=>s.app);c.exports.useEffect(()=>{var v,j,w,S,A,k,z,C;let s=e==null?void 0:e.grouped.opportunities.find(u=>u.settings.length>0&&u.files.items.length>0);s||(s=e==null?void 0:e.grouped.diagnostics.find(u=>u.settings.length>0&&u.files.items.length>0)),!s&&((j=(v=e==null?void 0:e.grouped)==null?void 0:v.opportunities)==null?void 0:j.length)&&((S=(w=e==null?void 0:e.grouped)==null?void 0:w.opportunities)==null?void 0:S.length)>0&&(s=e==null?void 0:e.grouped.opportunities[0]),!s&&((k=(A=e==null?void 0:e.grouped)==null?void 0:A.diagnostics)==null?void 0:k.length)&&((C=(z=e==null?void 0:e.grouped)==null?void 0:z.diagnostics)==null?void 0:C.length)>0&&(s=e==null?void 0:e.grouped.diagnostics[0]),n&&n(u=>{let T=document.getElementById("rapidload-optimizer-shadow-dom");return[...B,...s?Y(s):[],...o==="normal"?q:[]].map(p=>{var N;return T&&(p.shadowSelector=typeof p.selector=="string"?p.selector:p.shadowSelector,p.selector=(N=T.shadowRoot)==null?void 0:N.querySelector(p.shadowSelector)),p})})},[y,d]),c.exports.useEffect(()=>{r||m(0)},[y]),c.exports.useEffect(()=>{$&&f(x("activeMetric",null))},[d]),c.exports.useEffect(()=>{},[r]);const b=()=>{const s=document.getElementById("rapidload-page-optimizer-content");r&&s?s.style.overflowY="hidden":s&&(s.style.overflowY="auto")};return c.exports.useEffect(()=>{l!==r&&(b(),a(l))},[l]),c.exports.useEffect(()=>{l!==r&&(b(),f(R("isTourOpen",r)))},[r]),t.exports.jsx(t.exports.Fragment,{})};export{U as default};
