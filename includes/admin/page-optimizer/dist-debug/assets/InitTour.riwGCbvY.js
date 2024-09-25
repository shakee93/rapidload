import{j as e}from"./vendor.C2Ge5RaU.js";import{c as L,t as $,u as I,s as h,a as M,o as D,b as Y}from"./index.DA1ALxF2.js";import{r as m}from"./core-vendor.a21Wwx5s.js";import{u as B}from"./index.JHaeFuYk.js";import"./animations.CWk52Myu.js";/**
 * @license lucide-react v0.417.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const a=L("MousePointerClick",[["path",{d:"m9 9 5 12 1.8-5.2L21 14Z",key:"1b76lo"}],["path",{d:"M7.2 2.2 8 5.1",key:"1cfko1"}],["path",{d:"m5.1 8-2.9-.8",key:"1go3kf"}],["path",{d:"M14 4.1 12 6",key:"ita8i4"}],["path",{d:"m6 12-1.9 2",key:"mnht97"}]]),q=({audit:o})=>{const{dispatch:t,openAudits:c,activeTab:l}=I();return m.useEffect(()=>{c.includes(o.id)||t(h("openAudits",[...c,o.id])),(o.type==="opportunity"?"opportunities":o.type)!==l&&t(h("activeTab",o.type==="opportunity"?"opportunities":o.type))},[]),e.jsxs(e.Fragment,{children:[e.jsx(a,{onClick:()=>{},className:"mb-2"}),"Click the ",e.jsx("span",{className:"text-purple-750",children:'"Show Actions"'})," button to view detailed information on each performance audit and dive deeper."]})},G=o=>{var c,l;let t=o.id;return(l=(c=o.files)==null?void 0:c.headings)!=null&&l.find(s=>s.valueType==="controls"),o.settings.filter(s=>{var d,u;return!((u=(d=o.files)==null?void 0:d.grouped_items)!=null&&u.map(b=>$(o,b.type)).includes(s.category))}),[{selector:`[data-tour="audit-${t}"]`,content:{header:"Explore Individual Audits",body:e.jsx(q,{audit:o})},position:"left",resizeObservables:[`[data-tour="audit-${t}"]`,`[data-tour="audit-${t}-group-0"]`,`[data-tour="audit-${t}"] .audit-content`]},...o.files.items.length>0?[{selector:`[data-tour="${t}-group-0"]`,content:{header:"Streamlined Audit Insights",body:e.jsx(e.Fragment,{children:"Within every audit, discover thoughtfully arranged details and actions. Clarity, made simple."})},position:"left",resizeObservables:[`[data-tour="${t}-group-0"]`]}]:[],{selector:`[data-tour="${t}-group-0-table"]`,content:{header:"Organizing Your Assets",body:e.jsx(e.Fragment,{children:"Explore the table to find resources and their associated actions. Properly organized to help improve your page performance."})},position:"top"}]},U=[{selector:'[data-tour="switch-report-strategy"]',content:{header:"Select Mobile or Desktop",body:"Pick a device to analyze and optimize the page."}},{selector:'[data-tour="current-url"]',content:{header:"Current URL",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"})," This is the URL currently selected to configure, optimize, and analyze your site's performance."]})}},{selector:'[data-tour="analyze"]',content:{header:"Refresh Speed Again",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"})," Click to re-analyze the page speed using Google PageSpeed Insights."]})}},{selector:'[data-tour="test-mode"]',content:{header:"Test-Mode",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"})," Enable this to keep RapidLoad changes from going live."]})}},{selector:'[data-tour="preview-button"]',content:{header:"Preview",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"})," Click to see how the page looks after applying RapidLoad optimization before going live."]})}},{selector:'[data-tour="speed-settings"]',content:{header:"Speed Settings",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"})," Configure the RapidLoad plugin with one-click gears."]})}},{selector:'[data-tour="settings-gear"]',content:{header:"Performance gears",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"})," Select your Performance Mode: Starter, Accelerate, TurboMax, or Customize, to fine-tune your site's speed."]})},position:"right"},{selector:'[data-tour="customize-settings"]',content:{header:"Customize Settings",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"})," Tailor your site's performance settings to your specific requirements."]})},position:"right"},{selector:'[data-tour="speed-insights"]',content:{header:"Your Speed Insights",body:"See your overall website's speed rating, along with a breakdown of key metrics and performance scores."},position:"right"},{selector:'[data-tour="expand-metrics"]',content:{header:"Metrics",body:e.jsxs(e.Fragment,{children:[" ",e.jsx(a,{className:"mb-2"}),"Click on ”Expand Metrics” to identify individual metrics to uncover insights and get recommendations for enhancement."]})},position:"right"},{selector:'[data-tour="audit-groups"]',content:{header:"Dive into Audit Groups",body:e.jsxs("div",{className:"flex flex-col gap-2 ",children:[e.jsx(a,{}),e.jsx("div",{className:"text-md border-b pb-4",children:"Click on each audit group to explore detailed insights and actions."}),e.jsx("div",{className:"text-sm text-brand-600",children:e.jsxs("ul",{className:"dark:text-brand-200 text-brand-800 flex flex-col gap-2 [&>*]:flex [&>*]:flex-col [&>*]:gap-1",children:[e.jsxs("li",{children:[e.jsx("span",{className:"font-bold ",children:"Opportunities"})," Recommendations from Google to enhance your page's speed and efficiency."]}),e.jsxs("li",{children:[e.jsx("span",{className:"font-bold d",children:"Diagnostics"})," In-depth feedback about your site's performance and potential issues."]}),e.jsxs("li",{children:[e.jsx("span",{className:"font-bold ",children:"Passed Audits"})," Areas where your website meets or exceeds performance standards."]})]})})]})},position:"left"}],W=[{selector:'[data-tour="save-changes"]',content:{header:"Saving Your Optimizations",body:e.jsxs(e.Fragment,{children:[e.jsx(a,{className:"mb-2"}),"After making changes, remember to save. Tap the ",e.jsx("span",{className:"text-purple-750",children:'"Save Changes"'})," button to ensure all your tweaks are updated."]})},position:(o,t)=>{var s;let c=document.getElementById("rapidload-optimizer-shadow-dom"),l=(s=c==null?void 0:c.shadowRoot)==null?void 0:s.querySelector('[data-tour="save-changes"]');if(l){let d=l.getBoundingClientRect();return[Number(d.x+d.width)-o.width,d.y-o.height-25]}return"top"},padding:{popover:[100,25]}}],V=({mode:o})=>{const{data:t,loading:c}=M(D),{setIsOpen:l,isOpen:s,setSteps:d,currentStep:u,setCurrentStep:b}=B(),{activeTab:Z,isTourOpen:f,activeMetric:P,dispatch:y}=I(),{activeReport:x}=M(r=>r.app);m.useEffect(()=>{var j,S,k,w,C,N,z,A,T,E,F,O;let r=(S=(j=t==null?void 0:t.grouped)==null?void 0:j.opportunities)==null?void 0:S.find(n=>{var p,g,i;return((p=n==null?void 0:n.settings)==null?void 0:p.length)>0&&((i=(g=n==null?void 0:n.files)==null?void 0:g.items)==null?void 0:i.length)>0});r||(r=(w=(k=t==null?void 0:t.grouped)==null?void 0:k.diagnostics)==null?void 0:w.find(n=>{var p,g,i;return((p=n==null?void 0:n.settings)==null?void 0:p.length)>0&&((i=(g=n==null?void 0:n.files)==null?void 0:g.items)==null?void 0:i.length)>0})),!r&&((N=(C=t==null?void 0:t.grouped)==null?void 0:C.opportunities)!=null&&N.length)&&((A=(z=t==null?void 0:t.grouped)==null?void 0:z.opportunities)==null?void 0:A.length)>0&&(r=t.grouped.opportunities[0]),!r&&((E=(T=t==null?void 0:t.grouped)==null?void 0:T.diagnostics)!=null&&E.length)&&((O=(F=t==null?void 0:t.grouped)==null?void 0:F.diagnostics)==null?void 0:O.length)>0&&(r=t.grouped.diagnostics[0]),d&&d(n=>{let p=document.getElementById("rapidload-optimizer-shadow-dom");return[...U,...r?G(r):[],...o==="normal"?W:[]].map(i=>{var R;return p&&(i.shadowSelector=typeof i.selector=="string"?i.selector:i.shadowSelector,i.selector=(R=p.shadowRoot)==null?void 0:R.querySelector(i.shadowSelector)),i})})},[x,u]),m.useEffect(()=>{s||b(0)},[x]),m.useEffect(()=>{P&&y(h("activeMetric",null)),u===6&&y(h("activeTab","configurations")),u===10&&y(h("activeTab","opportunities"))},[u]),m.useEffect(()=>{},[s]);const v=()=>{const r=document.getElementById("rapidload-page-optimizer-content");s&&r?r.style.overflowY="hidden":r&&(r.style.overflowY="auto")};return m.useEffect(()=>{f!==s&&(v(),l(f))},[f]),m.useEffect(()=>{f!==s&&(v(),y(Y("isTourOpen",s)))},[s]),e.jsx(e.Fragment,{})};export{V as default};
//# sourceMappingURL=InitTour.riwGCbvY.js.map
