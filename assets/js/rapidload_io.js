(function ($) {

    $(document).ready(function (){

        function isHTML(str) {
            var a = document.createElement(str);
            for (var c = a.childNodes, i = c.length; i--; ) {
                if (c[i].nodeType == 1) return true;
            }
            return false;
        }

        window.rapidload_replace_image_src = function(){

            $('img').each(function (index, value){

                var url = $(value).data('original-src')

                if(window.rapidload_io_data && url){

                    var options = 'ret_img'

                    if(window.rapidload_io_data.optimize_level){
                        options += ",q_" + window.rapidload_io_data.optimize_level
                    }

                    if(window.rapidload_io_data.support_next_gen_format){
                        options += ",to_auto"
                    }

                    options += ',w_' + $(value).width()

                    if(!$(value).attr('src').toString().includes(',w_' + $(value).width())){
                        $(value).attr('src', window.rapidload_io_data.image_endpoint + options + '/' + url);
                    }

                }

            })

        }

        const targetNode = document.getElementsByTagName('body')[0];

        const config = { attributes: true, childList: true, subtree: true };

        const callback = (mutationList, observer) => {
            for (const mutation of mutationList) {
                if (mutation.type === 'childList') {
                    console.log(mutation.addedNodes)
                    for (const node of mutation.addedNodes){
                        console.log(node)
                        if(isHTML(node)){
                            try {
                                var imageTags = node.getElementsByTagName('img');
                                if(imageTags.length){
                                    for (const img of imageTags){
                                        var url = img.getAttribute('data-original-src')
                                        if(window.rapidload_io_data && url){
                                            var options = 'ret_img'
                                            if(window.rapidload_io_data.optimize_level){
                                                options += ",q_" + window.rapidload_io_data.optimize_level
                                            }
                                            if(window.rapidload_io_data.support_next_gen_format){
                                                options += ",to_auto"
                                            }
                                            options += ',w_' + img.getBoundingClientRect().width
                                            img.setAttribute('src', window.rapidload_io_data.image_endpoint + options + '/' + url);
                                        }
                                    }
                                }
                            }catch (e){

                            }
                        }
                    }
                }
            }
        };

        const observer = new MutationObserver(callback);

        observer.observe(targetNode, config);

        window.onresize = function(event) {
            window.rapidload_replace_image_src();
        };

        window.rapidload_replace_image_src();

    })

}(jQuery))