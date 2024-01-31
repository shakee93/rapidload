import{_ as n,c as s,r as l,o as a,a as d,b as e,d as c,w as p,f as u,v as b}from"./index.min.js";const _={name:"page-optimizer",data(){return{base:s.is_plugin?s.public_base+"images/":"public/images/",remove_css:!1,back:"/"}}},m={class:"rl-container mx-auto bg-white border-solid border border-gray-border-line inline-grid rounded-lg"},h={class:"flex border-y border-gray-border-line p-4 mb-6 pr-8 border-t-0"},g={class:"flex-initial w-32 pl-8"},f=e("svg",{width:"28",height:"28",viewBox:"0 0 28 28",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[e("path",{d:"M21.5833 14H7M7 14L14 7M7 14L14 21",stroke:"black","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round"})],-1),v={class:"flex mt-1"},x={class:"pr-1"},k={class:"items-center mr-4 mt-3"},w=e("div",null,[e("h1",{class:"font-semibold text-base text-black-font"},"Remove Unused CSS"),e("p",{class:"text-sm text-gray-font"},"Remove unused css and generate optimized css files with only with used CSS")],-1);function y(C,o,B,L,t,M){const r=l("RouterLink");return a(),d("main",null,[e("div",m,[e("div",h,[e("div",g,[c(r,{type:"button",to:t.back,class:"bg-white transition duration-300 hover:bg-purple-lite hover:text-white rounded-full px-3 py-3 text-center inline-flex items-center"},{default:p(()=>[f]),_:1},8,["to"])]),e("div",v,[e("div",x,[e("div",k,[e("label",null,[u(e("input",{id:"purple-checkbox","onUpdate:modelValue":o[0]||(o[0]=i=>t.remove_css=i),type:"checkbox",value:"",class:"rounded-lg checkmark accent-purple w-4 h-4 transition duration-200 text-purple-600 bg-purple-100 rounded border-purple-300 dark:ring-offset-purple-800 dark:bg-purple-700 dark:border-purple-600"},null,512),[[b,t.remove_css]])])])]),w])])])])}const S=n(_,[["render",y]]);export{S as default};
