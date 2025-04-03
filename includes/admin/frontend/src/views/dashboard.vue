<template>
  <main>
    <div :class="{disableBlock: !license_information.licensed_domain}" class="max-w-[935px] mb-6">
      <div class="flex justify-between">
        <div class="flex gap-2">
          <div class="relative flex items-center gap-1 rounded-[14px] bg-white p-1 ">
          <span :class="test_mode?'left-[66px] w-[76px] bg-amber-500/80':'left-[4px] w-[60px] bg-slate-200'"
                class="absolute top-1 h-9 rounded-[14px] duration-200 "></span>
            <button @click="testMode(false)"
                    class="flex gap-1 h-9 items-center w-14 justify-center rounded-[14px] px-1.5 text-xsmm font-medium duration-200 relative"
                    :class="!test_mode?'text-dark-5 dark:text-dark':'text-gray-font hover:bg-gray-1 hover:text-dark '">
              <div class="inline-flex">
              <span
                  class="stroke-0 animate-ping absolute inline-flex opacity-75 h-1.5 w-1.5 rounded-[14px]"
                  :class="test_mode ? 'fill-gray bg-gray' : 'fill-tips-green-head bg-tips-green-head'"/>
                <span
                    class="stroke-0 relative inline-flex h-1.5 w-1.5 rounded-[14px]"
                    :class="test_mode ? 'fill-gray bg-gray' : 'fill-tips-green-head bg-tips-green-head'"/>
              </div>
              Live
            </button>
            <button @click="testMode(true)"
                    class="inline-flex h-9 items-center justify-center rounded-[14px] px-3 text-xsmm font-medium duration-200 text-nowrap relative"
                    :class="test_mode?'text-dark-5 dark:text-dark text-white':'text-gray-font hover:bg-gray-1 hover:text-dark '">
              Test Mode
            </button>
          </div>
          <a :href="preview" target="_blank" :class="{disableBlock: !license_information.licensed_domain}">
            <div @mouseover="preview_over = true"
                 @mouseleave="preview_over = false"
                 class="relative flex items-center gap-1 rounded-[14px] bg-white p-[11px] ">
              <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M8.55156 11.9895C8.14883 12.3923 8.14883 13.0452 8.55156 13.448C8.95429 13.8507 9.60724 13.8507 10.01 13.448L17.187 6.27091V8.59375C17.187 9.16329 17.6487 9.625 18.2183 9.625C18.7878 9.625 19.2495 9.16329 19.2495 8.59375V3.78125C19.2495 3.21171 18.7878 2.75 18.2183 2.75H13.4058C12.8362 2.75 12.3745 3.21171 12.3745 3.78125C12.3745 4.35079 12.8362 4.8125 13.4058 4.8125H15.7286L8.55156 11.9895Z"
                    fill="#757476"/>
                <path
                    d="M4.81201 9.28125C4.81201 8.33201 5.58152 7.5625 6.53076 7.5625H9.62451C10.1941 7.5625 10.6558 7.10079 10.6558 6.53125C10.6558 5.96171 10.1941 5.5 9.62451 5.5H6.53076C4.44244 5.5 2.74951 7.19292 2.74951 9.28125V15.4688C2.74951 17.5571 4.44244 19.25 6.53076 19.25H12.7183C14.8066 19.25 16.4995 17.5571 16.4995 15.4688V12.375C16.4995 11.8055 16.0378 11.3438 15.4683 11.3438C14.8987 11.3438 14.437 11.8055 14.437 12.375V15.4688C14.437 16.418 13.6675 17.1875 12.7183 17.1875H6.53076C5.58152 17.1875 4.81201 16.418 4.81201 15.4688V9.28125Z"
                    fill="#757476"/>
              </svg>
              <svg :class="loading? 'block' : 'hidden-important'" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                   xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </a>

          <div :class="preview_over ? 'rl-Show' : 'rl-Hide'" class="-mt-[30px] mr-6 px-2 absolute ml-[128px]">
            <div
                class="arrow-top-copied font-medium text-xsmm relative bg-purple-lite leading-arw-mbox text-center text-purple rounded-[7px] px-2">
              Preview
            </div>
          </div>

        </div>
        <div class="flex gap-3">
          <div class="relative flex items-center gap-1 rounded-[14px] bg-white p-[5px]">
            <a :href="support" target="_blank" :class="{disableBlock: !license_information.licensed_domain}">
              <button @click=""
                      class="bg-white hover:bg-purple doc hover:doc-hover font-medium hover:text-white cursor-pointer h-[34px] items-center text-xsmm flex transition duration-300 py-1 px-3 rounded-[14px]">
                2.0 Feedback
              </button>
            </a>
          </div>
          <div class="relative flex items-center gap-1 rounded-[14px] bg-white p-[5px]">
            <a :href="docs" target="_blank" :class="{disableBlock: !license_information.licensed_domain}">
              <button @click=""
                      class="bg-white hover:bg-purple doc hover:doc-hover font-medium hover:text-white cursor-pointer h-[34px] items-center text-xsmm flex transition duration-300 py-1 px-3 rounded-[14px]">
                Documentation
              </button>
            </a>
          </div>

          <RouterLink :class="{disableBlock: !license_information.licensed_domain}"
                      to="/settings">
            <div @mouseover="general_over = true"
                 @mouseleave="general_over = false"
                 class="relative flex items-center gap-1 rounded-[14px] bg-white p-[9px] mr-2">

              <button>
                <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M14.9361 4.1222C14.4434 2.09257 11.5568 2.09257 11.0641 4.1222C10.7458 5.43332 9.24368 6.05552 8.09151 5.35349C6.30793 4.26674 4.26686 6.30781 5.35361 8.09139C6.05564 9.24356 5.43344 10.7457 4.12232 11.064C2.09269 11.5567 2.09269 14.4432 4.12232 14.936C5.43344 15.2543 6.05564 16.7564 5.35361 17.9086C4.26686 19.6921 6.30793 21.7332 8.09151 20.6465C9.24368 19.9444 10.7458 20.5666 11.0641 21.8778C11.5568 23.9074 14.4434 23.9074 14.9361 21.8778C15.2544 20.5666 16.7565 19.9444 17.9087 20.6465C19.6923 21.7332 21.7333 19.6921 20.6466 17.9086C19.9446 16.7564 20.5668 15.2543 21.8779 14.936C23.9075 14.4432 23.9075 11.5567 21.8779 11.064C20.5668 10.7457 19.9446 9.24356 20.6466 8.09139C21.7333 6.30781 19.6923 4.26674 17.9087 5.35349C16.7565 6.05552 15.2544 5.43332 14.9361 4.1222ZM13.0001 16.9C15.154 16.9 16.9001 15.1539 16.9001 13C16.9001 10.8461 15.154 9.09998 13.0001 9.09998C10.8462 9.09998 9.1001 10.8461 9.1001 13C9.1001 15.1539 10.8462 16.9 13.0001 16.9Z"
                        fill="#757476"/>
                </svg>
              </button>

            </div>
          </RouterLink>
          <div :class="general_over ? 'rl-Show' : 'rl-Hide'" class="-mt-[30px] mr-6 px-2 absolute ml-[172px]">
            <div
                class="arrow-top-copied font-medium text-xsmm relative bg-purple-lite leading-arw-mbox text-center text-purple rounded-[7px] px-2">
              General Settings
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex">


    <div class="w-[448px] h-[165px] mb-8 drop-shadow-sm rounded-xl border-2 border-purple bg-tips-purple mr-8">

      <div class="pl-6 pr-6 mt-3 inline-flex">
      <div class="leading-[15px] text-xsss font-medium flex mb-1 transition duration-300 bg-purple text-white py-1 px-3 border border-purple rounded-full">
        <svg class="mr-2 mt-[3px]" xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="#fff" viewBox="0 0 351.72 351.52"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M64.19,286.1c-12.92-2.89-24.31-8.05-34.19-16.22C10.71,253.92.5,233.28.29,208.3q-.54-64.38,0-128.77C.66,39.23,29.38,6.8,69.19.85A86.68,86.68,0,0,1,81.88.05Q176,0,270.13,0c39.6,0,71.76,25.8,79.8,64.05a87.45,87.45,0,0,1,1.68,17.47q.21,62.33.06,124.65c-.08,40.19-26.53,72.52-65.6,80a90.8,90.8,0,0,1-16.74,1.28c-28.32.13-56.64,0-84.95.15a12.6,12.6,0,0,0-6.52,2q-43.65,28.91-87.14,58.06c-3.62,2.42-7.41,4.16-11.86,3.74-8.56-.8-14.55-7.23-14.62-16-.13-15,0-29.95,0-44.92ZM176,127.88h74.87c7.11,0,14.23.11,21.33,0a15.9,15.9,0,0,0,15.27-13c1.3-7.91-2.84-15.37-10.29-17.83a26.11,26.11,0,0,0-8.08-1.09q-93.21-.09-186.41,0a31,31,0,0,0-3.74.11c-6.64.78-11.43,4.15-13.79,10.48-4,10.67,3.88,21.39,15.77,21.4Q128.45,127.94,176,127.88Zm-.27,63.84H212c9.48,0,19,.17,28.43-.09a15.76,15.76,0,0,0,14.76-20.14c-2-7.24-8.15-11.58-16.75-11.59q-62.49,0-125,0c-1,0-2,0-3,.09a15.79,15.79,0,0,0-13.88,11.8c-2.57,10.45,4.78,19.83,15.87,19.89C133.55,191.8,154.63,191.71,175.71,191.72Z"/></g></g></svg>
        Feeling lost?
      </div>
      </div>

      <div class="pl-6 pr-6 pt-1 min-h-[76px]">
        <h2 class="mb-1 text-xsm text-purple-tips-text font-semibold">RapidLoad support is here</h2>
        <p class="text-xsmm text-purple-tips-text font-normal">If you need an extra hand, We are here to help. Take a look at our knowledge base or feel free to reach us.</p>
      </div>

      <div class="actions pl-6 pr-6 pb-2 grid grid-cols-2 gap-4">

        <div class="col-end-7 col-span-2">
          <a :href="support" target="_blank">
            <button @click=""
                class="text-xsss mb-3 text-black-font transition duration-300 bg-purple font-semibold text-white py-[6px] px-8 border border-purple hover:border-transparent rounded-lg">
              Get Help</button>
          </a>
        </div>

      </div>
    </div>

      <div class="w-[448px] h-[165px] mb-8 drop-shadow-sm rounded-xl border-2 border-tips-border-green bg-tips-green-bg mr-8">

        <div class="pl-6 pr-6 mt-3 inline-flex">
          <div class="leading-[15px] text-xsss font-medium flex mb-1 transition duration-300 bg-tips-green-head text-white py-1 px-3 border border-tips-green-head rounded-full">
            <svg class="mr-2 mt-[2px]" width="9" height="12" viewBox="0 0 10 12" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7.33922 9.53101H2.7373V11.174H7.33922V9.53101Z" fill="white"/>
              <path d="M5.03476 2.5883e-06C4.10821 -0.000674638 3.2066 0.300187 2.46613 0.857142C1.72567 1.4141 1.18655 2.19689 0.930235 3.08728C0.673915 3.97767 0.714311 4.92729 1.04532 5.7927C1.37633 6.6581 1.97997 7.39229 2.76507 7.88435H7.29313C8.07855 7.39249 8.68251 6.65832 9.01374 5.79282C9.34497 4.92731 9.38547 3.97751 9.1291 3.08695C8.87273 2.1964 8.33344 1.4135 7.59273 0.856575C6.85202 0.299654 5.95015 -0.00101774 5.02343 2.5883e-06H5.03476Z" fill="white"/>
            </svg>
            Tips to improve PageSpeed
          </div>
        </div>

        <div class="pl-6 pr-6 pt-1 min-h-[76px] flex">
          <div  :class="improvetips_count=== improvetips_count? 'tips-slide':'tips-hidden'">
            <h2 class="mb-1 text-xsm text-tips-dark-green-font font-semibold" >{{tips[improvetips_count].title}}</h2>
            <p class="text-xsmm text-tips-dark-green-font font-normal" v-html="tips[improvetips_count].description"></p>
          </div>
        </div>

        <div class="actions pl-6 pr-6 pb-2 grid grid-cols-2 gap-4">
          <div class="col-start-1 col-end-3 mt-2" >
            <p class="mb-1 text-xsmm text-tips-dark-green font-normal">{{improvetips_count+1}} of
              {{tips.length}}</p>

          </div>
          <div class="col-end-7 col-span-2">
            <a :href="tips[improvetips_count].learn_more" target="_blank">
              <button class="mr-2 text-xsss mb-3 text-tips-dark-green transition duration-300 font-semibold">
                Learn more</button>
            </a>

              <button @click="tipsimprovenext"
                      class="text-xsss mb-3 text-black-font transition duration-300 bg-tips-dark-green font-semibold text-white py-[6px] px-8 border border-tips-dark-green hover:border-transparent rounded-lg">
                Next</button>

          </div>

        </div>
      </div>



    </div>

<!--    <optimization></optimization>-->

    <ul class="nav-items inline-grid grid grid-cols-3 gap-8">
      <messageBox></messageBox>
<!--      <performanceWidget :class="{disableBlock: !license_information.licensed_domain}"></performanceWidget>-->
      <li v-for="item in items" :key="item.id"
          :class="{disableBlock: !license_information.licensed_domain}" class="w-72 h-56 drop-shadow-sm rounded-xl border border-gray-border-line bg-white">
        <div>
          <div class="p-4 pb-6">
<!--            <img v-if="item.image" :src="base + item.image" :alt="item.title" width="49" height="49">-->
            <span v-html="item.image"></span>

            <h4 class="heading-margin text-black font-medium text-base opacity-80">{{ item.title }}</h4>
            <span class="dashboard-p text-xm text-slate-700 leading-db-lh m-0">{{ item.description }}</span>
          </div>
          <hr class="border-gray-border-line border-b-0 mt-1">
          <div class="actions p-4 mt-1 grid grid-cols-2 gap-4 items-center">

            <div class="col-start-1 col-end-3" >
              <RouterLink v-if="item.id !== 'cdn' && item.id !== 'page-optimizer' && license_information.licensed_domain" :class="item.status ? 'rl-Show' :'rl-Hide'" class=" text-xs bg-transparent mb-3 text-black-b transition duration-300 hover:bg-purple font-medium hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg"
                          :to="item.link">
                <button >Settings</button>
              </RouterLink>

              <RouterLink v-if="item.id === 'cdn' && license_information.licensed_domain" :class="item.status && !loading? 'rl-Show': 'rl-Hide'" class=" text-xs bg-transparent mb-3 text-black-b transition duration-300 hover:bg-purple font-medium hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg"
                          :to="item.link">
                <button >Settings</button>
              </RouterLink>

              <RouterLink @click="howtouse=true" v-if="item.id === 'page-optimizer' && license_information.licensed_domain"
                   :class="item.status? 'rl-Show': 'rl-Hide'"
                   class="cursor-pointer w-fit text-xs bg-transparent text-black-b transition duration-300 hover:bg-purple font-medium hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent rounded-lg"
                          :to="item.link">
                How to Use
              </RouterLink>




              <svg v-if="item.id === 'cdn'"  :class="loading? 'rl-Show': 'rl-Hide'" class="absolute" style="top:80.5%;" width="25" height="25" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g id="loader">
                  <animateTransform
                      xlink:href="#loader"
                      attributeName="transform"
                      attributeType="XML"
                      type="rotate"
                      from="0 50 50"
                      to="360 50 50"
                      dur="1s"
                      begin="0s"
                      repeatCount="indefinite"
                      restart="always"
                  />
                  <path class="a" opacity="0.2" fill-rule="evenodd" clip-rule="evenodd" d="M50 100C77.6142 100 100 77.6142 100 50C100 22.3858 77.6142 0 50 0C22.3858 0 0 22.3858 0 50C0 77.6142 22.3858 100 50 100ZM50 90C72.0914 90 90 72.0914 90 50C90 27.9086 72.0914 10 50 10C27.9086 10 10 27.9086 10 50C10 72.0914 27.9086 90 50 90Z" fill="#66B1DC"/>
                  <path class="b" fill-rule="evenodd" clip-rule="evenodd" d="M100 50C100 22.3858 77.6142 0 50 0V10C72.0914 10 90 27.9086 90 50H100Z" fill="#7F54B3"/>
                </g>
              </svg>
            </div>


            <div class="col-end-7 col-span-2 ...">
              <label :for="'toggle'+item.title" class="inline-flex relative items-center cursor-pointer">
                <input type="checkbox" v-model="item.status" @click="update(item.status, item.id)" value="" :id="'toggle'+item.title" class="sr-only peer">
                <div
                    class="w-11 h-6 bg-gray peer-focus:outline-none outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 transition duration-300 after:transition-all dark:border-gray peer-checked:bg-purple"></div>
              </label>
            </div>

          </div>
        </div>
      </li>

      <div v-if="!license_information.licensed_domain" class="w-72 h-56 drop-shadow-sm rounded-xl border border-gray-border-line bg-white mb-1">
        <!--        popup starts Here-->

        <div :class="connect_btn? 'rl-Show': 'rl-Hide'" class="w-72 h-56 transition duration-300 rounded-xl bg-white absolute mt-[0px] z-[100]">

          <div class="actions pl-4 pr-4 pb-2 grid grid-cols-2">
            <div class="col-start-1 col-end-3" >
              <h4 class="mt-4 text-gray-h text-base font-semibold">Enter your License Key below</h4>
            </div>

            <div class="col-end-7 col-span-2">
              <img @click="connect_btn = !connect_btn" class="absolute top-2 right-2 cursor-pointer" :src="base+'/close.svg'" alt="Back">
            </div>

          </div>

          <hr class="border-gray-border-line border-b-0">

          <div class="p-2 pl-4 pr-4 pb-1 font-normal">
            <p class="mb-1 text-gray-p text-xss"><b>Slow load times</b> are the <b>#1 reason</b> for <b>high
              bounce rates</b> and one of the root causes of
              <b>poor Google Rankings.</b></p>
          </div>

          <div class="p-2 pl-4 pr-4 pb-2.5 pt-1 mt-4">

            <div class="grid">
<!--              <div :class="connect_with_license_error.length ? 'Show' : 'Hide'" class="mt-0.5 license-error-popup" >-->
<!--                <div class="arrow-top font-medium text-xs relative bg-arrow-message leading-arw-mbox text-center text-arrow-message-tc rounded-[7px] px-2">-->
<!--                  {{ connect_with_license_error }}</div>-->
<!--              </div>-->
              <div :class="connect_with_license_error.length ? 'rl-Show' : 'rl-Hide'" class="flex mt-0.5 license-error-popup absolute" >
                <div class="font-medium text-xs relative leading-arw-mbox text-center text-arrow-message-tc rounded-[7px] px-2">
                  {{ connect_with_license_error }}</div>
                <img class="mt-3 w-3 absolute top-[26px] right-3.5" :src="base+'/error.svg'" alt="Back">
              </div>
              <input
                  @focus="focus='license-error'"
                  @blur="focus=''"
                  v-model="license_information.key"
                  :class="focus==='license-error' ? 'cdn-endpoint':'' || connect_with_license_error.length ? 'red-border' : 'gray-border' "
                  class="cdn resize-none text-xs z-50 appearance-none border rounded-lg w-full py-2 px-3 h-[2rem] text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                  id="force-include" type="text" placeholder="License Key">
            </div>
          </div>

          <div class="actions pl-2 pr-4 pb-2 grid grid-cols-2 gap-4">

            <div class="col-end-7 col-span-2">
              <button @click="connect_license" :disabled="license_loading" :class="license_loading? 'bg-purple text-white' : 'bg-transparent '"
                      class="disabled:opacity-50 flex text-xs text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-1.5 px-4 border border-gray-button-border hover:border-transparent rounded-lg"
              >
                <svg :class="license_loading? 'block' : 'hidden'" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                Connect</button>
            </div>

          </div>
        </div>

        <!--        ends here-->

        <div class="pl-4 pr-4 pb-2 pt-2">
          <h4 class="heading-margin text-gray-h text-base font-semibold">Connect your website</h4>
        </div>

        <hr class="border-gray-border-line border-b-0">

        <div class="p-2 pl-4 pr-4 pb-1 font-normal">
          <p class="mb-1 text-gray-p text-xss"><b>Slow load times</b> are the <b>#1 reason</b> for <b>high
            bounce rates</b> and one of the root causes of
            <b>poor Google Rankings.</b></p>
        </div>

        <div class="actions grid justify-center">
          <a :href="license_information.link" target="_blank">
            <button class="text-xss bg-purple font-semibold text-white py-2 px-4 border rounded-full"
            >Get RapidLoad</button>
          </a>
        </div>



        <p class="mb-1 text-xsm mt-2 text-color-grey pl-4">Already have license?</p>
        <div class="actions pl-4 pr-4 pb-2 grid grid-cols-2 gap-4">
          <div class="col-start-1 col-end-3" >
            <a :href="license_information.connect_link">
              <button class="text-xs bg-transparent mb-3 text-black-font transition duration-300 hover:bg-black-b font-medium hover:text-white py-1 px-4 border border-gray-button-border hover:border-transparent rounded-full"
              >Connect</button>
            </a>
          </div>

          <div class="col-end-7 col-span-2">
            <button @click="connect_btn = !connect_btn"
                    class="text-xs bg-transparent mb-3 text-black-font transition duration-300 hover:bg-black-b font-medium hover:text-white py-1 px-4 border border-gray-button-border hover:border-transparent rounded-full"
            >License Key</button>
          </div>

        </div>


      </div>

      <div v-if="license_information.licensed_domain"  class="w-72 h-56 drop-shadow-sm rounded-xl border border-gray-border-line bg-white">

        <div class="pl-4 pr-4 pb-2 pt-2 grid grid-cols-2 gap-4 items-center">
          <div class="col-start-1 col-end-3" >
            <h4 class="heading-margin text-gray-h text-base font-semibold">License Information</h4>
          </div>
          <div class="col-end-7 col-span-2">
            <svg class="heading-margin" v-if="tick_image" width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11.5" cy="11.5" r="11.5" fill="#09B42F"/>
              <path d="M7 11.3455L10.4068 15L16 9" stroke="white" stroke-width="2.5"/>
            </svg>
          </div>
        </div>

        <hr class="border-gray-border-line border-b-0">

        <div class="p-2 pl-4 pr-4 pb-1 pt-6">
          <p class="mb-1 text-xm text-black font-medium">Name: <span class="text-color-grey">{{license_information.name}}</span></p>
          <p class="mb-1 text-xm text-black font-medium">Expiration Date: <span class="text-color-grey">{{license_information.exp_date.toLocaleDateString()}}</span></p>
          <p class="mb-1 text-xm text-black font-medium">License: <span class="text-color-grey">{{license_information.license}}</span></p>
        </div>

        <!--        <p class="mb-1 text-sm mt-1 text-gray-500 pl-4">Want to change plan?</p>-->
        <div class="actions pt-1 pl-4 pr-4 pb-2 grid grid-cols-2 gap-4">

          <div class="col-start-1 col-end-3" >
            <a :href="license_information.link" target="_blank">
              <button class="text-xs bg-transparent mb-3 text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg">View My Account</button>
            </a>
          </div>

        </div>
      </div>

      <div v-if="on_board_complete===''"  class="w-72 h-56 drop-shadow-sm rounded-xl border border-gray-border-line bg-white">

        <div class="pl-4 pr-4 pb-2 pt-2 grid grid-cols-2 gap-4 items-center">
          <div class="col-start-1 col-end-3" >
            <h4 class="heading-margin text-gray-h text-base font-semibold">Onboard Pending</h4>
          </div>
          <div class="col-end-7 col-span-2">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 0C17.0775 0 22 4.9225 22 11C22 17.0775 17.0775 22 11 22C4.9225 22 0 17.0775 0 11C0 4.9225 4.9225 0 11 0ZM12.5538 12.8975L13.035 4.015H8.965L9.44625 12.8975H12.5538ZM12.43 17.5175C12.76 17.2013 12.9387 16.7612 12.9387 16.1975C12.9387 15.62 12.7738 15.18 12.4438 14.8638C12.1138 14.5475 11.6325 14.3825 10.9862 14.3825C10.34 14.3825 9.85875 14.5475 9.515 14.8638C9.17125 15.18 9.00625 15.62 9.00625 16.1975C9.00625 16.7612 9.185 17.2013 9.52875 17.5175C9.88625 17.8337 10.3675 17.985 10.9862 17.985C11.605 17.985 12.0863 17.8337 12.43 17.5175Z" fill="#EED202"/>
            </svg>
          </div>
        </div>

        <hr class="border-gray-border-line border-b-0">

        <div class="p-2 pl-4 pr-4 pb-1 pt-6">
          <p class="mb-1 text-xm text-black font-medium">You have not completed the onboard
            process. Please complete it by pressing
            the continue onboard button.  </p>
        </div>

        <!--        <p class="mb-1 text-sm mt-1 text-gray-500 pl-4">Want to change plan?</p>-->
        <div class="actions pt-1 pl-4 pr-4 pb-2 grid grid-cols-2 gap-4">

          <div class="col-start-1 col-end-3" >
              <button @click="openOptimizer" class="text-xs bg-transparent mb-3 text-black-font transition duration-300 hover:bg-purple font-semibold hover:text-white py-2 px-4 border border-gray-button-border hover:border-transparent mt-5 rounded-lg">Continue Onboard</button>
          </div>

        </div>
      </div>


    </ul>


<!--    <popupModel v-if="popupModel && !welcomeModel" @dont="handleDont" @cancel="handleCancel" :default="license_information.name"/>-->
    <howtoUse v-if="howtouse" @cancel="howtouse=false" @goto="gotoHome"/>
    <welcome v-if="welcomeModel" @start="startAnalyzing" @cancel="welcomeModel = false" />
    <update v-if="db_tobe_updated==='1' && !welcomeModel" @update="updateDatabase" @cancel="db_tobe_updated = false" :message="this.updateError"/>

  </main>
</template>

<script>

import config from "../config";
import axios from 'axios';
import messageBox from "../components/messageBox.vue";
import Vue3TagsInput from "vue3-tags-input";
import popupModel from "../components/popupModel.vue";
import howtoUse from "../components/howtoUse.vue";
import welcome from "../components/welcome.vue";
import performanceWidget from "../components/performanceWidget.vue";
import optimization from "./optimization.vue";
import update from "../components/update.vue";

// import onboard from "../../../on-board/on-board-view/src/views/onboard";

export default {

  components: {
    Vue3TagsInput,
    messageBox,
    popupModel,
    howtoUse,
    welcome,
    optimization,
    performanceWidget,
    update

  },

  mounted() {

    if (window.RapidLoadOptimizer) {

      const container = document.getElementById('rapidload-page-optimizer');
      const optimizerConfig = {
        container,
        showOptimizer: false,
        global: true,
      };

      if (this.on_board_complete === '') {
        optimizerConfig.mode = 'onboard';
        optimizerConfig.modeData = {
          connect_url: this.license_information.connect_link,
        };
      }

      new window.RapidLoadOptimizer(optimizerConfig);
    }
    window.addEventListener('rapidLoad:optimizer-mounted', () => {
      if (this.on_board_complete ==='') {
         this.welcomeModel=true;
      }else if(window.location.href.indexOf("source=rapidload_app") > -1 && localStorage.getItem('RapidLoadOptimizer') !== 'true'){

        if(this.db_tobe_updated===''){
          localStorage.setItem('RapidLoadOptimizer', 'true');
          this.openOptimizer();
        }

      }
    });


    //const rapidLoadLicense = JSON.parse(localStorage.getItem("rapidLoadLicense"));

    // if (rapidLoadLicense) {
     // Object.keys(rapidLoadLicense).forEach((a) => {
      //  const data = rapidLoadLicense[a];
      //  this.license_information.name = data.name
      //  this.license_information.exp_date = new Date(data.next_billing * 1000)
      //  this.license_information.license = data.plan
      //  this.license_information.licensed_domain = data.licensedDomain
      //})

      const rapidLoadLicense = window.uucss_global?.rapidload_license_data;

     // console.log("rapidLoadLicense", rapidLoadLicense);

      if (rapidLoadLicense) {
        // Map the object properties to license information
        this.license_information = {
        name: rapidLoadLicense.name,
        exp_date: new Date(rapidLoadLicense.next_billing * 1000),
        license: rapidLoadLicense.plan,
        licensed_domain: rapidLoadLicense.licensedDomain,
      };
     // this.update_license();

      const lastExecutionDate = localStorage.getItem('RapidLoadDashboardPopupModellastExecutionDate');
      const currentDate = new Date().toLocaleDateString();
      if (lastExecutionDate !== currentDate) {
        this.popupModel = !localStorage.getItem('RapidLoadDashboardPopupModel');

      }


    }else{
    //  this.update_license()
    }

    const activeModules = [];

    Object.keys(window.uucss_global.active_modules).forEach((a) => {
      activeModules.push(window.uucss_global.active_modules[a])
    });

    this.items_data = activeModules
    //console.log(this.items_data);

    if (this.items_data) {
      Object.keys(this.items_data).map((key) => {
        this.items.map((val) => {
          if (val.id === this.items_data[key].id) {
            val.status = this.items_data[key].status === 'on';
          }
        })
      });
    }

    this.test_mode = window.uucss_global?.test_mode === "1";

  },
  methods:{

    testMode(mode) {
      this.test_mode = mode;
      axios.post(window.uucss_global?.ajax_url + '?action=rapidload_switch_test_mode&test_mode=' + this.test_mode + '&nonce=' + window.uucss_global?.nonce).then((response) => {
        if (response.data?.data) {
          this.test_mode = response.data?.data.status;
        }
      })
    },

    gotoHome(){

      window.open(this.home_url, '_blank');
    },
    startAnalyzing(){
      this.openOptimizer();
      this.welcomeModel=false;
    },
    updateDatabase(){

      axios.post(window.uucss_global?.ajax_url + '?action=rapidload_db_update&nonce='+window.uucss_global?.nonce,{
        headers: {
          'Content-Type':'multipart/form-data'
        }
      }).then((response)=>{

        if(response.data?.success){

          window.location.reload();
        }else{
          this.updateError = response.data?.error;
        }
      })

    },
    // whatsnext(){
    //   this.whatstips_count++;
    //   if(this.whatstips_count===4){
    //     this.whatstips_count = 1;
    //   }
    //
    // },

    openOptimizer() {
      if (window.RapidLoadOptimizer) {
        window.RapidLoadOptimizer.showOptimizer(true);

      }
    },


    handleCancel(){
      this.popupModel = false;
      const currentDate = new Date().toLocaleDateString();
      localStorage.setItem('RapidLoadDashboardPopupModellastExecutionDate', currentDate);
    },
    handleDont(){
      this.popupModel = false;
      localStorage.setItem('RapidLoadDashboardPopupModel', 'false')
    },
    tipsimprovenext(){
      this.improvetips_count++;
      if(this.improvetips_count===5){
        this.improvetips_count = 0;
      }
    },

    connect_license(){
      this.license_loading = true;
      this.connect_with_license_error = "";
      const data = {
        license_key:this.license_information.key,

      }
      axios.post(window.uucss_global?.ajax_url + '?action=uucss_connect&nonce='+window.uucss_global?.nonce, data,{
        headers: {
          'Content-Type':'multipart/form-data'
        }
      }).then((response)=>{

        if(response.data?.success){
          window.location.href = '?rapidload_license=' + this.license_information.key + '&nonce=' + response.data.data.activation_nonce +'&page=rapidload#/'
          this.license_loading = false;
        }else{
          this.license_loading = false;
          if(typeof response.data?.data === "string"){
            this.connect_with_license_error = response.data?.data
          }else{
            this.connect_with_license_error = "Invalid License Key"
          }
        }
      })

    },

    disconnect_license(){
      const data = {
        disconnect:true,
      }
      axios.post(window.uucss_global?.ajax_url + '?action=uucss_license&nonce='+window.uucss_global?.nonce, data,{
        headers: {
          'Content-Type':'multipart/form-data'
        }
      })

    },
    update_license(){

      axios.post(window.uucss_global?.ajax_url + '?action=uucss_license&nonce='+window.uucss_global?.nonce).then((response)=>{
        if(response.data?.data){

          if(response.data?.data === 'License key authentication failed' || response.data?.data?.licensed_domain === null){
            this.disconnect_license();
            localStorage.clear();
            this.license_information.name = null
            this.license_information.exp_date = null
            this.license_information.license = null
            this.license_information.licensed_domain = null
            this.license_information.key = ''

          }else if(response.data?.data !== "cURL error 6: Could not resolve host: api.rapidload.io"){
            const licenseData = []
            licenseData.push(response.data?.data)
            localStorage.setItem('rapidLoadLicense', JSON.stringify(licenseData))
            this.license_information.name = response.data?.data?.name
            this.license_information.exp_date = new Date(response.data?.data?.next_billing * 1000)
            this.license_information.license = response.data?.data?.plan
            this.license_information.licensed_domain = response.data?.data?.licensedDomain
          }
        }
      })

    },

    update(toggle, module){

      if(!this.license_information.licensed_domain){

        return;

      }
      if(module==='cdn'){
        this.loading = true;
      }

      if(!toggle){
        toggle = "on";
      } else{
        toggle = "off";
      }

      if(this.axios_request){
        this.axios_request.cancel("");
        this.axios_request = null;
      }

      this.axios_request = axios.CancelToken.source();
      const cancelToken = this.axios_request.token;
      const data = {
        toggle: toggle,
        module: module,
      };

      //this.store_toggle_data.push(data);
      axios.post(window.uucss_global?.ajax_url + '?action=activate_module&module='+module+'&active='+toggle + '&nonce='+window.uucss_global?.nonce, {
        cancelToken: cancelToken
      })
          .then(response => {
            response.data
            window.uucss_global.active_modules = response.data.data
            this.loading = false;
            const activeModules = [];

            Object.keys(response.data.data).forEach((a) => {
              activeModules.push(response.data.data[a])
            });

            for (const item of activeModules) {
              if (item.id === data.module ) {
                if(item.status !== data.toggle){
                //  console.log("Mistmatch")
                  // console.log(`Mismatch found for module ${data.module}: Toggle is ${data.toggle}, but status is ${item.status}`);
                }
              }
            }
            this.items.map((item)=>{
             // item.status = response.data.data[item.id].status === "on";
            })
          })
          .catch(error => {
            this.errorMessage = error.message;

          });

    },
    // onboardOptimizer(){
    //   if (window.RapidLoadOptimizer) {
    //     let container = document.getElementById('rapidload-page-optimizer')
    //
    //     // open optimizer in onboard mode
    //     new window.RapidLoadOptimizer({
    //       container,
    //       showOptimizer: false,
    //       mode: 'onboard',
    //       modeData: {
    //         // this is the url where to redirect user when they press connect
    //         connect_url: this.license_information.connect_link,
    //         // target: '_blank'
    //       }
    //     });
    //   }
    // },
    // normalOptimizer(){
    //   if (window.RapidLoadOptimizer) {
    //     let container = document.getElementById('rapidload-page-optimizer')
    //
    //     new window.RapidLoadOptimizer({
    //       container,
    //       showOptimizer: false
    //     })
    //   }
    // },

  },

  data() {
    return {
      update_databse: false,
      store_toggle_data: [],
      on_board_complete: window.uucss_global.on_board_complete,
      onboard_link: window.uucss_global.home_url+'/wp-admin/options-general.php?page=rapidload-on-board#/',
      home_url: window.uucss_global.home_url,
      preview: window.uucss_global.home_url+'?rapidload_preview',
      popupModel: false,
      howtouse: false,
      welcomeModel: false,
      updateError:"",
      db_tobe_updated: window.uucss_global.db_tobe_updated,
      axios_request : null,
      tick_image: 'license-information.svg',
      whats_new: 'tips-whats.svg',
      tips_toimprove: 'tips-toimprove.svg',
      connect_btn: false,
      connect_with_license_error : '',
      // whatstips_count: 1,
      support: 'https://rapidload.zendesk.com/hc/en-us/requests/new',
      docs: ' https://docs.rapidload.io/',
      improvetips_count: 0,
      focus: null,
      test_mode: false,
      general_over: false,
      preview_over: false,
      licenseReqCount:0,
      license_information:
          {
            name: '',
            key:'',
            exp_date:new Date(),
            license:'',
            status: true,
            link: window.uucss_global?.app_url,
            licensed_domain: null,
            connect_link: window.uucss_global?.activation_url,
            activation_nonce: null,
          },
      items_data: [],
      loading: false,
      license_loading: false,
      tips: [
        {
          title : "Image compression",
          description: 'Lossless image compression will <b>reduce image size by 30%</b> causing this to have a 50% effect on your page speed score.',
          learn_more: 'https://docs.rapidload.io/features/image-delivery',
        },
        {
          title : "Unused JS",
          description: "Removing Unused JS can <b>improve the website's performance</b> by reducing the amount of code the browser has to parse and execute.",
          learn_more: 'https://docs.rapidload.io/features/javascript-delivery',
        },
        {
          title : "CDN",
          description: "Using a CDN on-site will <b>improve the reliability of resource delivery</b> by using multiple servers in different locations, especially for users who are physically far from the server.",
          learn_more: 'https://docs.rapidload.io/features/cloud-delivery',
        },
        {
          title : "Self-hosting Google fonts",
          description: "Google fonts may not work correctly when loaded from external servers. Self-hosting the fonts <b>helps avoid these compatibility issues</b>.",
          learn_more: 'https://docs.rapidload.io/features/font-delivery',
        },
        {
          title : "Unused CSS",
          description: "RapidLoad will cut unused CSS <b>up to 97% and increase the page speed</b> by 50% positively affecting your conversion rate.",
          learn_more: 'https://docs.rapidload.io/features/css-delivery',
        },
      ],
      items: [
        {
          id : "page-optimizer",
          title: "Titan Optimizer",
          description: 'Effortlessly optimize your site’s speed with on-the-spot actionable fixes',
          image: '<svg width="49" height="49" xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" viewBox="0 0 20.34 20.3"><defs><style>.cls-1{fill:#190028;}.cls-2{fill:#fff;}</style></defs><rect class="cls-1" x="0.25" y="0.27" width="19.75" height="19.75" rx="6.39"/><polygon class="cls-2" points="9.19 5.67 7.18 11.22 9.19 11.26 7.52 15.47 12.82 9.27 10.72 9.22 13.07 5.63 9.19 5.67"/></svg>',
          link: '/#',
          status: false,
          isDisabled: true
        },
        {
          id : "css",
          title: "CSS Delivery",
          description: 'Deliver CSS files by removing unused CSS and prioritizing critical CSS.',
          image: '<svg width="49" height="49" viewBox="0 0 49 49" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="49" height="49" rx="15" fill="#7F54B3"/> <path d="M16.4048 24.8452V23.5028C17.0298 23.5028 17.4631 23.3821 17.7045 23.1406C17.946 22.8956 18.0668 22.5121 18.0668 21.9901V21.1591C18.0668 20.392 18.1857 19.799 18.4237 19.38C18.6651 18.9574 18.9883 18.6555 19.3931 18.4744C19.8015 18.2933 20.2631 18.185 20.7781 18.1495C21.293 18.1104 21.8239 18.0909 22.3707 18.0909V20.2216C21.9446 20.2216 21.6286 20.2695 21.4226 20.3654C21.2166 20.4613 21.0817 20.6069 21.0178 20.8022C20.9538 20.9975 20.9219 21.2443 20.9219 21.5426V22.7145C20.9219 22.9808 20.8615 23.2418 20.7408 23.4975C20.6236 23.7496 20.4052 23.9769 20.0856 24.1793C19.766 24.3817 19.3097 24.5433 18.7166 24.6641C18.1236 24.7848 17.353 24.8452 16.4048 24.8452ZM22.3707 31.4716C21.8239 31.4716 21.293 31.4521 20.7781 31.413C20.2631 31.3775 19.8015 31.2692 19.3931 31.0881C18.9883 30.907 18.6651 30.6051 18.4237 30.1825C18.1857 29.7635 18.0668 29.1705 18.0668 28.4034V27.5724C18.0668 27.0504 17.946 26.6687 17.7045 26.4272C17.4631 26.1822 17.0298 26.0597 16.4048 26.0597V24.7173C17.353 24.7173 18.1236 24.7777 18.7166 24.8984C19.3097 25.0192 19.766 25.1808 20.0856 25.3832C20.4052 25.5856 20.6236 25.8146 20.7408 26.0703C20.8615 26.3224 20.9219 26.5817 20.9219 26.848V28.0199C20.9219 28.3146 20.9538 28.5614 21.0178 28.7603C21.0817 28.9556 21.2166 29.1012 21.4226 29.1971C21.6286 29.293 21.9446 29.3409 22.3707 29.3409V31.4716ZM16.4048 26.0597V23.5028H18.7486V26.0597H16.4048ZM31.795 24.7173V26.0597C31.1736 26.0597 30.7403 26.1822 30.4953 26.4272C30.2538 26.6687 30.1331 27.0504 30.1331 27.5724V28.4034C30.1331 29.1705 30.0123 29.7635 29.7709 30.1825C29.5329 30.6051 29.2098 30.907 28.8014 31.0881C28.3966 31.2692 27.9367 31.3775 27.4218 31.413C26.9104 31.4521 26.3795 31.4716 25.8291 31.4716V29.3409C26.2552 29.3409 26.5713 29.293 26.7773 29.1971C26.9832 29.1012 27.1182 28.9556 27.1821 28.7603C27.246 28.5614 27.278 28.3146 27.278 28.0199V26.848C27.278 26.5817 27.3366 26.3224 27.4537 26.0703C27.5745 25.8146 27.7947 25.5856 28.1143 25.3832C28.4339 25.1808 28.8902 25.0192 29.4832 24.8984C30.0763 24.7777 30.8469 24.7173 31.795 24.7173ZM25.8291 18.0909C26.3795 18.0909 26.9104 18.1104 27.4218 18.1495C27.9367 18.185 28.3966 18.2933 28.8014 18.4744C29.2098 18.6555 29.5329 18.9574 29.7709 19.38C30.0123 19.799 30.1331 20.392 30.1331 21.1591V21.9901C30.1331 22.5121 30.2538 22.8956 30.4953 23.1406C30.7403 23.3821 31.1736 23.5028 31.795 23.5028V24.8452C30.8469 24.8452 30.0763 24.7848 29.4832 24.6641C28.8902 24.5433 28.4339 24.3817 28.1143 24.1793C27.7947 23.9769 27.5745 23.7496 27.4537 23.4975C27.3366 23.2418 27.278 22.9808 27.278 22.7145V21.5426C27.278 21.2443 27.246 20.9975 27.1821 20.8022C27.1182 20.6069 26.9832 20.4613 26.7773 20.3654C26.5713 20.2695 26.2552 20.2216 25.8291 20.2216V18.0909ZM31.795 23.5028V26.0597H29.4513V23.5028H31.795Z" fill="white"/></svg>',
          link: '/css',
          status: false,
          isDisabled: false,
        },
        {
          id : "javascript",
          title: "Javascript Delivery",
          description: 'Minify and deliver Javascript files with best practices.',
          image: '<svg width="49" height="49" viewBox="0 0 49 49" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="49" height="49" rx="15" fill="#FDC20A"/><path d="M12.2358 26.8466V24.2898L20.1619 21.1151V23.7997L15.1122 25.5469L15.1974 25.419V25.7173L15.1122 25.5895L20.1619 27.3366V30.0213L12.2358 26.8466ZM27.4941 18.5795L23.9785 31.6406H21.4856L25.0012 18.5795H27.4941ZM36.744 26.8466L28.8178 30.0213V27.3366L33.8675 25.5895L33.7823 25.7173V25.419L33.8675 25.5469L28.8178 23.7997V21.1151L36.744 24.2898V26.8466Z" fill="white"/></svg>',
          link: '/javascript',
          status: false,
          isDisabled: false
        },
        {
          id : "image-delivery",
          title: "Image Delivery",
          description: 'Optimize all your images on-the-fly with modern formats (AVIF, WEBP).',
          image: '<svg width="49" height="49" viewBox="0 0 49 49" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="49" height="49" rx="15" fill="#0EBFE6"/><circle cx="21" cy="28" r="7.5" stroke="white" stroke-width="3"/><circle cx="33" cy="16" r="3.75" stroke="white" stroke-width="2.5"/></svg>',
          link: '/image',
          status: false,
          isDisabled: false
        },
        {
          id : "font",
          title: "Font Delivery",
          description: 'Optimize and host your fonts locally for faster page load times.',
          image: '<svg width="49" height="49" viewBox="0 0 49 49" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="49" height="49" rx="15" fill="#295ECF"/><path d="M15.4 32H18.32L19.36 26.66H23.7L24.16 24.24H19.84L20.38 21.46H25.26L25.74 19H17.94L15.4 32ZM25.6228 32.06C25.5028 32.64 25.3228 33.02 25.0428 33.22C24.7628 33.42 24.2628 33.5 23.5428 33.5L23.4628 35.94C24.9428 35.94 26.0628 35.68 26.8228 35.12C27.5628 34.56 28.0628 33.62 28.3428 32.26L29.8628 24.44H31.5628L32.1228 22.4H30.2628L30.5028 21.24C30.6228 20.56 31.0628 20.2 31.8228 20.2C32.3028 20.2 32.7228 20.28 33.0428 20.4L33.8828 18.24C33.2428 17.98 32.5028 17.84 31.6628 17.84C30.6828 17.84 29.8428 18.16 29.1228 18.76C28.4028 19.36 27.9428 20.14 27.7628 21.1L27.5028 22.4H25.9228L25.5228 24.44H27.1028L25.6228 32.06Z" fill="white"/></svg>',
          link: '/font',
          status: false,
          isDisabled: true
        },
        {
          id : "cdn",
          title: "Cloud Delivery (CDN)",
          description: 'Load resource files faster by using 112 edge locations with only 27ms latency.',
          image: '<svg width="49" height="49" viewBox="0 0 49 49" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="49" height="49" rx="15" fill="#09B42F"/><path d="M20 28V34H17.5M24 26V36.5M28 28V34C28 34 28.9379 34 30.5 34" stroke="white" stroke-width="1.5" stroke-linejoin="round"/><rect x="30" y="33" width="2" height="2" rx="0.5" stroke="white"/><rect x="16" y="33" width="2" height="2" rx="0.5" stroke="white"/><rect x="23" y="36" width="2" height="2" rx="0.5" stroke="white"/><path d="M32 29.6073C33.4937 29.0221 35 27.6889 35 25C35 21 31.6667 20 30 20C30 18 30 14 24 14C18 14 18 18 18 20C16.3333 20 13 21 13 25C13 27.6889 14.5063 29.0221 16 29.6073" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
          link: '/cdn',
          status: false,
          isDisabled: true
        },
        {
          id : "cache",
          title: "Page Cache",
          description: 'Optimize and cache static HTML pages to provide a snappier page experience.',
          image: '<svg width="49" height="49" viewBox="0 0 49 49" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="49" height="49" rx="15" fill="#FF7D00"/><path d="M27 24L22.1146 27.4804L24.0943 28.7715L22 32L26.8854 28.5691L24.9083 27.2825L27 24Z" fill="white"/><rect x="10.5" y="12.5" width="28" height="24" rx="4.5" stroke="white" stroke-width="3" stroke-linejoin="bevel"/><circle cx="16" cy="17" r="1" transform="rotate(-180 16 17)" fill="white"/><circle cx="19" cy="17" r="1" transform="rotate(-180 19 17)" fill="white"/><circle cx="22" cy="17" r="1" transform="rotate(-180 22 17)" fill="white"/><path d="M11 20.5H38.5" stroke="white" stroke-width="2"/></svg>',
          link: '/cache',
          status: false,
          isDisabled: true
        },


      ],
      base: config.is_plugin ? config.public_base + 'images/' : 'public/images/'
    }
  }

}
</script>