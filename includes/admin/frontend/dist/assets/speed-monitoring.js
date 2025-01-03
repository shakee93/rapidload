import{_ as m,e as _,c,a as d,b as e,d as u,w as x,f as a,v as i,F as v,r as f,g as b,o as p,n,h as r,i as w}from"./index.min.js";const y={name:"speed-monitoring",components:{Vue3TagsInput:_},methods:{},data(){return{base:c.is_plugin?c.public_base+"images/":"public/images/",tag:"",tags:["Elementor"],refresh_element:!1,page_animation:!0,pages_with_rules1:!1,advance_settings1:!1,pages_with_rules2:!1,advance_settings2:!1,remove_css:!1,back:"/",buttons:[{load_original_css:"user_interaction"}]}}},k={class:"rl-container mx-auto bg-white border-solid border border-gray-border-line inline-grid rounded-lg"},z={class:"flex border-y border-gray-border-line p-4 mb-6 pr-8 border-t-0"},M={class:"flex-initial w-32 pl-8"},C=e("svg",{width:"28",height:"28",viewBox:"0 0 28 28",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[e("path",{d:"M21.5833 14H7M7 14L14 7M7 14L14 21",stroke:"black","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round"})],-1),V={class:"flex mt-1"},R={class:"pr-1"},S={class:"items-center mr-4 mt-3"},B=e("div",null,[e("h1",{class:"font-semibold text-base text-black-font"},"Remove Unused CSS"),e("p",{class:"text-sm text-gray-font"},"Remove unused css and generate optimized css files with only with used CSS")],-1),H={class:"p-4 pl-32 pr-72"},A=e("h1",{class:"font-semibold text-base text-black-font"},"Load Original CSS",-1),L=e("p",{class:"text-sm pb-3 text-gray-font"},"How to load the original CSS files?",-1),F=["onClick"],T=["onClick"],U=["onClick"],j=e("div",{class:"mt-5 bg-purple-lite border border-purple rounded-2xl px-4 py-3 shadow-md",role:"alert"},[e("div",{class:"flex"},[e("div",{class:"py-1 mt-1"},[e("svg",{class:"fill-current h-6 w-6 text-purple mr-4",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 20 20"},[e("path",{d:"M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"})])]),e("div",null,[e("p",{class:"font-semibold text-sm text-purple-back-font leading-5"},[r("Removing the original files from loading may not be compatible with all the websites. "),e("br"),r(" If you are having site-breaks try on user interaction or asynchronously.")])])])],-1),N=[j],O=e("h1",{class:"font-semibold text-base text-black-font mt-5"},"Force Include selectors",-1),E=e("p",{class:"text-sm pb-3 text-gray-font"},"These selectors will be forcefully included into optimization.",-1),I=e("div",{class:"grid mb-5"},[e("textarea",{class:"resize-none z-10 appearance-none border border-gray-button-border rounded-lg w-full py-2 px-3 h-20 text-gray-700 leading-tight focus:outline-none focus:shadow-outline",id:"force-include",type:"text",placeholder:""}),e("div",{class:"-mt-3 bg-gray-lite-background rounded-lg px-4 py-4 pb-2",role:"alert"},[e("p",{class:"text-sm text-dark-gray-font"},"One selector rule per line. You can use wildcards as well ‘elementor-*, *-gallery’ etc...")])],-1),P=e("h1",{class:"font-semibold text-base text-black-font"},"Force Exclude selectors",-1),Y=e("p",{class:"text-sm pb-3 text-gray-font"},"These selectors will be forcefully excluded from optimization.",-1),G=e("div",{class:"grid mb-5"},[e("textarea",{class:"resize-none z-10 appearance-none border border-gray-button-border rounded-lg w-full py-2 px-3 h-20 text-gray-700 leading-tight focus:outline-none focus:shadow-outline",id:"force-include",type:"text",placeholder:""}),e("div",{class:"-mt-3 bg-gray-lite-background rounded-lg px-4 py-4 pb-2",role:"alert"},[e("p",{class:"text-sm text-dark-gray-font"},"One selector rule per line. You can use wildcards as well ‘elementor-*, *-gallery’ etc...")])],-1),D=e("h1",{class:"font-semibold text-base text-black-font"},"Selector Packs",-1),q=e("p",{class:"text-sm pb-3 text-gray-font"},"Selector packs contains predefined force exclude and include rules for plugins and themes.",-1),J={class:"grid mb-5"},K={class:"flex text-sm"},Q={class:"mt-3 z-50 -ml-9 cursor-pointer"},W=w('<g class="" clip-path="url(#clip0_49_525)"><path d="M11.466 4.33334C10.6301 2.42028 8.72122 1.08334 6.5 1.08334C3.6913 1.08334 1.38187 3.22113 1.11011 5.95834" stroke="#7F54B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.20825 4.33333H11.5916C11.7711 4.33333 11.9166 4.18783 11.9166 4.00833V1.625" stroke="#7F54B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M1.56079 8.66666C2.39665 10.5797 4.30557 11.9167 6.52676 11.9167C9.33546 11.9167 11.6449 9.77886 11.9167 7.04166" stroke="#7F54B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3.81844 8.66666H1.43511C1.25562 8.66666 1.11011 8.81215 1.11011 8.99166V11.375" stroke="#7F54B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></g>',1),X=[W],Z=e("div",{class:"-mt-3 bg-gray-lite-background rounded-lg px-4 py-4 pb-2",role:"alert"},[e("p",{class:"text-sm text-dark-gray-font"},"Search by plugin or theme name. You can add multiple packs.")],-1),$={class:"mb-5"},ee={class:"flex"},te={class:"pr-1"},se={class:"flex items-center mr-4 mt-3"},oe=e("div",null,[e("h1",{class:"font-semibold text-base text-black-font"},"Group pages with rules"),e("p",{class:"text-sm text-gray-font"},"This can help you group pages which has same html structure. Product pages, Category pages etc...")],-1),re=e("button",{class:"bg-transparent mb-3 text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg"}," Manage Rules ",-1),le=e("div",{class:"mb-5 bg-purple-lite border border-purple rounded-lg px-4 py-3 shadow-md",role:"alert"},[e("div",{class:"flex"},[e("div",{class:"py-1 mt-1"},[e("svg",{class:"fill-current h-6 w-6 text-purple mr-4",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 20 20"},[e("path",{d:"M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"})])]),e("div",null,[e("p",{class:"font-semibold text-sm text-purple-back-font leading-5"},[r("Recommended for websites with 50 plus pages. RapidLoad will analyze a parent"),e("br"),r(" page and will apply results for all matched pages.")])])])],-1),ne=[re,le],ae={class:"mb-5"},ie={class:"flex"},de={class:"pr-1"},pe={class:"flex items-center mr-4 mt-3"},ce=e("div",null,[e("h1",{class:"font-semibold text-base text-black-font"},"Group pages with rules"),e("p",{class:"text-sm text-gray-font"},"This can help you group pages which has same html structure. Product pages, Category pages etc...")],-1),ue=e("button",{class:"bg-transparent mb-3 text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg"}," Manage Rules ",-1),be=e("div",{class:"mb-5 bg-purple-lite border border-purple rounded-lg px-4 py-3 shadow-md",role:"alert"},[e("div",{class:"flex"},[e("div",{class:"py-1 mt-1"},[e("svg",{class:"fill-current h-6 w-6 text-purple mr-4",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 20 20"},[e("path",{d:"M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"})])]),e("div",null,[e("p",{class:"font-semibold text-sm text-purple-back-font leading-5"},[r("Recommended for websites with 50 plus pages. RapidLoad will analyze a parent"),e("br"),r(" page and will apply results for all matched pages.")])])])],-1),he=[ue,be],ge={class:"mb-5"},me={class:"flex"},_e={class:"pr-1"},xe={class:"flex items-center mr-4 mt-3"},ve=e("div",null,[e("h1",{class:"font-semibold text-base text-black-font"},"Advanced Settings"),e("p",{class:"text-sm text-gray-font"},"More advanced options for pro users")],-1),fe=e("button",{class:"bg-transparent mb-3 text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg"}," Manage Rules ",-1),we=e("div",{class:"mb-5 bg-purple-lite border border-purple rounded-lg px-4 py-3 shadow-md",role:"alert"},[e("div",{class:"flex"},[e("div",{class:"py-1 mt-1"},[e("svg",{class:"fill-current h-6 w-6 text-purple mr-4",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 20 20"},[e("path",{d:"M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"})])]),e("div",null,[e("p",{class:"font-semibold text-sm text-purple-back-font leading-5"},[r("Recommended for websites with 50 plus pages. RapidLoad will analyze a parent"),e("br"),r(" page and will apply results for all matched pages.")])])])],-1),ye=[fe,we],ke={class:"mb-5"},ze={class:"flex"},Me={class:"pr-1"},Ce={class:"flex items-center mr-4 mt-3"},Ve=e("div",null,[e("h1",{class:"font-semibold text-base text-black-font"},"Advanced Settings"),e("p",{class:"text-sm text-gray-font"},"More advanced options for pro users")],-1),Re=e("button",{class:"bg-transparent mb-3 text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg"}," Manage Rules ",-1),Se=e("div",{class:"mb-5 bg-purple-lite border border-purple rounded-lg px-4 py-3 shadow-md",role:"alert"},[e("div",{class:"flex"},[e("div",{class:"py-1 mt-1"},[e("svg",{class:"fill-current h-6 w-6 text-purple mr-4",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 20 20"},[e("path",{d:"M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"})])]),e("div",null,[e("p",{class:"font-semibold text-sm text-purple-back-font leading-5"},[r("Recommended for websites with 50 plus pages. RapidLoad will analyze a parent"),e("br"),r(" page and will apply results for all matched pages.")])])])],-1),Be=[Re,Se],He=e("button",{class:"bg-transparent mb-3 text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg"}," Save Settings ",-1),Ae=e("div",{class:"pb-6"},null,-1);function Le(Fe,s,Te,Ue,t,je){const h=b("RouterLink"),g=b("vue3-tags-input");return p(),d("main",null,[e("div",k,[e("div",z,[e("div",M,[u(h,{type:"button",to:t.back,class:"bg-white transition duration-300 hover:bg-purple-lite hover:text-white rounded-full px-3 py-3 text-center inline-flex items-center"},{default:x(()=>[C]),_:1},8,["to"])]),e("div",V,[e("div",R,[e("div",S,[e("label",null,[a(e("input",{id:"purple-checkbox","onUpdate:modelValue":s[0]||(s[0]=l=>t.remove_css=l),type:"checkbox",value:"",class:"rounded-lg checkmark accent-purple w-4 h-4 transition duration-200 text-purple-600 bg-purple-100 rounded border-purple-300 dark:ring-offset-purple-800 dark:bg-purple-700 dark:border-purple-600"},null,512),[[i,t.remove_css]])])])]),B])]),e("div",null,[e("div",H,[(p(!0),d(v,null,f(t.buttons,l=>(p(),d("div",null,[A,L,e("button",{onClick:o=>l.load_original_css="user_interaction",class:n([{active:l.load_original_css==="user_interaction"},"bg-transparent text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent rounded-l-lg"])}," On user interaction ",10,F),e("button",{onClick:o=>l.load_original_css="asynchronously",class:n([{active:l.load_original_css==="asynchronously"},"bg-transparent text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border-y border-gray-button-border hover:border-transparent"])}," Asynchronously ",10,T),e("button",{onClick:o=>l.load_original_css="remove",class:n([{active:l.load_original_css==="remove"},"bg-transparent text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent rounded-r-lg"])}," Remove ",10,U),e("div",{class:n([{expand:l.load_original_css==="remove"},"not-expand"])},N,2),O,E,I,P,Y,G,D,q,e("div",J,[e("div",K,[u(g,{tags:t.tags,class:"flex resize-none z-50 appearance-none border border-gray-button-border rounded-lg w-full p-1 text-gray-700 leading-tight focus:outline-none focus:shadow-outline",placeholder:"Type your plugin..."},null,8,["tags"]),e("div",Q,[(p(),d("svg",{class:n([{"animate-spin":t.refresh_element},"fill-none transition ease-in-out"]),onClick:s[1]||(s[1]=o=>t.refresh_element=!t.refresh_element),width:"20px",height:"20px",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 13 13"},X,2))])]),Z]),e("div",$,[e("div",ee,[e("div",te,[e("div",se,[e("label",null,[a(e("input",{"onUpdate:modelValue":s[2]||(s[2]=o=>t.pages_with_rules1=o),type:"checkbox",value:"",class:"accent-purple w-4 h-4 transition duration-200 text-purple-600 bg-purple-100 rounded border-purple-300 dark:ring-offset-purple-800 dark:bg-purple-700 dark:border-purple-600"},null,512),[[i,t.pages_with_rules1]])])])]),oe]),e("div",{class:n([{expand:t.pages_with_rules1},"pl-9 not-expand"])},ne,2)]),e("div",ae,[e("div",ie,[e("div",de,[e("div",pe,[e("label",null,[a(e("input",{"onUpdate:modelValue":s[3]||(s[3]=o=>t.pages_with_rules2=o),type:"checkbox",value:"",class:"accent-purple w-4 h-4 transition duration-200 text-purple-600 bg-purple-100 rounded border-purple-300 dark:ring-offset-purple-800 dark:bg-purple-700 dark:border-purple-600"},null,512),[[i,t.pages_with_rules2]])])])]),ce]),e("div",{class:n([{expand:t.pages_with_rules2},"pl-9 not-expand"])},he,2)]),e("div",ge,[e("div",me,[e("div",_e,[e("div",xe,[e("label",null,[a(e("input",{"onUpdate:modelValue":s[4]||(s[4]=o=>t.advance_settings1=o),type:"checkbox",value:"",class:"accent-purple w-4 h-4 transition duration-200 text-purple-600 bg-purple-100 rounded border-purple-300 dark:ring-offset-purple-800 dark:bg-purple-700 dark:border-purple-600"},null,512),[[i,t.advance_settings1]])])])]),ve]),e("div",{class:n([{expand:t.advance_settings1},"pl-9 not-expand"])},ye,2)]),e("div",ke,[e("div",ze,[e("div",Me,[e("div",Ce,[e("label",null,[a(e("input",{"onUpdate:modelValue":s[5]||(s[5]=o=>t.advance_settings2=o),type:"checkbox",value:"",class:"accent-purple w-4 h-4 transition duration-200 text-purple-600 bg-purple-100 rounded border-purple-300 dark:ring-offset-purple-800 dark:bg-purple-700 dark:border-purple-600"},null,512),[[i,t.advance_settings2]])])])]),Ve]),e("div",{class:n([{expand:t.advance_settings2},"pl-9 not-expand"])},Be,2)])]))),256)),He])]),Ae])])}const Oe=m(y,[["render",Le]]);export{Oe as default};
