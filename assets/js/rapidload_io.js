(function ($) {

    $(document).ready(function (){

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

                    $(value).attr('src', window.rapidload_io_data.image_endpoint + options + '/' + url);
                }

            })

        }

        window.rapidload_replace_image_src();

    })

}(jQuery))