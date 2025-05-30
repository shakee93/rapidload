(function ($) {

    $.uucssAlert = function(message, type = 'success', duration = 3000) {
        new Noty({
            text: message,
            layout: 'bottomCenter',
            timeout: duration,
            type : type,
            animation: {
                open: 'animated bounceInUp',
                close: 'animated bounceOutDown'
            }
        }).show();
        //alert(message);
    }

    $.uucss_log = function (log) {

        if(!log){
            return;
        }

        $.ajax({
            url : uucss_global.ajax_url,
            method : 'POST',
            data : {
                action : 'frontend_logs',
                type : log.type,
                url : log.url,
                log : log.log,
                nonce : window.uucss_global.nonce
            },
            success : function(response){

            }
        })
    };

    $(document).ready(function () {

        if (window.location.href.includes('#configure_autoptimize_css')) {
            $(document).ready(function () {
                $(window).scrollTop($('#autoptimize_css').position().top - 50)
            })

        }

        /**
         * Safelist input in options
         *
         * @type {any}
         */

        window.safelist = JSON.parse($('#uucss_safelist').val() || '[]');
        window.blocklist = JSON.parse($('#uucss_blocklist').val() || '[]');
        drawSafeList();
        drawBlockList();

        $('#uucss-options .safelist-add button, #uucss-wrapper .safelist-add button').on('click', function (e) {
            e.preventDefault();
            addRule();
        });

        $('#uucss-options .safelist-add #safelist-add, #uucss-wrapper .safelist-add button').on('keydown', function (e) {
            if (e.key && e.key === 'Enter') {
                e.preventDefault();
                addRule();
            }
        });

        $('#uucss-options .blocklist-add button, #uucss-wrapper .blocklist-add button').on('click', function (e) {
            e.preventDefault();
            addBlockList();
        });

        $('#uucss-options .blocklist-add #blocklist-add, #uucss-wrapper .blocklist-add button').on('keydown', function (e) {
            if (e.key && e.key === 'Enter') {
                e.preventDefault();
                addBlockList();
            }
        });

        function addBlockList() {
            var item = $('#blocklist-add');

            var value = item.val().trim();

            if(value === ''){
                return;
            }

            var exists = window.blocklist.findIndex(function (p) {
                return p === value
            });

            if (exists >= 0) {
                return;
            }

            blocklist.push(value);

            item.val('')

            drawBlockList();
        }

        function addRule() {

            var type = $('#safelist-type');
            var item = $('#safelist-add');

            var pattern = {
                type: type.val(),
                rule: item.val().trim()
            }

            if (!pattern.rule) {
                return;
            }

            var exists = window.safelist.findIndex(function (p) {
                return p.rule === pattern.rule && p.type === pattern.type
            });

            if (exists >= 0) {
                return;
            }

            safelist.push(pattern);

            item.val('')
            type.val('greedy')

            drawSafeList();

        }

        function updateInput() {
            $('#uucss_safelist').val(JSON.stringify(window.safelist))
        }

        function updateBlockListInput() {
            $('#uucss_blocklist').val(JSON.stringify(window.blocklist))
        }

        function drawBlockList() {

            $('.blocklist-list ul').empty();

            window.blocklist.forEach(function (item) {

                var li = $(`<li><span data-rule="` + item + `" title="remove rule" class="dashicons dashicons-remove dashicons-no-alt"></span> <span class="blocklist-list-value"> ` + item + `</span></li>`)

                li.find('.dashicons-remove').click(function () {
                    var _item = $(this).data('rule')

                    window.blocklist = window.blocklist.filter(function (i) {
                        return !(i === _item)
                    });

                    drawBlockList();
                });

                $('.blocklist-list ul').append(li)

            });

            updateBlockListInput();
        }

        function drawSafeList() {

            $('.safelist-list ul').empty();

            window.safelist.forEach(function (item) {

                var li = $(`<li><span data-rule="` + item.rule + `" data-type="` + item.type + `" title="remove rule" class="dashicons dashicons-remove dashicons-no-alt"></span> <span class="safelist-list-type"> ` + item.type + `</span> <span>` + item.rule + `</span></li>`)

                li.find('.dashicons-remove').click(function () {
                    var _item = $(this).data()

                    window.safelist = window.safelist.filter(function (i) {
                        return !(i.rule === _item.rule && i.type === _item.type)
                    });

                    drawSafeList();
                });

                $('.safelist-list ul').append(li)

            });

            updateInput();
        }

        $('.notice-action-rapidload-db-update .notice-main-action a.button').click(function (e) {
            var $target = $(this);

            if($target.text() === 'Contact Support'){
                return;
            }
            e.preventDefault();

            function displayError(){
                var $error = $('.notice-action-rapidload-db-update .notice-icon-content');
                $error.find('h2').text('RapidLoad - Data update failed !');
                $error.find('p').text('Database update failed');
                $('.notice-action-rapidload-db-update').css('border-left-color','#dc3232');
                $target.text('Failed : Contact Support');
                $target.attr('href', 'https://rapidload.zendesk.com/hc/en-us/requests/new');
                $target.attr('target', '_blank');
            }

            $target.text('Updating...');
            $target.removeAttr('href');
            $.ajax({
                url : uucss_global.ajax_url,
                method : 'POST',
                data : {
                    action : 'rapidload_db_update',
                    nonce : window.uucss_global.nonce
                },
                success : function(response){
                    if(response.success){
                        window.location.reload();
                    }else{
                        displayError();
                    }
                },
                error: function(xhr, status, error) {
                    displayError();
                }
            })
        })

        $('.rapidload-clear-all').click(function (e) {
            e.preventDefault();

            var href = $(this).find('a').attr('href');

            var urlParams = new URLSearchParams(href.split('?')[1]);

            var params = {};

            urlParams.forEach((value, key) => {
                params[key] = value;
            });

            wp.ajax.post('rapidload_purge_all', params).done(function (d) {
                window.location.reload();
            })

        });

    });

}(jQuery))