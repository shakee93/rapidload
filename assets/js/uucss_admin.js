(function ($) {

    function showNotification(heading, message, type = 'info', enableClose = false, notice_id = false) {
        var container = $('#uucss-wrapper')
        var content = $($('.uucss-info-wrapper.safelist-settings')[0]).clone().css('max-width', '100%');

        if(!content.length){
            return;
        }

        if(enableClose){
            content.find('h4').html(heading + '<span data-notice_id="' + notice_id + '" class="dashicons dashicons-no-alt notice-close '+ type +'"></span>');
        }else{
            content.find('h4').text(heading);
        }

        content.find('p').remove();
        content.find('.info-details').append('<p class="divider"></p>').append('<p>' + message + '</p>');

        container.prepend('<li class="uucss-notification uucss-notification-'+ type +' uucss-info-wrapper"><div class="content">'+ content.html() +'</div></li>').parent().show()
    }

    function showFaqs() {

        if (window.uucss && window.uucss.faqs.length) {

            var container = $('#uucss-wrapper')
            var content = $($('.uucss-info-wrapper.safelist-settings')[0]).clone().css('max-width', '100%');

            if(!content.length){
                return;
            }

            content.prepend('<h3>Frequently Asked Questions<a id="uucss-faq-read" href="#">close</a></h3>');
            content.find('h4').text(window.uucss.faqs[0].title);
            content.find('h4').attr('data-index',0);
            content.find('.info-icon').remove();
            content.find('p').remove();
            content.find('.info-details').append('<p class="divider"></p>').append('<p class="answer">' + window.uucss.faqs[0].message + '</p>');

            container.prepend('<li class="uucss-notification uucss-notification-faq uucss-info-wrapper"><span class="dashicons dashicons-arrow-left-alt2 prev-faq nav-faq"></span><span class="dashicons dashicons-arrow-right-alt2 next-faq nav-faq"></span><div class="content">'+ content.html() +'</div></li>').parent().show()

            container.find('.uucss-notification-faq .dashicons.nav-faq').click(function () {
                var $this = $(this)
                var $heading = $this.parent().find('h4')
                var $answer = $this.parent().find('p.answer')
                var faq_index = $heading.data('index');
                if($this.hasClass('prev-faq')){
                    if(faq_index === 0){
                        faq_index = window.uucss.faqs.length;
                    }
                    $heading.text(window.uucss.faqs[faq_index-1].title);
                    $answer.html(window.uucss.faqs[faq_index-1].message);
                    $heading.data('index',faq_index-1);
                }else{
                    if(faq_index === window.uucss.faqs.length - 1){
                        faq_index = -1;
                    }
                    $heading.text(window.uucss.faqs[faq_index+1].title);
                    $answer.html(window.uucss.faqs[faq_index+1].message);
                    $heading.data('index',faq_index+1);
                }
            })

            container.find('#uucss-faq-read').click(function (e) {
                e.preventDefault();
                wp.ajax.post('mark_faqs_read',{ nonce : window.uucss.nonce}).then(function (i) {
                    container.find('.uucss-notification.uucss-notification-faq').remove();
                }).fail(function (i) {

                });
            })
        }

    }

    function showPublicNotices() {
        if (window.uucss && window.uucss.public_notices.length) {
            window.uucss.public_notices.forEach(function(value){
                if(window.uucss && window.uucss.cpcss_enabled == "1" && value.id === 3){ // skip public notice if critical css enabled
                    return;
                }
                showNotification(value.title, value.message, value.type + ' public-notice public-notice-' + value.id, true, value.id);
            })
        }

        var container = $('#uucss-wrapper');

        container.find('span.public-notice').click(function (e) {
            e.preventDefault();
            var $this = $(this);
            wp.ajax.post('mark_notice_read',{
                notice_id : $this.data('notice_id'),
                nonce : window.uucss.nonce
            }).then(function (i) {
                $this.parents('li:first').remove();
            }).fail(function (i) {

            });
        })
    }

    function hideNotification() {
        var container = $('.uucss-notification');
        container.hide()
    }

    function trailingslashit(str){
        return str.replace(/\/$/, '') + "/";
    }

    function updateSitemapUrl(){

        var $sitemap_input = $('input.site-map-url');

        wp.ajax.post('get_robots_text', { nonce : window.uucss.nonce }).done(function (data){
            if(data && data.sitemap){
                $sitemap_input.data('sitemap_url', data.sitemap);
            }
        })
    }

    $(document).ready(function () {

        $.fn.dataTable.ext.errMode = 'none';
        
        if (window.uucss && window.uucss.notifications.length) {
            window.uucss.notifications.forEach(function (i) {

                showNotification(i.title, i.message, i.type);

            })
        }

        // options page
        window.tagBox.init();

        var $input = $('#uucss_api_key')
        var $uucss_spinner = $('.uucss-history.uucss-job-history .spinner-history')
        var $uucss_rule_spinner = $('.uucss-history.uucss-rule-history .spinner-history')

        try {
            var _url = new URL(window.location.href);

            if (_url.search.includes('token')) {
                _url.searchParams.delete('token');
                _url.searchParams.delete('nonce');
                history.pushState('', document.title, _url);
            }

            if(_url.search.includes('deactivated')){
                _url.searchParams.delete('deactivated');
                history.pushState('', document.title, _url);
            }

        } catch (e) {

        }

        var whitelist_pack_el = $('#whitelist_packs');
        whitelist_pack_el.select2({
            ajax: {
                url: window.uucss.api + '/whitelist-packs',
                data: function (params) {
                    return {
                        s: params.term,
                        url: window.location.origin
                    };
                },
                headers: {
                    "Authorization": "Bearer " + $input.val(),
                    "Content-Type": "application/json",
                },
                delay: 150,
                cache: true,
                processResults: function (data) {

                    let d = data.data.map(function (item) {

                        return {
                            id: item.id + ":" + item.name,
                            text: item.name
                        }

                    })
                    return {
                        results: d,
                        pagination: {
                            more: false
                        }
                    };
                }
            },
            maximumSelectionLength: 5,
            width: '100%',
        })

        $('#uucss-pack-suggest').click(function () {

            var $button = $(this)
            var oldText = $button.val()

            $button.val('loading..')
            wp.ajax.post('suggest_whitelist_packs', { nonce : window.uucss.nonce }).done(function (data) {

                $button.val(oldText)

                if (!data) {
                    return;
                }

                data.forEach(function (item) {

                    if ($("li[title='" + item.name + "']").length === 0) {
                        var newOption = new Option(item.name, item.id + ':' + item.name, true, true);
                        whitelist_pack_el.append(newOption).trigger('change');
                    }

                });

            }).fail(function () {
                $button.val('Load Recommended Packs');
                $('#uucss-pack-suggest-error').text('error : something went wrong, please contact support')

            });

        });

        /*$.each($('span.dashicons.has-tooltip'),function(index, value){
            tippy($(value)[0], {
                content: $($(value)[0]).data('message'),
                allowHTML: true,
                placement: 'auto',
                animation: null,
                theme: 'light',
                interactive: true,
                delay: 0,
                arrow: false,
                offset: [0, 7]
            });
        });*/

        if(window.uucss.rules_enabled === ""){
            $('#uucss-wrapper li:not(:nth-child(2)) h2').click(function () {
                $(this).parent().find('.content').slideToggle('fast');
                $(this).find('.uucss-toggle-section').toggleClass('rotate')
            });
        }else{
            $('#uucss-wrapper li:not(:nth-child(2),:nth-child(3)) h2').click(function () {
                $(this).parent().find('.content').slideToggle('fast');
                $(this).find('.uucss-toggle-section').toggleClass('rotate')
            });
        }



        var table = $('#uucss-history')
        var rule_table = $('#uucss-rule-history')

        table.on('init.dt', function () {
            setInterval(refreshTable, 1000 * 5)
        });

        rule_table.on('init.dt', function () {
            setInterval(refreshRulesTable, 1000 * 5)
        });

        var x = 0;

        table.on('error.dt', function(e, settings, techNote, message){
            $.uucss_log({
                log : message,
            })
        });

        rule_table.on('error.dt', function(e, settings, techNote, message){
            $.uucss_log({
                log : message,
            })
        });

        table.on('draw.dt', function (x,y) {

            var element = '<div id="uucss-auto-refresh" class="uucss-auto-refresh">' +
                '<input type="checkbox" id="uucss_auto_refresh_frontend" name="autoptimize_uucss_settings[uucss_auto_refresh_frontend]" value="1">' +
                '<label for="uucss_auto_refresh_frontend"> Auto Refresh</label><br>' +
                '<div>';

            $('#uucss-history_info').append(element);
            $('#uucss_auto_refresh_frontend').change(function () {
                $('#uucss_auto_refresh_frontend-hidden').val($(this).is(':checked') ? 1 : 0);
                auto_refresh = $(this).is(':checked');
            });
            $('#uucss_auto_refresh_frontend').prop('checked', auto_refresh);

            var lengthChange = '<div class="dataTables_length" id="uucss-history_length"><label>Show ' +
                '<select name="uucss-history_length" aria-controls="uucss-history" class="">' +
                '<option value="10" selected>10</option>' +
                '<option value="25">25</option>' +
                '<option value="50">50</option>' +
                '<option value="100">100</option>' +
                '</select></label></div>';

            $(lengthChange).prependTo($('#uucss-history_info'));

            var rule_based_status = '';

            if(window.uucss.rules_enabled === "1"){
                rule_based_status = '<option value="rule-based" ' + (status_filter === 'rule-based'? 'selected' : '') +'>Rule Based</option>'
            }

            var select = $('<select class="status">' +
                    '<option value="" ' + (status_filter === ''? 'selected' : '') +'>All</option>' +
                    '<option value="queued" ' + (status_filter === 'queued'? 'selected' : '') +'>Queued</option>' +
                    '<option value="waiting" ' + (status_filter === 'waiting'? 'selected' : '') +'>Waiting</option>' +
                    '<option value="processing" ' + (status_filter === 'processing'? 'selected' : '') +'>Processing</option>' +
                    '<option value="success" ' + (status_filter === 'success'? 'selected' : '') +'>Success</option>' +
                    '<option value="warning" ' + (status_filter === 'warning'? 'selected' : '') +'>Warning</option>' +
                    '<option value="failed" ' + (status_filter === 'failed'? 'selected' : '') +'>Failed</option>' +
                    rule_based_status +
                '</select>');

            var input = '<div class="uucss-url-search-wrap url-history"><input type="search" placeholder="Search" value="'+ url_filter +'"><input class="uucss_search_exact" type="checkbox" id="uucss_search_exact" value="1"></div>';
            $(input).prependTo($('#uucss-history_info'));

            $(select).prependTo($('#uucss-history_info'));

            $('#uucss-history_info select.status').on('change', function(){
                status_filter = $(this).val();
                table.column(4).search( status_filter ? '^'+ status_filter +'$' : '', true, false )
                    .draw();
            });

            tippy($('.uucss_search_exact')[0], {
                content: 'Exact Match',
                placement: 'top',
                appendTo: 'parent',
            });

            var $url_input = $('#uucss-history_info .uucss-url-search-wrap.url-history input[type="search"]')
            var $exact_search = $('#uucss-history_info .uucss-url-search-wrap.url-history input.uucss_search_exact')

            $url_input.on('input',function () {
                url_filter = $(this).val();

                var regex = url_filter;

                if(exact_search_val){
                    regex = '^' + url_filter + '$';
                }

                table.column(1).search( url_filter ? regex : '', true, false )
                    .draw();
            });

            $exact_search.on('change',function () {
                exact_search_val = $(this).prop('checked');
            });

            if(url_filter !== ''){
                $url_input.focus().val('').val(url_filter);
            }

            $exact_search.prop('checked', exact_search_val);

            $('#uucss-history tbody tr').off();
            $('#uucss-history tbody tr').click(function () {
                $(this).toggleClass('selected');
                var $table_row = $('#uucss-history tbody tr.selected');
                var $container = $('#uucss-wrapper li.uucss-history.uucss-job-history');
                $('#uucss-wrapper li.uucss-history.uucss-job-history .multiple-selected-text .multiple-selected-value').text('(' + $table_row.length + ') URLs');
                if($table_row.length > 1){
                    !$container.hasClass('multi-select') && $container.addClass('multi-select')
                }else{
                    $container.hasClass('multi-select') && $container.removeClass('multi-select')
                }
            });

            $('#uucss-history_length select').change(function(){
                page_length = $(this).val()
                if(!page_length){
                    return;
                }
                $uucss_spinner.addClass('loading');
                table.page.len(page_length)
                table.ajax.reload(null, false);
            })

            if(Number(page_length) !== 10){
                $('#uucss-history_length select').val(page_length)
            }
        });

        rule_table.on('draw.dt', function (x,y) {

            var element = '<div id="uucss-auto-refresh-rule" class="uucss-auto-refresh">' +
                '<input type="checkbox" id="uucss_auto_refresh_frontend_rule" name="autoptimize_uucss_settings[uucss_auto_refresh_frontend_rule]" value="1">' +
                '<label for="uucss_auto_refresh_frontend_rule"> Auto Refresh</label><br>' +
                '<div>';

            $('#uucss-rule-history_wrapper .dataTables_info').append(element);
            $('#uucss_auto_refresh_frontend_rule').change(function () {
                $('#uucss_auto_refresh_frontend-hidden_rule').val($(this).is(':checked') ? 1 : 0);
                auto_refresh_rule = $(this).is(':checked');
            });
            $('#uucss_auto_refresh_frontend_rule').prop('checked', auto_refresh_rule);

            var lengthChange = '<div class="dataTables_length" id="uucss-rule-history_length"><label>Show ' +
                '<select name="uucss-rule-history_length" aria-controls="uucss-rule-history" class="">' +
                '<option value="15" selected>15</option>' +
                '<option value="25">25</option>' +
                '<option value="50">50</option>' +
                '<option value="100">100</option>' +
                '</select></label></div>';

            $(lengthChange).prependTo($('#uucss-rule-history_info'));

            var select = $('<select class="status">' +
                '<option value="" ' + (status_filter_rule === ''? 'selected' : '') +'>All</option>' +
                '<option value="queued" ' + (status_filter_rule === 'queued'? 'selected' : '') +'>Queued</option>' +
                '<option value="waiting" ' + (status_filter_rule === 'waiting'? 'selected' : '') +'>Waiting</option>' +
                '<option value="processing" ' + (status_filter_rule === 'processing'? 'selected' : '') +'>Processing</option>' +
                '<option value="success" ' + (status_filter_rule === 'success'? 'selected' : '') +'>Success</option>' +
                '<option value="warning" ' + (status_filter_rule === 'warning'? 'selected' : '') +'>Warning</option>' +
                '<option value="failed" ' + (status_filter_rule === 'failed'? 'selected' : '') +'>Failed</option>' +
                '</select>');

            var input = '<div class="uucss-url-search-wrap rule-history"><input type="search" placeholder="Search" value="'+ url_filter +'"><input class="uucss_search_exact" type="checkbox" id="uucss_search_exact_rule" value="1"></div>';
            $(input).prependTo($('#uucss-rule-history_info'));

            $(select).prependTo($('#uucss-rule-history_info'));

            $('#uucss-rule-history_info select.status').on('change', function(){
                status_filter_rule = $(this).val();
                rule_table.column(4).search( status_filter_rule ? '^'+ status_filter_rule +'$' : '', true, false )
                    .draw();
            });

            /*tippy($('.uucss_search_exact')[0], {
                content: 'Exact Match',
                placement: 'top',
                appendTo: 'parent',
            });*/

            var $input = $('#uucss-rule-history_info .uucss-url-search-wrap.rule-history input[type="search"]')
            var $exact_search = $('#uucss-rule-history_info .uucss-url-search-wrap.rule-history input.uucss_search_exact')

            $input.on('input',function () {
                url_filter_rule = $(this).val();

                var regex = url_filter_rule;

                if(exact_search_val_rule){
                    regex = '^' + url_filter_rule + '$';
                }

                rule_table.column(1).search( url_filter_rule ? regex : '', true, false )
                    .draw();
            });

            $exact_search.on('change',function () {
                exact_search_val_rule = $(this).prop('checked');
            });

            if(url_filter_rule !== ''){
                $input.focus().val('').val(url_filter_rule);
            }

            $exact_search.prop('checked', exact_search_val_rule);

            $('#uucss-rule-history tbody tr').off();
            $('#uucss-rule-history tbody tr').click(function () {
                $(this).toggleClass('selected');
                var $table_row = $('#uucss-rule-history tbody tr.selected');
                var $container = $('#uucss-wrapper li.uucss-history.uucss-rule-history');
                $('#uucss-wrapper li.uucss-history.uucss-rule-history .multiple-selected-text .multiple-selected-value').text('(' + $table_row.length + ') Rules');
                if($table_row.length > 1){
                    !$container.hasClass('multi-select') && $container.addClass('multi-select')
                }else{
                    $container.hasClass('multi-select') && $container.removeClass('multi-select')
                }
            });

            $('#uucss-rule-history_length select').change(function(){
                page_length_rule = $(this).val()
                if(!page_length_rule){
                    return;
                }
                $uucss_rule_spinner.addClass('loading');
                rule_table.page.len(page_length_rule)
                rule_table.ajax.reload(null, false);
            })

            if(Number(page_length_rule) !== 10){
                $('#uucss-rule-history_length select').val(page_length_rule)
            }

            $('#uucss-rule-history thead tr th.applied-links').click(function (){
                rule_table.columns([3]).visible(true);
            })

            $('#uucss-rule-history thead tr th.hits-count').click(function (){
                rule_table.columns([3]).visible(false);
            })
        });

        var auto_refresh = $('#uucss_auto_refresh_frontend-hidden').val() == '0';
        var auto_refresh_rule = $('#uucss_auto_refresh_frontend-hidden_rule').val() == '0';
        var firstReload = true;
        var firstRuleReload = true;

        var status_filter = '';
        var status_filter_rule = '';
        var url_filter = '';
        var url_filter_rule = '';
        var page_length = '10';
        var page_length_rule = '10';
        var exact_search_val = false;
        var exact_search_val_rule = false;

        $uucss_spinner.addClass('loading')
        table = table.DataTable({
            serverSide: true,
            processing: false,
            language: {
                processing: '<span class="spinner loading"></span>'
            },
            ajax: {
                beforeSend() {
                    !$uucss_spinner.hasClass('loading') && $uucss_spinner.addClass('loading');
                },
                url: wp.ajax.settings.url + '?action=uucss_data',
                type : 'POST',
                data: function (d) {

                    if(status_filter !== "" && status_filter !== undefined){
                        if(d.columns[0] && d.columns[0].search){
                            d.columns[0].search.value = status_filter
                        }
                    }

                    if(url_filter !== "" && url_filter !== undefined){
                        if(d.columns[1] && d.columns[1].search){
                            d.columns[1].search.value = url_filter;
                            d.columns[1].search.regex = exact_search_val
                        }
                    }

                    d.nonce = uucss.nonce

                    return d;
                },
                dataSrc: function (d) {
                    $uucss_spinner.removeClass('loading')

                    if (!d.success) {
                        $.uucssAlert("Failed to fetch optimizations", 'error')
                        return [];
                    }

                    var results = d.data;

                    firstReload = false;
                    return results;
                }
            },
            searching: true,
            pagingType: "simple",
            tfoot: false,
            lengthChange : false,
            bSort: false,
            columns: [
                {
                    "data": "status",
                    title: "Status",
                    width: '50px',
                    className: 'dt-body-center dt-head-center',
                    render: function (data, type, row, meta) {
                        if(!data && row.cpcss){
                            data = row.cpcss.status
                        }
                        var classNames = 'status ';
                        if(data === 'queued' || data === 'processing'){
                            classNames += 'refresh ';
                        }
                        classNames += data + ' ';
                        return '<span class="job-status ' + classNames +'">'+ data +'</span>'
                    },
                },
                {
                    "data": "url",
                    title: "URL",
                    className: "url",
                    render(data) {

                        if (!data) {
                            return '';
                        }

                        return '<a href="'+ decodeURI(data) +'" target="_blank">'+ decodeURI(data) +'</a>';
                    }
                },
                {
                    "data": "rule",
                    title: "Rule",
                    width: '100px',
                    visible : window.uucss.rules_enabled === "1",
                    className: 'dt-body-center dt-head-center',
                    render: function (data, type, row, meta) {
                        return '<span class="">'+ (data ? data.replace('is_','') : '') +'</span>';
                    },
                },
                {
                    data: "url",
                    className: 'dt-body-center dt-head-center stats th-reduction',
                    title: "File Size Reduction",
                    width: '145px',
                    render: function (data, type, row, meta) {
                        if ((row.meta && row.meta.stats) && (row.status === 'success' || row.rule_status === 'success')) {
                            return row.meta.stats.reduction + '%'
                        }else if(row.status === 'queued' || row.status === 'processing' || row.status === 'waiting' || row.status === 'rule-based'){
                            return '<span class="job-file-size">-</span>';
                        }

                        return '-'
                    },
                    createdCell: function (td, cellData, rowData) {

                        var innerTippy
                        var innerTippy2

                        var stat = $(td).wrapInner($('<span></span>'));

                        var $warnings_html = $('<div class="uucss-warnings"></div>');

                        var $cpcss_html = $('<div class="cpcss-status cpcss-status-' + (rowData.cpcss ? rowData.cpcss.status : '') + '"></div>');

                        if(rowData.cpcss){
                            if(rowData.cpcss.status === 'success'){
                                $cpcss_html.append('<span class="dashicons dashicons-yes-alt" style="color : #009688; width: 16px; height: 16px"></span>');
                                $cpcss_html.append('<span style="font-size: 12px; margin-left:2px">Critical css generated ('+ rowData.cpcss.hits + '/' + rowData.cpcss.attempts +')</span>');
                            }
                        }

                        if(!window.uucss || !window.uucss.uucss_enable_debug){
                            if(rowData.meta?.warnings?.length){
                                rowData.meta.warnings = rowData.meta.warnings.filter(function(w){
                                    return !w.message.toString().includes('optimized version for the file missing')
                                })
                            }
                        }

                        var warnings = []

                        if(rowData.meta && rowData.meta.warnings && rowData.meta.warnings.length){

                            if(rowData.status === 'rule-based'){

                                warnings = rowData.meta.warnings.filter((war)=>{
                                    return war.id == rowData.job_id;
                                })

                            }else{
                                warnings = rowData.meta.warnings
                            }

                        }

                        if(warnings.length){
                            var scrollable = warnings.length > 2 ? 'scrollable' : '';
                            $warnings_html.append('<h5 class="warnings-title ">Warnings (' + warnings.length  + ')</h5>');
                            $warnings_html.append('<ul class="warning-list ' + scrollable  + '"></ul>');
                            $.each(warnings, function(index, value){
                                var $warning_html = $('<li class="warning"></li>')
                                $warning_html.append('<div class="warning-info"></div>');
                                $warning_html.find('.warning-info').append('<p class="warning-header">' +  value.message + '</p>');
                                if(value.file){
                                    $warning_html.find('.warning-info').append('<p class="warning-content"><a href="' + value.file +'" target="_blank">' +  value.file + '</a></p>');
                                }
                                $warnings_html.find('.warning-list').append($warning_html.wrap('<div></div>').parent().html())
                            })
                        }
                        else{
                            $warnings_html.removeClass('uucss-warnings');
                        }

                        var attemptsString = "";

                        if((rowData.status === 'success' && rowData.success_count > 0 || rowData.rule_status === 'success' && rowData.success_count > 0 && rowData.rule_hits > 0)){
                            attemptsString = 'Hits : ' + rowData.success_count + '/' + rowData.attempts
                        }else if(rowData.meta && rowData.meta.stats && rowData.meta.stats.success_count > 0){
                            attemptsString = 'Hits : ' + rowData.meta.stats.success_count + '/' + rowData.attempts
                        }else if(rowData.cpcss){
                            if(rowData.cpcss.status === 'success' && rowData.cpcss.hits > 0){
                                attemptsString = 'Hits : ' + rowData.cpcss.hits + '/' + rowData.cpcss.attempts
                            }else if(Number(rowData.attempts) !== 0) {
                                attemptsString = 'Attempts : ' + rowData.attempts
                            }
                        }else if(Number(rowData.attempts) !== 0) {
                            attemptsString = 'Attempts : ' + rowData.attempts
                        }

                        var tippyOptions;

                        tippyOptions = {
                            theme: 'light',
                            triggerTarget: stat.find('span')[0],
                            content: function () {
                                var c = $('<div class="stat-tooltip">' +
                                    '       <div class="progress-bar-wrapper">' +
                                    '           <div class="progress-bar w-100">' +
                                    '               <span style="width:' + (100 - (rowData.meta && rowData.meta.stats ? rowData.meta.stats.reduction : 100)) + '%">' + (100 - (rowData.meta && rowData.meta.stats ? rowData.meta.stats.reduction : 100)).toFixed() + '%' +
                                    '               </span>' +
                                    '           </div>' +
                                    '       </div>' +
                                    $cpcss_html.wrap('<div class="cpcss-result"></div>').parent().html() +
                                    $warnings_html.wrap('<div></div>').parent().html() +
                                    '<div class="time">' +
                                    '   <p class="val uucss-show-job-details">Created at ' +
                                    new Date(rowData.time * 1000).toLocaleDateString() + ' ' + new Date(rowData.time * 1000).toLocaleTimeString() +
                                    '   </p>' +
                                    '   <p class="attempts">' +
                                    attemptsString +
                                    '   </p>' +
                                    '</div>' +
                                    '</div>');

                                innerTippy = tippy(c.find('.progress-bar-wrapper')[0], {
                                    content: 'Without RapidLoad <span class="perc">' + (rowData.meta && rowData.meta.stats ? rowData.meta.stats.before : 0) + '</span>',
                                    allowHTML: true,
                                    placement: 'bottom-end',
                                    trigger: 'manual',
                                    hideOnClick: false,
                                    animation: null,
                                    theme: 'tomato',
                                    interactive: true,
                                    delay: 0,
                                    offset: [0, 7],
                                    inlinePositioning: true,
                                })

                                innerTippy2 = tippy(c.find('.progress-bar')[0], {
                                    content: 'RapidLoad <span class="perc"> ' + (rowData.meta && rowData.meta.stats ? rowData.meta.stats.after : 0) + '</span>',
                                    allowHTML: true,
                                    placement: 'top-start',
                                    trigger: 'manual',
                                    hideOnClick: false,
                                    animation: null,
                                    theme: 'ketchup',
                                    interactive: true,
                                    delay: 0,
                                    inlinePositioning: true,
                                })

                                return c[0]
                            },
                            placement: 'left',
                            //trigger: 'click',
                            interactive: true,
                            allowHTML: true,
                            animation: "shift-toward-extreme",
                            appendTo: "parent",
                            onShow: function () {
                                innerTippy.show()
                                innerTippy2.show()
                            },
                            onShown: function (instance) {
                                $(instance.popper).find('.progress-bar.w-100').removeClass('w-100')
                                $('.uucss-show-job-details')
                                    .featherlight('<div><div class="code"><pre><code>'+ JSON.stringify(rowData, undefined, 2) +'</code></pre></div></div>',{
                                        variant : 'uucss-job-details'
                                    })
                            },
                            onHide: function (instance) {
                                innerTippy.hide()
                                innerTippy2.hide()
                                $(instance.popper).find('.progress-bar').addClass('w-100')
                            }

                        }

                        if (rowData.status === 'failed') {
                            stat.find('span').append('<span class="dashicons dashicons-info error"></span>');

                            tippyOptions.onShow = function () {
                            }
                            tippyOptions.onHide = function () {
                            }

                            var code = (rowData.meta && rowData.meta.error && rowData.meta.error.code) ? rowData.meta.error.code : 500;
                            var message = (rowData.meta && rowData.meta.error && rowData.meta.error.message) ? rowData.meta.error.message : 'Unknown Error Occurred';
                            tippyOptions.content = '<div class="error-tooltip"><h5>Error</h5> <span><strong>CODE :</strong> ' + code + '</span> <br><span>' + message + '</span></div>';

                            //tippyOptions.triggerTarget = $(td).closest('tr')[0]
                            tippy(stat.find('span')[0], tippyOptions);
                            return
                        }

                        if ((rowData.meta && rowData.meta.stats && (rowData.status === 'success' || rowData.rule_status === 'success')) && (!warnings || !warnings.length)) {
                            var hits = rowData.meta && rowData.meta.stats && rowData.meta.stats.success_count > 0 || (rowData.status === 'success' && rowData.success_count > 0 || rowData.status === 'rule-based' && rowData.success_count > 0 && rowData.success_count > 0) ? 'hits-success' : '';
                            stat.find('span').append('<span class="dashicons dashicons-yes-alt '+ hits +'"></span>');
                            tippy(stat.find('span')[0], tippyOptions);
                        } else if ((rowData.status === 'success' || rowData.rule_status === 'success') && warnings.length) {
                            stat.find('span').append('<span class="dashicons dashicons-warning"></span>');
                            tippy(stat.find('span')[0], tippyOptions);
                        } else if(rowData.status === 'failed'){
                            stat.find('span').append('<span class="dashicons dashicons-info error"></span>');
                            tippy(stat.find('span')[0], tippyOptions);
                        } else if (rowData.cpcss){
                            if(rowData.cpcss.status === 'success'){
                                stat.find('span').append('<span class="dashicons dashicons-yes-alt"></span>');
                                tippy(stat.find('span')[0], tippyOptions);
                            }else if(rowData.cpcss.status === 'failed'){
                                stat.find('span').append('<span class="dashicons dashicons-info error"></span>');
                                tippy(stat.find('span')[0], tippyOptions);
                            }
                        }

                    }
                },
                {
                    "data": "url",
                    className: 'dt-body-right dt-head-right action th-actions',
                    "targets": 0,
                    title: "Actions",
                    width: '60px',
                    render: function (data, type, row, meta) {
                        var _render = '';

                        _render += '<button data-uucss-optimize data-url="' + data + '" data-rule="' + row.rule + '" data-rule_id="' + row.rule_id + '" data-regex="' + row.regex + '"><span class="dashicons dashicons-update"></span></button>'

                        _render += '<button data-uucss-options data-url="' + data + '" data-rule="' + row.rule + '" data-rule_id="' + row.rule_id + '" data-regex="' + row.regex + '" ><span class="dashicons dashicons-ellipsis"></span></button>';

                        return _render;
                    },
                },
                {
                    "data": "meta",
                    visible : false,
                    render: function (data, type, row, meta) {
                        if (data && data.warnings && data.warnings.length > 0) return 'warning';
                        if(data) return data.status;
                        if(row.cpcss) return row.cpcss.status
                    }
                }
            ],
            rowCallback: function (row, data, displayNum, displayIndex, dataIndex) {

                tippy($(row).find('button[data-uucss-options]')[0], {
                    allowHTML: true,
                    trigger: 'click',
                    arrow: true,
                    appendTo: $(row).find('button[data-uucss-options]')[0],
                    interactive: true,
                    animation: 'shift-toward',
                    hideOnClick: true,
                    theme: 'light',
                    content: ()=>{

                        var $content = $('<div class="uucss-option-list"><ul class="option-list"></ul></div>')

                        if((data.status === 'success' || data.status === 'rule-based') && data.meta && data.meta.stats){
                            $content.find('ul').append('<li data-action_name="test"><a data-action_name="test" href="#">GPSI Status</a></li>')
                        }

                        if(data.status !== 'queued' && data.status !== 'rule-based'){
                            $content.find('ul').append('<li data-action_name="requeue_url"><a data-action_name="requeue_url" href="#">Requeue</a></li>')

                            if(window.uucss && window.uucss.cpcss_enabled === "1"){
                                $content.find('ul').append('<li data-action_name="regenerate_cpcss"><a data-action_name="regenerate_cpcss" href="#">Regenerate Critical CSS</a></li>')
                            }
                        }

                        if($('#thirtd_part_cache_plugins').val() === "1"){
                            $content.find('ul').append('<li data-action_name="purge-url"><a data-action_name="purge-url" href="#">Clear Page Cache</a></li>');
                        }

                        if(data.rule_id){
                            $content.find('ul').append('<li data-action_name="detach_from_rule"><a data-action_name="detach_from_rule" href="#">Detach from Rule</a></li>')
                        }

                        if(!data.rule_id && window.uucss.rules_enabled === "1" && rule_table && rule_table.rows().data().length){
                            $content.find('ul').append('<li data-action_name="attach_to_rule"><a data-action_name="attach_to_rule" href="#">Attach Rule</a></li>')
                        }

                        $content.find('ul').append('<li data-action_name="remove"><a data-action_name="remove" href="#">Remove</a></li>');

                        $content.find('ul').append('<li data-action_name="preview"><a data-action_name="preview" target="_blank" href="' + data.url + '">Preview</a></li>');

                        return $content.wrap('<div></div>').parent().html();
                    },
                    onClickOutside(instance, event) {
                        instance.hide()
                    },
                    onCreate(){

                        tippy($('.uucss-option-list ul.option-list li[data-action_name="remove"]')[0], {
                            content: 'Remove RapidLoad cache files',
                            allowHTML: true,
                            placement: 'left',
                            hideOnClick: false,
                            animation: null,
                            interactive: true,
                            delay: 0,
                            inlinePositioning: true,
                            maxWidth: 500,
                            appendTo: 'parent'
                        })

                        tippy($('.uucss-option-list ul.option-list li[data-action_name="test"]')[0], {
                            content: 'Test Url',
                            allowHTML: true,
                            placement: 'left',
                            hideOnClick: false,
                            animation: null,
                            interactive: true,
                            delay: 0,
                            inlinePositioning: true,
                            maxWidth: 500,
                            appendTo: 'parent'
                        });

                    },
                    onMount(instance) {

                        $('.uucss-option-list ul.option-list li a').off().click(function (e) {

                            var $this = $(this);

                            var action = $this.data('action_name');

                            switch (action) {
                                case 'preview':{
                                    let dynamicUrl = $(this).attr('href').toString();
                                    let additionalParam = "rapidload_preview";
                                    window.open(dynamicUrl + (dynamicUrl.includes("?") ? "&" : "?") + additionalParam, "_blank");
                                    break;
                                }
                                case 'requeue_url':{
                                    requeue('current', {url : data.url},null, 'url')
                                    break;
                                }
                                case 'regenerate_cpcss':{

                                    wp.ajax.post('cpcss_purge_url',{ url : data.url, nonce : window.uucss.nonce }).then(function (i) {

                                        $.uucssAlert(i, 'success')

                                    }).fail(function (i) {

                                        $.uucssAlert(i, 'error')
                                    });

                                    break;
                                }
                                case 'attach_to_rule':{

                                    var $attach_rule_content = $('<div class="action-content"><div><select class="rule-items" id="attach-rule-item"></select></div><div><p>Base URL : <a class="base-url" target="_blank" href=""></a></p></div><div class="add-action-wrap"></div></div>');

                                    var rule_data = rule_table.rows().data();

                                    rule_data.each(function (value, index) {
                                        var $rule_item = $('<option class="rule-item" data-url="'+ value.url +'"  data-id="'+ value.id +'">Rule : [ '+ value.rule +' ] Pattern : [ '+ value.regex +' ]</option>')
                                        $rule_item.attr('value', value.id);
                                        $attach_rule_content.find('select.rule-items').append($rule_item)
                                    });

                                    $attach_rule_content.find('.add-action-wrap').append('<input id="update-attach-rule" type="button" class="button button-primary" value="Attach Rule">')
                                    $attach_rule_content.find('#update-attach-rule').data('url', data.url)

                                    $.featherlight($attach_rule_content,{
                                        variant : 'attach-rule-content-model uucss-update-form-fetherlight',
                                        afterOpen:function(){

                                            $('#attach-rule-item').change(function(){

                                                var baseUrl = $attach_rule_content.find('option[value="'+ $(this).val() +'"]').data('url');
                                                $('.attach-rule-content-model a.base-url').text(baseUrl);
                                                $('.attach-rule-content-model a.base-url').attr('href',baseUrl);
                                            })

                                            $('#attach-rule-item').trigger('change');

                                            $('#update-attach-rule').click(function(){

                                                wp.ajax.post('attach_rule',{ nonce : window.uucss.nonce, url : data.url, type : 'attach', rule_id : $('#attach-rule-item').val() }).then(function (i) {

                                                    $.uucssAlert(i, 'success')
                                                    var currentFeather = $.featherlight.current();
                                                    if(currentFeather) currentFeather.close();

                                                }).fail(function (i) {

                                                    $.uucssAlert(i, 'error')
                                                });

                                            })
                                        }
                                    })
                                    break;
                                }
                                case 'detach_from_rule':{
                                    wp.ajax.post('attach_rule',{ url : data.url, type : 'detach', nonce : window.uucss.nonce }).then(function (i) {

                                        $.uucssAlert(i, 'success')

                                    }).fail(function (i) {

                                        $.uucssAlert(i, 'error')
                                    });

                                    break;
                                }
                                case 'remove':{
                                    uucss_purge_url(data.url, true, row, dataIndex, data)
                                    /*wp.ajax.post('rapidload_purge_all',{
                                        job_type : 'url',
                                        url : data.url,
                                        clear : true
                                    }).then(function (i) {

                                    }).done(function(){

                                    });*/
                                    break;
                                }
                                case 'purge-url':{

                                    wp.ajax.post('clear_page_cache',{ url : data.url, nonce : window.uucss.nonce }).then(function (i) {

                                        $.uucssAlert(i, 'Successfully cleared your page cache')

                                    }).fail(function (i) {

                                        $.uucssAlert(i, 'Unknown error occurred when clearing the page cache')
                                    });

                                    break;
                                }
                                case 'test':{

                                    if($this.data('fetching')){
                                        return;
                                    }

                                    $.ajax({
                                        method : 'POST',
                                        url: wp.ajax.settings.url + '?action=uucss_test_url',
                                        data : {
                                            url: data.url,
                                            nonce : window.uucss.nonce
                                        },
                                        beforeSend(){
                                            $this.data('fetching', true);
                                        },
                                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                                            var $feather_content = $('.featherlight.uucss-gpsi-test .featherlight-content');
                                            var $content = $('<div class="content"></div>');

                                            $content.append('<div class="header"></div>');
                                            $content.append('<div class="devider"></div>');
                                            $content.append('<div class="description"></div>');

                                            $content.find('.header').append('<h2><span class="js-gpsi-reult dashicons dashicons-warning"></span>Pending</h2>')
                                            $content.find('.description').append('<p>Your optimization is yet to be reflected on Google Page Insight, GT Metrix and all other page speed testing tools.</p>')

                                            $feather_content.find('.spinner').remove();
                                            $feather_content.append($content.wrap('<div></div>').parent().html());
                                        },
                                        success: function (response) {
                                            var $feather_content = $('.featherlight.uucss-gpsi-test .featherlight-content');
                                            var $content = $('<div class="content"></div>');

                                            $content.append('<div class="header"></div>');
                                            $content.append('<div class="devider"></div>');
                                            $content.append('<div class="description"></div>');

                                            if(response.success && response.data && ( response.data.injected || response.data.success) && response.data.injectedCSS > 0){

                                                $content.find('.header').append('<h2><span class="js-gpsi-reult dashicons dashicons-yes-alt"></span>Success</h2>')
                                                $content.find('.description').append('<p>Optimization is now reflected in Google Page Speed Insight, GT Metrix and all other page speed testing tools.</p>')

                                            }else{

                                                $content.find('.header').append('<h2><span class="js-gpsi-reult dashicons dashicons-warning"></span>Pending</h2>')
                                                $content.find('.description').append('<p>Your optimization is yet to be reflected on Google Page Insight, GT Metrix and all other page speed testing tools.</p>')

                                            }

                                            if(response.success && response.data && response.data.success){

                                                const with_uucss = new URL(response.data.url);
                                                const without_uucss = new URL(response.data.url);
                                                without_uucss.searchParams.append('no_uucss','');

                                                $content.find('.description').html('<p>' + $content.find('.description').text() + ' Compare your page speed scores:' + '</p>')
                                                $content.find('.description').append('<p class="test-site-links-heading without-rapidload"><strong>Without RapidLoad</strong></p>');
                                                $content.find('.description').append('<ul class="test-site-links test-site-links-without"></ul>');
                                                $content.find('.test-site-links-without').append('<li class="test-site-link"><a href="https://gtmetrix.com/?url='+ without_uucss.toString().replace('no_uucss=','no_uucss') +'" target="_blank">GT Metrix</a></li>')
                                                $content.find('.test-site-links-without').append('<li class="test-site-link"><a href="https://developers.google.com/speed/pagespeed/insights/?url='+ without_uucss.toString().replace('no_uucss=','no_uucss') +'" target="_blank">Google Insights</a></li>')


                                                $content.find('.description').append('<p class="test-site-links-heading with-rapidload"><strong>RapidLoad</strong></p>');
                                                $content.find('.description').append('<ul class="test-site-links test-site-links-with"></ul>');
                                                $content.find('.test-site-links-with').append('<li class="test-site-link"><a href="https://gtmetrix.com/?url='+ with_uucss.toString() +'" target="_blank">GT Metrix</a></li>')
                                                $content.find('.test-site-links-with').append('<li class="test-site-link"><a href="https://developers.google.com/speed/pagespeed/insights/?url='+ with_uucss.toString() +'" target="_blank">Google Insights</a></li>')
                                            }

                                            $feather_content.find('.spinner').remove();
                                            $feather_content.append($content.wrap('<div></div>').parent().html());

                                            if(response.success && response.data){
                                                $('.js-gpsi-reult')
                                                    .featherlight('<div><div class="code"><pre><code>'+ JSON.stringify(response.data, undefined, 2) +'</code></pre></div></div>',{
                                                        variant : 'uucss-gpsi-result-details'
                                                    })
                                            }
                                        },
                                        complete:function () {
                                            $this.data('fetching', false);
                                        }
                                    });

                                    break;
                                }
                                default:{
                                    break;
                                }
                            }
                        })

                        $('.uucss-option-list ul.option-list li a[data-action_name="test"]')
                            .featherlight('<div class="spinner loading"></div>',{
                                variant : 'uucss-gpsi-test'
                            })
                    },
                    placement: 'bottom-end',
                })

                tippy($(row).find('span.job-status.status.waiting')[0], {
                    content: 'Waiting to be processed',
                    placement: 'top',
                    appendTo: "parent",
                });

                tippy($(row).find('button[data-uucss-optimize]')[0], {
                    content: 'Refresh files Immediately',
                    placement: 'top',
                    appendTo: "parent"
                });

                $(row).find('button').data('index',dataIndex);

                $(row).find('button[data-uucss-options]').off('click').click(function (e) {
                    e.preventDefault();
                });

                $(row).find('button[data-uucss-optimize]').off('click').click(function (e) {
                    e.preventDefault()

                    var is_clear = (typeof $(this).data().uucssClear === 'string')
                    var rule = $(this).data('rule');
                    var regex = $(this).data('regex');
                    var rule_id = $(this).data('rule_id');

                    uucss_purge_url(data.url, is_clear, row, dataIndex, data, { rule : rule, rule_id : rule_id, regex : regex})

                });

                $(row).find('button[data-uucss-optimize]').off('click').click(function (e) {
                    e.preventDefault()

                    var is_clear = (typeof $(this).data().uucssClear === 'string')

                    uucss_purge_url(data.url, is_clear, row, dataIndex, data, {immediate : true})

                });
            }
        });

        $uucss_rule_spinner.addClass('loading')
        rule_table = rule_table.DataTable({
            serverSide: true,
            processing: false,
            language: {
                processing: '<span class="spinner loading"></span>'
            },
            ajax: {
                beforeSend() {
                    !$uucss_rule_spinner.hasClass('loading') && $uucss_rule_spinner.addClass('loading');
                },
                url: wp.ajax.settings.url + '?action=uucss_data',
                type : 'POST',
                data: function (d) {

                    if(status_filter_rule !== "" && status_filter_rule !== undefined){
                        if(d.columns[0] && d.columns[0].search){
                            d.columns[0].search.value = status_filter_rule
                        }
                    }

                    if(url_filter_rule !== "" && url_filter_rule !== undefined){
                        if(d.columns[1] && d.columns[1].search){
                            d.columns[1].search.value = url_filter_rule;
                            d.columns[1].search.regex = exact_search_val_rule
                        }
                    }

                    d.nonce = uucss.nonce
                    d.type = 'rule'

                    return d;
                },
                dataSrc: function (d) {
                    $uucss_rule_spinner.removeClass('loading')

                    if (!d.success) {
                        $.uucssAlert("Failed to fetch optimizations", 'error')
                        return [];
                    }

                    var results = d.data;

                    firstRuleReload = false;
                    return results;
                }
            },
            searching: true,
            pagingType: "simple",
            tfoot: false,
            lengthChange : false,
            bSort: false,
            columns: [
                {
                    "data": "status",
                    title: "Status",
                    width: '50px',
                    className: 'dt-body-center dt-head-center',
                    render: function (data, type, row, meta) {
                        if(!data && row.cpcss){
                            data = row.cpcss.status
                        }
                        var classNames = 'status ';
                        if(data === 'queued' || data === 'processing'){
                            classNames += 'refresh ';
                        }
                        classNames += data + ' ';
                        return '<span class="job-status ' + classNames +'">'+ data +'</span>'
                    },
                },
                {
                    "data": "url",
                    title: "Base",
                    className: "url",
                    render(data) {

                        if (!data) {
                            return '';
                        }

                        return '<a href="'+ decodeURI(data) +'" target="_blank">'+ decodeURI(data) +'</a>';
                    }
                },
                {
                    "data": "applied_links",
                    title: "Jobs",
                    width: '25px',
                    className: 'dt-body-center dt-head-center applied-links hits-hidden',
                    render: function (data, type, row, meta) {
                        var font_style = row.applied_links === row.applied_successful_links ? 'style="font-weight:500"' : '';
                        return '<span class="" '+ font_style +'>'+ data +'</span>';
                    },
                },
                {
                    "data": "applied_successful_links",
                    title: "Hits",
                    width: '25px',
                    visible : false,
                    className: 'dt-body-center dt-head-center hits-count',
                    render: function (data, type, row, meta) {
                        return '<span class="">'+ data +'</span>';
                    },
                },
                {
                    "data": "regex",
                    title: "pattern",
                    width: '200px',
                    className: 'dt-body-center dt-head-center pattern',
                    render: function (data, type, row, meta) {
                        return '<span class="">'+ (data ? data : '') +'</span>';
                    },
                },
                {
                    "data": "rule",
                    title: "Rule",
                    width: '100px',
                    className: 'dt-body-center dt-head-center',
                    render: function (data, type, row, meta) {
                        return '<span class="">'+ (data ? data.replace('is_','') : '') +'</span>';
                    },
                },
                {
                    data: "url",
                    className: 'dt-body-center dt-head-center stats th-reduction',
                    title: "File Size Reduction",
                    width: '145px',
                    render: function (data, type, row, meta) {
                        if (row.meta && row.meta.stats && row.meta.stats.reduction && row.status === 'success') {
                            return row.meta.stats.reduction + '%'
                        }else if(row.status === 'queued' || row.status === 'processing' || row.status === 'waiting'){
                            return '<span class="job-file-size">-</span>';
                        }

                        return '-'
                    },
                    createdCell: function (td, cellData, rowData) {

                        var innerTippy
                        var innerTippy2

                        var stat = $(td).wrapInner($('<span></span>'));

                        var $warnings_html = $('<div class="uucss-warnings"></div>');

                        var $cpcss_html = $('<div class="cpcss-status cpcss-status-' + (rowData.cpcss ? rowData.cpcss.status : '') + '"></div>');

                        if(rowData.cpcss){
                            if(rowData.cpcss.status === 'success'){
                                $cpcss_html.append('<span class="dashicons dashicons-yes-alt" style="color : #009688; width: 16px; height: 16px"></span>');
                                $cpcss_html.append('<span style="font-size: 12px; margin-left:2px">Critical css generated ('+ rowData.cpcss.hits + '/' + rowData.cpcss.attempts +')</span>');
                            }
                        }

                        if(!window.uucss || !window.uucss.uucss_enable_debug){
                            if(rowData.meta?.warnings?.length){
                                rowData.meta.warnings = rowData.meta.warnings.filter(function(w){
                                    return !w.message.toString().includes('optimized version for the file missing')
                                })
                            }
                        }

                        /*if(rowData.meta.warnings && rowData.meta.warnings.length){
                            var scrollable = rowData.meta.warnings.length > 2 ? 'scrollable' : '';
                            $warnings_html.append('<h5 class="warnings-title ">Warnings (' + rowData.meta.warnings.length  + ')</h5>');
                            $warnings_html.append('<ul class="warning-list ' + scrollable  + '"></ul>');
                            $.each(rowData.meta.warnings, function(index, value){
                                var $warning_html = $('<li class="warning"></li>')
                                $warning_html.append('<div class="warning-info"></div>');
                                $warning_html.find('.warning-info').append('<p class="warning-header">' +  value.message + '</p>');
                                if(value.file){
                                    $warning_html.find('.warning-info').append('<p class="warning-content"><a href="' + value.file +'" target="_blank">' +  value.file + '</a></p>');
                                }
                                $warnings_html.find('.warning-list').append($warning_html.wrap('<div></div>').parent().html())
                            })
                        }else{
                            
                        }*/

                        $warnings_html.removeClass('uucss-warnings');

                        var attemptsString = '';

                        if(rowData.success_count > 0){
                            attemptsString = 'Hits : ' + rowData.success_count + '/' + rowData.attempts;
                        }else if(rowData.meta && rowData.meta.stats && rowData.meta.stats.success_count){
                            attemptsString = 'Hits : ' + rowData.meta.stats.success_count + '/' + rowData.attempts;
                        }else if(rowData.cpcss){
                            if(rowData.cpcss.status === 'success' && rowData.cpcss.hits > 0){
                                attemptsString = 'Hits : ' + rowData.cpcss.hits + '/' + rowData.cpcss.attempts
                            }else if(Number(rowData.attempts) !== 0) {
                                attemptsString = 'Attempts : ' + rowData.attempts
                            }
                        }else if(Number(rowData.attempts) !== 0){
                            attemptsString = 'Attempts : ' + rowData.attempts
                        }

                        var tippyOptions;

                        tippyOptions = {
                            theme: 'light',
                            triggerTarget: stat.find('span')[0],
                            content: function () {
                                var c = $('<div class="stat-tooltip">' +
                                    '       <div class="progress-bar-wrapper">' +
                                    '           <div class="progress-bar w-100">' +
                                    '               <span style="width:' + (100 - (rowData.meta && rowData.meta.stats ? rowData.meta.stats.reduction : 100)) + '%">' + (100 - (rowData.meta && rowData.meta.stats ? rowData.meta.stats.reduction : 100)).toFixed() + '%' +
                                    '               </span>' +
                                    '           </div>' +
                                    '       </div>' +
                                    $cpcss_html.wrap('<div class="cpcss-result"></div>').parent().html() +
                                    $warnings_html.wrap('<div></div>').parent().html() +
                                    '<div class="time">' +
                                    '   <p class="val uucss-show-job-details">Created at ' +
                                    new Date(rowData.time * 1000).toLocaleDateString() + ' ' + new Date(rowData.time * 1000).toLocaleTimeString() +
                                    '   </p>' +
                                    '   <p class="attempts">' +
                                    attemptsString +
                                    '   </p>' +
                                    '</div>' +
                                    '</div>');

                                innerTippy = tippy(c.find('.progress-bar-wrapper')[0], {
                                    content: 'Without RapidLoad <span class="perc">' + (rowData.meta && rowData.meta.stats ? rowData.meta.stats.before : 0) + '</span>',
                                    allowHTML: true,
                                    placement: 'bottom-end',
                                    trigger: 'manual',
                                    hideOnClick: false,
                                    animation: null,
                                    theme: 'tomato',
                                    interactive: true,
                                    delay: 0,
                                    offset: [0, 7],
                                    inlinePositioning: true,
                                })

                                innerTippy2 = tippy(c.find('.progress-bar')[0], {
                                    content: 'RapidLoad <span class="perc"> ' + (rowData.meta && rowData.meta.stats ? rowData.meta.stats.after : 0) + '</span>',
                                    allowHTML: true,
                                    placement: 'top-start',
                                    trigger: 'manual',
                                    hideOnClick: false,
                                    animation: null,
                                    theme: 'ketchup',
                                    interactive: true,
                                    delay: 0,
                                    inlinePositioning: true,
                                })

                                return c[0]
                            },
                            placement: 'left',
                            //trigger: 'click',
                            interactive: true,
                            allowHTML: true,
                            animation: "shift-toward-extreme",
                            appendTo: "parent",
                            onShow: function () {
                                innerTippy.show()
                                innerTippy2.show()
                            },
                            onShown: function (instance) {
                                $(instance.popper).find('.progress-bar.w-100').removeClass('w-100')
                                $('.uucss-show-job-details')
                                    .featherlight('<div><div class="code"><pre><code>'+ JSON.stringify(rowData, undefined, 2) +'</code></pre></div></div>',{
                                        variant : 'uucss-job-details'
                                    })
                            },
                            onHide: function (instance) {
                                innerTippy.hide()
                                innerTippy2.hide()
                                $(instance.popper).find('.progress-bar').addClass('w-100')
                            }

                        }

                        if (rowData.status === 'failed') {
                            stat.find('span').append('<span class="dashicons dashicons-info error"></span>');

                            tippyOptions.onShow = function () {
                            }
                            tippyOptions.onHide = function () {
                            }

                            var code = (rowData.meta.error.code) ? rowData.meta.error.code : 500;
                            tippyOptions.content = '<div class="error-tooltip"><h5>Error</h5> <span><strong>CODE :</strong> ' + code + '</span> <br><span>' + rowData.meta.error.message + '</span></div>';

                            //tippyOptions.triggerTarget = $(td).closest('tr')[0]
                            tippy(stat.find('span')[0], tippyOptions);
                            return
                        }

                        if (rowData.status === 'success' && (!rowData.meta?.warnings || !rowData.meta?.warnings?.length)) {
                            var hits = rowData.meta && rowData.meta.stats && rowData.meta.stats.success_count > 0 || Number(rowData.success_count) > 0 ? 'hits-success' : '';
                            stat.find('span').append('<span class="dashicons dashicons-yes-alt '+ hits +'"></span>');
                            tippy(stat.find('span')[0], tippyOptions);
                        } else if (rowData.status === 'success' && rowData.meta.warnings.length) {
                            stat.find('span').append('<span class="dashicons dashicons-warning"></span>');
                            tippy(stat.find('span')[0], tippyOptions);
                        } else if(rowData.status === 'failed'){
                            stat.find('span').append('<span class="dashicons dashicons-info error"></span>');
                            tippy(stat.find('span')[0], tippyOptions);
                        } else if (rowData.cpcss){
                            if(rowData.cpcss.status === 'success'){
                                stat.find('span').append('<span class="dashicons dashicons-yes-alt"></span>');
                                tippy(stat.find('span')[0], tippyOptions);
                            }else if(rowData.cpcss.status === 'failed'){
                                stat.find('span').append('<span class="dashicons dashicons-info error"></span>');
                                tippy(stat.find('span')[0], tippyOptions);
                            }
                        }

                    }
                },
                {
                    "data": "status",
                    visible : false,
                    render: function (data, type, row, meta) {
                        if(!data && row.cpcss){
                            data = row.cpcss.status
                        }
                        if (data && data.warnings && data.warnings.length > 0) return 'warning';
                        return data.status;
                    }
                },
                {
                    "data": "url",
                    className: 'dt-body-right dt-head-right action th-actions',
                    "targets": 0,
                    title: "Actions",
                    width: '60px',
                    render: function (data, type, row, meta) {
                        var _render = '';

                        _render += '<button data-uucss-optimize data-url="' + data + '" data-rule="'+ row.rule + '" data-rule_id="'+ row.id + '" data-regex="'+ row.regex + '"><span class="dashicons dashicons-update"></span></button>'

                        _render += '<button data-uucss-options data-url="' + data + '" data-rule="'+ row.rule + '" data-regex="'+ row.regex + '"><span class="dashicons dashicons-ellipsis"></span></button>';

                        return _render;
                    },
                }
            ],
            rowCallback: function (row, data, displayNum, displayIndex, dataIndex) {

                tippy($(row).find('button[data-uucss-options]')[0], {
                    allowHTML: true,
                    trigger: 'click',
                    arrow: true,
                    appendTo: $(row).find('button[data-uucss-options]')[0],
                    interactive: true,
                    animation: 'shift-toward',
                    hideOnClick: true,
                    theme: 'light',
                    content: ()=>{

                        var $content = $('<div class="uucss-option-list"><ul class="option-list"></ul></div>')

                        $content.find('ul').append('<li data-action_name="edit_rule"><a data-action_name="edit_rule" href="#" data-url="'+ data.url + '" data-rule="'+ data.rule + '" data-regex="'+ data.regex + '" data-index="'+ dataIndex + '">Edit</a></li>');
                        $content.find('ul').append('<li data-action_name="duplicate_rule"><a data-action_name="duplicate_rule" href="#" data-url="'+ data.url + '" data-rule="'+ data.rule + '" data-regex="'+ data.regex + '" data-index="'+ dataIndex + '">Duplicate</a></li>');

                        if(data.status !== 'queued'){
                            $content.find('ul').append('<li data-action_name="requeue_rule"><a data-action_name="requeue_rule" href="#" data-url="'+ data.url + '" data-rule="'+ data.rule + '" data-regex="'+ data.regex + '" data-index="'+ dataIndex + '">Requeue</a></li>');
                            if(window.uucss && window.uucss.cpcss_enabled === "1"){
                                $content.find('ul').append('<li data-action_name="regenerate_cpcss"><a data-action_name="regenerate_cpcss" href="#" data-url="'+ data.url + '" data-rule="'+ data.rule + '" data-regex="'+ data.regex + '" data-index="'+ dataIndex + '">Regenerate Critical CSS</a></li>')
                            }
                        }

                        if(data.status === 'success'){
                            $content.find('ul').append('<li data-action_name="test"><a data-action_name="test" href="#" data-url="'+ data.url + '" data-rule="'+ data.rule + '" data-regex="'+ data.regex + '" data-index="'+ dataIndex + '">GPSI Status</a></li>')
                        }

                        if($('#thirtd_part_cache_plugins').val() === "1"){
                            $content.find('ul').append('<li data-action_name="purge-url"><a data-action_name="purge-url" data-rule="'+ data.rule + '" data-regex="'+ data.regex + '" href="#" data-url="'+ data.url + '" data-index="'+ dataIndex + '">Clear Page Cache</a></li>');
                        }

                        $content.find('ul').append('<li data-action_name="remove"><a data-action_name="remove" href="#" data-url="'+ data.url + '" data-rule="'+ data.rule + '" data-regex="'+ data.regex + '" data-index="'+ dataIndex + '">Remove</a></li>');
                        $content.find('ul').append('<li data-action_name="preview"><a data-action_name="preview" target="_blank" href="' + data.url + '">Preview</a></li>');
                        return $content.wrap('<div></div>').parent().html();
                    },
                    onClickOutside(instance, event) {
                        instance.hide()
                    },
                    onCreate(){

                        tippy($('.uucss-option-list ul.option-list li[data-action_name="remove"]')[0], {
                            content: 'Remove RapidLoad cache files',
                            allowHTML: true,
                            placement: 'left',
                            hideOnClick: false,
                            animation: null,
                            interactive: true,
                            delay: 0,
                            inlinePositioning: true,
                            maxWidth: 500,
                            appendTo: 'parent'
                        })

                        tippy($('.uucss-option-list ul.option-list li[data-action_name="test"]')[0], {
                            content: 'Test Url',
                            allowHTML: true,
                            placement: 'left',
                            hideOnClick: false,
                            animation: null,
                            interactive: true,
                            delay: 0,
                            inlinePositioning: true,
                            maxWidth: 500,
                            appendTo: 'parent'
                        });

                    },
                    onMount(instance) {

                        $('.uucss-option-list ul.option-list li a').off().click(function (e) {

                            var $this = $(this);

                            var action = $this.data('action_name');
                            var rule = $this.data('rule');
                            var regex = $this.data('regex');
                            var url = $this.data('url');

                            switch (action) {
                                case 'preview':{
                                    let dynamicUrl = $(this).attr('href').toString();
                                    let additionalParam = "rapidload_preview";
                                    window.open(dynamicUrl + (dynamicUrl.includes("?") ? "&" : "?") + additionalParam, "_blank");
                                    break;
                                }
                                case 'requeue_rule':{
                                    requeue('current', {
                                        url : url,
                                        rule : rule,
                                        regex : regex
                                    }, null, 'rule');
                                    break;
                                }
                                case 'regenerate_cpcss':{

                                    wp.ajax.post('cpcss_purge_url',{ url : url, nonce : window.uucss.nonce }).then(function (i) {

                                        $.uucssAlert(i, 'success')

                                    }).fail(function (i) {

                                        $.uucssAlert(i, 'error')
                                    });

                                    break;
                                }
                                case 'duplicate_rule':{
                                    $.featherlight($('#add_rule_featherlight_content'),{
                                        variant : 'add-site-rule-model',
                                        afterOpen:function (){
                                            this.$content.find('#model-uucss-rules').val(rule);
                                            this.$content.find('input.rule-base-url').val(url);
                                            this.$content.find('input.rule-url-regex').val(regex);
                                        }
                                    })
                                    break;
                                }
                                case 'edit_rule':{
                                    $.featherlight($('#add_rule_featherlight_content'),{
                                        variant : 'add-site-rule-model',
                                        afterOpen:function (){
                                            this.$content.find('#model-uucss-rules').val(rule);
                                            this.$content.data('old_rule',rule);
                                            this.$content.find('input.rule-base-url').val(url);
                                            this.$content.data('old_base_url',url);
                                            this.$content.find('input.rule-url-regex').val(regex);
                                            this.$content.data('old_rule_regex',regex);
                                        }
                                    })
                                    break;
                                }
                                case 'remove':{
                                    uucss_purge_url(data.url, true, row, dataIndex, data, { rule : rule, regex : regex })
                                    /*wp.ajax.post('rapidload_purge_all',{
                                        job_type : 'rule',
                                        rule : rule,
                                        regex : regex,
                                        clear : true
                                    }).then(function (i) {

                                    }).done(function(){

                                    });*/
                                    break;
                                }
                                case 'purge-url':{

                                    wp.ajax.post('clear_page_cache',{ url : data.url, rule : rule, regex : regex, nonce : window.uucss.nonce }).then(function (i) {

                                        $.uucssAlert(i, 'Successfully cleared your page cache')

                                    }).fail(function (i) {

                                        $.uucssAlert(i, 'Unknown error occurred when clearing the page cache')
                                    });

                                    break;
                                }
                                case 'test':{

                                    if($this.data('fetching')){
                                        return;
                                    }

                                    $.ajax({
                                        method : 'POST',
                                        url: wp.ajax.settings.url + '?action=uucss_test_url',
                                        data : {
                                            url: data.url,
                                            type: 'rule',
                                            rule : rule,
                                            regex : regex,
                                            nonce : window.uucss.nonce
                                        },
                                        beforeSend(){
                                            $this.data('fetching', true);
                                        },
                                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                                            var $feather_content = $('.featherlight.uucss-gpsi-test .featherlight-content');
                                            var $content = $('<div class="content"></div>');

                                            $content.append('<div class="header"></div>');
                                            $content.append('<div class="devider"></div>');
                                            $content.append('<div class="description"></div>');

                                            $content.find('.header').append('<h2><span class="js-gpsi-reult dashicons dashicons-warning"></span>Pending</h2>')
                                            $content.find('.description').append('<p>Your optimization is yet to be reflected on Google Page Insight, GT Metrix and all other page speed testing tools.</p>')

                                            $feather_content.find('.spinner').remove();
                                            $feather_content.append($content.wrap('<div></div>').parent().html());
                                        },
                                        success: function (response) {
                                            var $feather_content = $('.featherlight.uucss-gpsi-test .featherlight-content');
                                            var $content = $('<div class="content"></div>');

                                            $content.append('<div class="header"></div>');
                                            $content.append('<div class="devider"></div>');
                                            $content.append('<div class="description"></div>');

                                            if(response.success && response.data && ( response.data.injected || response.data.success) && response.data.injectedCSS > 0){

                                                $content.find('.header').append('<h2><span class="js-gpsi-reult dashicons dashicons-yes-alt"></span>Success</h2>')
                                                $content.find('.description').append('<p>Optimization is now reflected in Google Page Speed Insight, GT Metrix and all other page speed testing tools.</p>')

                                            }else{

                                                $content.find('.header').append('<h2><span class="js-gpsi-reult dashicons dashicons-warning"></span>Pending</h2>')
                                                $content.find('.description').append('<p>Your optimization is yet to be reflected on Google Page Insight, GT Metrix and all other page speed testing tools.</p>')

                                            }

                                            if(response.success && response.data && response.data.success){

                                                const with_uucss = new URL(response.data.url);
                                                const without_uucss = new URL(response.data.url);
                                                without_uucss.searchParams.append('no_uucss','');

                                                $content.find('.description').html('<p>' + $content.find('.description').text() + ' Compare your page speed scores:' + '</p>')
                                                $content.find('.description').append('<p class="test-site-links-heading without-rapidload"><strong>Without RapidLoad</strong></p>');
                                                $content.find('.description').append('<ul class="test-site-links test-site-links-without"></ul>');
                                                $content.find('.test-site-links-without').append('<li class="test-site-link"><a href="https://gtmetrix.com/?url='+ without_uucss.toString().replace('no_uucss=','no_uucss') +'" target="_blank">GT Metrix</a></li>')
                                                $content.find('.test-site-links-without').append('<li class="test-site-link"><a href="https://developers.google.com/speed/pagespeed/insights/?url='+ without_uucss.toString().replace('no_uucss=','no_uucss') +'" target="_blank">Google Insights</a></li>')


                                                $content.find('.description').append('<p class="test-site-links-heading with-rapidload"><strong>RapidLoad</strong></p>');
                                                $content.find('.description').append('<ul class="test-site-links test-site-links-with"></ul>');
                                                $content.find('.test-site-links-with').append('<li class="test-site-link"><a href="https://gtmetrix.com/?url='+ with_uucss.toString() +'" target="_blank">GT Metrix</a></li>')
                                                $content.find('.test-site-links-with').append('<li class="test-site-link"><a href="https://developers.google.com/speed/pagespeed/insights/?url='+ with_uucss.toString() +'" target="_blank">Google Insights</a></li>')
                                            }

                                            $feather_content.find('.spinner').remove();
                                            $feather_content.append($content.wrap('<div></div>').parent().html());

                                            if(response.success && response.data){
                                                $('.js-gpsi-reult')
                                                    .featherlight('<div><div class="code"><pre><code>'+ JSON.stringify(response.data, undefined, 2) +'</code></pre></div></div>',{
                                                        variant : 'uucss-gpsi-result-details'
                                                    })
                                            }
                                        },
                                        complete:function () {
                                            $this.data('fetching', false);
                                        }
                                    });

                                    break;
                                }
                                default:{
                                    break;
                                }
                            }
                        })

                        $('.uucss-option-list ul.option-list li a[data-action_name="test"]')
                            .featherlight('<div class="spinner loading"></div>',{
                                variant : 'uucss-gpsi-test'
                            })
                    },
                    placement: 'bottom-end',
                })

                tippy($(row).find('span.job-status.status.waiting')[0], {
                    content: 'Waiting to be processed',
                    placement: 'top',
                    appendTo: "parent",
                });

                tippy($(row).find('button[data-uucss-optimize]')[0], {
                    content: 'Refresh files Immediately',
                    placement: 'top',
                    appendTo: "parent"
                });

                $(row).find('button').data('index',dataIndex);

                $(row).find('button[data-uucss-options]').off('click').click(function (e) {
                    e.preventDefault();
                });

                $(row).find('button[data-uucss-optimize]').off('click').click(function (e) {
                    e.preventDefault()

                    var is_clear = (typeof $(this).data().uucssClear === 'string')
                    var rule = $(this).data('rule');
                    var regex = $(this).data('regex');
                    var rule_id = $(this).data('rule_id');

                    uucss_purge_url(data.url, is_clear, row, dataIndex, data, { rule : rule, rule_id : rule_id, regex : regex})

                });

                $(row).find('button[data-uucss-optimize]').off('click').click(function (e) {
                    e.preventDefault()

                    var is_clear = (typeof $(this).data().uucssClear === 'string')

                    uucss_purge_url(data.url, is_clear, row, dataIndex, data, {immediate : true})

                });
            }
        });

        $('button.uucss-add-site-urls-submenu').off('click').click(function (e) {
            e.preventDefault();
        });

        $('button.uucss-add-site-rule-submenu').off('click').click(function (e) {
            e.preventDefault();
        });

        function requeue(post_type, data = {}, list = [], type = 'requeue_all_url'){

            var data_ = {
                url_list : list,
                url : data.url,
                rule : data.rule,
                regex : data.regex,
                post_type : post_type,
                type : type,
                job_type : type,
                nonce : window.uucss.nonce
            }

            /*wp.ajax.post('uucss_queue',data_).then(function (i) {
                if(table && type === 'path'){
                    table.ajax.reload(null, false);
                }else if(rule_table && type === 'rule'){
                    rule_table.ajax.reload(null, false);
                }
            }).done(function () {
                $('#uucss-wrapper li.uucss-history').hasClass('multi-select') && $('#uucss-wrapper li.uucss-history').removeClass('multi-select');
            });*/

            wp.ajax.post('rapidload_purge_all',data_).then(function (i) {

            }).done(function(){
                $('#uucss-wrapper li.uucss-history').hasClass('multi-select') && $('#uucss-wrapper li.uucss-history').removeClass('multi-select');
            });

            //wp.ajax.post('cpcss_purge_url',{ url : data.url, post_type : post_type });

        }

        tippy($('button.uucss-add-site-urls-submenu')[0], {
            allowHTML: true,
            trigger: 'click',
            arrow: true,
            appendTo: $('button.uucss-add-site-urls-submenu')[0],
            interactive: true,
            animation: 'shift-toward',
            hideOnClick: true,
            theme: 'light',
            content: ()=>{

                var $content = $('<div class="uucss-submenu-option-list"><ul class="option-list"></ul></div>')

                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_all"><a data-action_name="requeue_all" href="#">Requeue All</a></li>');
                $content.find('ul').append('<li class="multi-select-menu" data-action_name="requeue_selected"><a data-action_name="requeue_selected" href="#">Requeue Selected</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_processing"><a data-action_name="requeue_processing" href="#">Requeue Pending</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_warnings"><a data-action_name="requeue_warnings" href="#">Requeue Warnings</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_failed"><a data-action_name="requeue_failed" href="#">Requeue Failed</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="remove_all"><a data-action_name="remove_all" href="#">Remove All</a></li>');
                $content.find('ul').append('<li class="multi-select-menu" data-action_name="remove_selected"><a data-action_name="remove_selected" href="#">Remove Selected</a></li>');
                $content.find('ul').append('<li class="select-all" data-action_name="select_all"><a data-action_name="select_all" href="#">Select All</a></li>');

                if(window.uucss && window.uucss.dev_mode === "1"){
                    $content.find('ul').append('<li data-action_name="run_gpsi_test"><a data-action_name="run_gpsi_test" href="#">Run GPSI Test</a></li>');
                    //$content.find('ul').append('<li class="rule-stats" data-action_name="rule-stats"><a data-action_name="rule-stats" href="#">Find Duplicate Files</a></li>');
                }

                if($('#thirtd_part_cache_plugins').val() === "1"){
                    $content.find('ul').append('<li data-action_name="clear_warnings_cache"><a data-action_name="clear_warnings_cache" href="#">Clear Page Cache</a></li>');
                }


                return $content.wrap('<div></div>').parent().html();
            },
            onClickOutside(instance, event) {
                instance.hide()
            },
            onCreate(){

            },
            onMount(instance) {

                $('.uucss-submenu-option-list ul.option-list li a').off().click(function (e) {

                    var $this = $(this);

                    var action = $this.data('action_name');

                    switch (action) {
                        case 'rule-stats':{
                            wp.ajax.post('uucss_rule_stats').then(function (i) {
                                if(i){

                                    var $ruleStatsContent = $('<div class="rule-stats-cont"><ol class="duplicates"></ol></div>');

                                    if(i.duplicateFiles && i.duplicateFiles.length){

                                        $.each(i.duplicateFiles,function(index, value){
                                            var $duplicateFile = $('<li class="duplicate-file-item"></li>');
                                            $duplicateFile.data('otherURLs', value.otherUrls)
                                            $duplicateFile.append('<p>Count : '+ value.count +' Link : <a class="duplicate-file-item-base" target="_blank" href="'+ value.url +'">'+value.url+'</a></p>')
                                            $ruleStatsContent.find('ol.duplicates').append($duplicateFile);
                                        });
                                    }

                                    $.featherlight($ruleStatsContent,{
                                        variant : 'uucss-rule-stats',
                                        afterOpen: function(){

                                            $.each($('a.duplicate-file-item-base'), function (index, value){

                                                var $otherUrls = $('<div><ol class="duplicate-other-urls"></ol></div>');

                                                var list = $(value).parent().parent().data('otherURLs');

                                                if(list && list.length){

                                                    $.each(list, function(index, url){
                                                        $otherUrls.find('ol').append('<li><a target="_blank" href="' + url + '">'+ url +'</a>')
                                                    })
                                                }

                                                tippy($(value)[0],{
                                                    content: $otherUrls.html(),
                                                    theme: 'light',
                                                    allowHTML: true,
                                                    interactive: true,
                                                    hideOnClick:true,
                                                    placement:'right'
                                                })

                                            });


                                        }
                                    });
                                }
                            });
                            break;
                        }
                        case 'requeue_selected':
                        case 'requeue_all':{
                            var requeue_url_list = [];
                            if(table.rows('.selected').data().length){
                                $.each(table.rows('.selected').data(), function(table_row_index, table_row_value){
                                    requeue_url_list.push(table_row_value.id)
                                });
                            }
                            requeue('current', {}, requeue_url_list, 'requeue_all_url');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }case 'requeue_warnings':{
                            requeue('warnings', {}, requeue_url_list, 'requeue_all_url_warnings');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }case 'requeue_failed':{
                            requeue('failed', {}, requeue_url_list, 'requeue_all_url_failed');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }case 'requeue_processing':{
                            requeue('processing', {}, requeue_url_list, 'requeue_all_url_processing');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }
                        case 'remove_selected':
                        case 'remove_all':{
                            var data = {
                                url : '',
                                clear : true,
                                job_type: 'url',
                                nonce: window.uucss.nonce,
                            }

                            if(action === 'remove_selected'){
                                var url_list = [];
                                if(table.rows('.selected').data().length){
                                    $.each(table.rows('.selected').data(), function(table_row_index, table_row_value){
                                        url_list.push(table_row_value.id)
                                    });
                                }
                                if(url_list.length){
                                    data.url_list = url_list
                                }
                            }

                            /*wp.ajax.post('uucss_purge_url',data).then(function (i) {
                                if(table){
                                    table.ajax.reload(null, false);
                                    $.uucssAlert('Successfully removed links from the table', 'info');
                                }
                            }).done(function(){
                                $('#uucss-wrapper li.uucss-history').hasClass('multi-select') && $('#uucss-wrapper li.uucss-history').removeClass('multi-select')
                            });
                            wp.ajax.post('cpcss_purge_url',data).then(function (i) {

                            }).done(function(){

                            });*/
                            wp.ajax.post('rapidload_purge_all',data).then(function (i) {

                            }).done(function(){
                                $('#uucss-wrapper li.uucss-history').hasClass('multi-select') && $('#uucss-wrapper li.uucss-history').removeClass('multi-select')
                            });
                            break;
                        }
                        case 'clear_warnings_cache':{
                            wp.ajax.post('clear_page_cache',{ status : 'warnings', nonce : window.uucss.nonce }).then(function (i) {

                                $.uucssAlert(i, 'Successfully cleared your page cache')

                            }).fail(function (i) {

                                $.uucssAlert(i, 'Unknown error occurred when clearing the page cache')
                            });
                            break;
                        }
                        case 'run_gpsi_test':{
                            wp.ajax.post('uucss_run_gpsi_status_check_for_all',{ nonce : window.uucss.nonce }).then(function (i) {
                                $.uucssAlert('GPSI test run started')
                            }).fail(function (i) {

                            });
                            break;
                        }
                        case 'select_all':{
                            var $container = $('#uucss-wrapper li.uucss-job-history');
                            if($container.hasClass('multi-select')){
                                $container.removeClass('multi-select');
                            }
                            if($container.find('table tbody tr').hasClass('selected')){
                                $container.find('table tbody tr').removeClass('selected')
                            }
                            $container.find('table tbody tr').addClass('selected')
                            $container.addClass('multi-select');
                            $container.find('.multiple-selected-text .multiple-selected-value').text('(' + $container.find('table tbody tr.selected').length + ') URLs');
                            break;
                        }
                        default:{
                            break;
                        }
                    }
                })
            },
            placement: 'bottom-end',
        })

        tippy($('button.uucss-add-site-rule-submenu')[0], {
            allowHTML: true,
            trigger: 'click',
            arrow: true,
            appendTo: $('button.uucss-add-site-rule-submenu')[0],
            interactive: true,
            animation: 'shift-toward',
            hideOnClick: true,
            theme: 'light',
            content: ()=>{

                var $content = $('<div class="uucss-submenu-option-list"><ul class="option-list"></ul></div>')

                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_all"><a data-action_name="requeue_all" href="#">Requeue All</a></li>');
                $content.find('ul').append('<li class="multi-select-menu" data-action_name="requeue_selected"><a data-action_name="requeue_selected" href="#">Requeue Selected</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_processing"><a data-action_name="requeue_processing" href="#">Requeue Pending</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_warnings"><a data-action_name="requeue_warnings" href="#">Requeue Warnings</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="requeue_failed"><a data-action_name="requeue_failed" href="#">Requeue Failed</a></li>');
                $content.find('ul').append('<li class="simple-menu" data-action_name="remove_all"><a data-action_name="remove_all" href="#">Remove All</a></li>');
                $content.find('ul').append('<li class="multi-select-menu" data-action_name="remove_selected"><a data-action_name="remove_selected" href="#">Remove Selected</a></li>');
                $content.find('ul').append('<li class="select-all" data-action_name="select_all"><a data-action_name="select_all" href="#">Select All</a></li>');
                $content.find('ul').append('<li class="export-all" data-action_name="export_all"><a data-action_name="export_all" href="#">Export</a></li>');
                $content.find('ul').append('<li class="import-all" data-action_name="import_all"><a data-action_name="import_all" href="#">Import</a></li>');

                return $content.wrap('<div></div>').parent().html();
            },
            onClickOutside(instance, event) {
                instance.hide()
            },
            onCreate(){

            },
            onMount(instance) {

                $('.uucss-submenu-option-list ul.option-list li a').off().click(function (e) {

                    var $this = $(this);

                    var action = $this.data('action_name');

                    switch (action) {
                        case 'requeue_selected':
                        case 'requeue_all':{
                            var requeue_url_list = [];
                            if(rule_table.rows('.selected').data().length){
                                $.each(table.rows('.selected').data(), function(table_row_index, table_row_value){
                                    requeue_url_list.push(table_row_value.id)
                                });
                            }
                            requeue('current', {}, requeue_url_list, 'requeue_all_rule');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }case 'requeue_warnings':{
                            requeue('warnings', {},  null, 'requeue_all_rule_warnings');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }case 'requeue_failed':{
                            requeue('failed', {}, null, 'requeue_all_rule_failed');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }case 'requeue_processing':{
                            requeue('processing', {}, null, 'requeue_all_rule_processing');
                            $.uucssAlert('Successfully added links added to the queue');
                            break;
                        }
                        case 'remove_selected':
                        case 'remove_all':{
                            var data = {
                                url : '',
                                clear : true,
                                nonce: window.uucss.nonce,
                                job_type: 'rule',
                                args: {
                                    type : 'rule'
                                }
                            }

                            if(action === 'remove_selected'){
                                var url_list = [];
                                if(rule_table.rows('.selected').data().length){
                                    $.each(rule_table.rows('.selected').data(), function(table_row_index, table_row_value){
                                        url_list.push({
                                            url : table_row_value.url,
                                            rule : table_row_value.rule,
                                            regex : table_row_value.regex
                                        })
                                    });
                                }
                                if(url_list.length){
                                    data.url_list = url_list
                                }
                            }

                            /*wp.ajax.post('uucss_purge_url',data).then(function (i) {
                                if(rule_table){
                                    rule_table.ajax.reload(null, false);
                                    $.uucssAlert('Successfully removed links from the table', 'info');
                                }
                            }).done(function(){
                                $('#uucss-wrapper li.uucss-history').hasClass('multi-select') && $('#uucss-wrapper li.uucss-history').removeClass('multi-select')
                            });*/
                            wp.ajax.post('rapidload_purge_all',data).then(function (i) {

                            }).done(function(){
                                $('#uucss-wrapper li.uucss-history').hasClass('multi-select') && $('#uucss-wrapper li.uucss-history').removeClass('multi-select')
                            });
                            break;
                        }
                        case 'clear_warnings_cache':{
                            wp.ajax.post('clear_page_cache',{ status : 'warnings', type : 'rule', nonce : window.uucss.nonce }).then(function (i) {

                                $.uucssAlert(i, 'Successfully cleared your page cache')

                            }).fail(function (i) {

                                $.uucssAlert(i, 'Unknown error occurred when clearing the page cache')
                            });
                            break;
                        }
                        case 'run_gpsi_test':{
                            wp.ajax.post('uucss_run_gpsi_status_check_for_all',{ nonce : window.uucss.nonce }).then(function (i) {
                                $.uucssAlert('GPSI test run started')
                            }).fail(function (i) {

                            });
                            break;
                        }
                        case 'select_all':{
                            var $container = $('#uucss-wrapper li.uucss-rule-history');
                            if($container.hasClass('multi-select')){
                                $container.removeClass('multi-select');
                            }
                            if($container.find('table tbody tr').hasClass('selected')){
                                $container.find('table tbody tr').removeClass('selected')
                            }
                            $container.find('table tbody tr').addClass('selected')
                            $container.addClass('multi-select');
                            $container.find('.multiple-selected-text .multiple-selected-value').text('(' + $container.find('table tbody tr.selected').length + ') URLs');
                            break;
                        }
                        case 'export_all':{

                            wp.ajax.post('get_all_rules',{ nonce : window.uucss.nonce}).then(function (i) {
                                if(i){
                                    var exportLink = document.createElement('a');
                                    exportLink.download = 'rapidload-rules-' + Date.now();
                                    exportLink.href = 'data:text/plain;charset=utf-8,' + JSON.stringify(i) ;
                                    exportLink.style.display = "none";
                                    exportLink.click()
                                }
                            }).fail(function (i) {

                            });
                            break;
                        }
                        case 'import_all':{

                            var importInput = document.createElement('input');
                            importInput.type = 'file';
                            importInput.addEventListener('change', function (e){
                                var fileReader = new FileReader();
                                fileReader.onload = function(){
                                    wp.ajax.post('upload_rules',{
                                        nonce : window.uucss.nonce,
                                        rules : fileReader.result
                                    }).then(function (i) {
                                        $.uucssAlert(i)
                                    }).fail(function (i) {
                                        $.uucssAlert(i, 'Error')
                                    });
                                }
                                fileReader.readAsText(this.files[0]);
                            })
                            importInput.click();
                            break;
                        }
                        default:{
                            break;
                        }
                    }
                })
            },
            placement: 'bottom-end',
        })

        function uucss_purge_url(url , isClear, row, index, data, args = {}) {

            var _row = !args.rule ? table.row(index) : rule_table.row(index);

            var $row  = $(row);

            $row.addClass('loading');

            if(!args.rule){
                $uucss_spinner.addClass('loading');
            }else{
                $uucss_rule_spinner.addClass('loading');
            }

            if (!isClear) {
                $(this).hide();
            }

            var _data = {
                url: data.url,
                clear: isClear,
                nonce: window.uucss.nonce,
            }

            if(args.rule && args.regex){
                _data.rule = args.rule
                _data.regex = args.regex
            }

            if(args.immediate){
                _data.immediate = true;
            }

            if(_data.clear || _data.immediate){
                if(_data.rule && _data.regex){
                    _data.job_type = 'rule'
                }else if(_data.url){
                    _data.job_type = 'url'
                }
            }



            $.ajax({
                method : 'POST',
                url: wp.ajax.settings.url + '?action=rapidload_purge_all',
                data : _data,
                success : function(response){

                    if(!args.rule){
                        $uucss_spinner.removeClass('loading')
                    }else{
                        $uucss_rule_spinner.removeClass('loading');
                    }


                    if(response.success){

                        if (isClear) {
                            (_row.length>0) && _row.remove().draw();
                        }else{
                            data.status = 'queued';
                            _row.data(data).draw(false);
                        }
                    }

                },
                complete:function () {
                    $row.removeClass('loading')
                }
            });
        }

        function refreshTable(){

            if(!auto_refresh ||
                $('.tippy-content').length ||
                $('#uucss-wrapper .uucss-job-history select:focus').length ||
                $('html.with-featherlight').length ||
                $('#uucss-wrapper li.uucss-job-history').hasClass('multi-select')){
                return;
            }

            $uucss_spinner.addClass('loading')
            table.ajax.reload(null, false);
        }

        function refreshRulesTable(){

            if(!auto_refresh_rule ||
                $('.tippy-content').length ||
                $('#uucss-wrapper .uucss-rule-history select:focus').length ||
                $('html.with-featherlight').length ||
                $('#uucss-wrapper li.uucss-rule-history').hasClass('multi-select')){
                return;
            }

            $uucss_rule_spinner.addClass('loading')
            rule_table.ajax.reload(null, false);
        }

        function validateJobPerQue(value, reset) {

            var max = $('#uucss_queue_interval option[value="'+ value +'"]').data('max');

            var options = $('#uucss_jobs_per_queue option');

            $.each(options, function (element) {
                $(options[element]).attr('disabled', $(options[element]).val() > max)
            });
           if(reset){
               $('#uucss_jobs_per_queue').val($(options[0]).val());
           }
        }

        $('#uucss_queue_interval').change(function () {
             validateJobPerQue($(this).val(), true)
        });

        validateJobPerQue($('#uucss_queue_interval').val(), false);

        $('#uucss-deactivate').click(function (e) {
            e.preventDefault()
            let $this = $(this)
            $this.text('deactivating...');

            wp.ajax.post('uucss_deactivate', { nonce : window.uucss.nonce }).done(function (r) {
                $this.text('deactivated');
                window.location.reload()
            })
        });

        $('a.connect-with-license').click(function (e) {
            e.preventDefault();
        });

        tippy('a.connect-with-license', {
            allowHTML: true,
            arrow: false,
            appendTo: $('a.connect-with-license')[0],
            interactive: true,
            animation: 'shift-toward',
            placement: 'top-start',
            trigger: 'click',
            hideOnClick: false,
            theme: 'light',
            maxWidth: 500,
            onClickOutside(instance, event) {
                instance.hide()
            },
            content: function () {
                var content;
                content = '<div class="tippy-connect-with-license">' +
                    '               <div class="tippy-connect-with-license-content">' +
                    '                   <div class="header-text">' +
                    '                       <p>Enter your License Key below</p>' +
                    '                   </div>' +
                    '                   <div class="input-wrap">' +
                    '                       <input type="text"  placeholder="License Key" class="uucss-key">' +
                    '                       <a href="#" class="connect">Connect</a>' +
                    '                   </div>' +
                    '                   <div><p class="uucss-key-error"></p></div>' +
                    '               </div>' +
                    '          </div>';
                return content;
            },
            onMount(instance){
                $('a.connect-with-license .tippy-connect-with-license-content input.uucss-key').focus();
                $('a.connect-with-license .tippy-connect-with-license-content .input-wrap .connect').click(function (e) {
                    e.preventDefault();

                    var license_key = $('a.connect-with-license .tippy-connect-with-license-content .input-wrap input').val();

                    if(license_key === ''){
                        $.uucssAlert('Please enter a license key', 'error');
                        return;
                    }

                    var $target = $(this);

                    $target.text('Connecting...');
                    $target.removeAttr('href');

                    wp.ajax.post('uucss_connect',{ license_key : license_key, nonce : window.uucss.nonce }).then(function (i) {

                        if(i.success){
                            window.location.href = window.location.href + '&token=' + license_key + '&nonce=' + i.activation_nonce
                        }

                    }).fail(function (i) {

                        $target.text('Connect');
                        $target.attr('href','#');
                        $('a.connect-with-license p.uucss-key-error').text(i);

                    })

                })
            }
        });

        function isUrl(s) {
            var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
            return regexp.test(s);
        }

        $('#model-requeue-post-type').change(function () {
            $model_content = $('.featherlight.add-site-url-model');

            if($(this).val() === 'site_map' || $(this).val() === 'url'){
                if($(this).val() === 'site_map'){
                    $model_content.find('input.site-map-url').attr('placeholder', $model_content.find('input.site-map-url').data('sitemap_url'));
                    $model_content.find('input.site-map-url').val($model_content.find('input.site-map-url').data('sitemap_url'));
                }else if($(this).val() === 'url'){
                    $model_content.find('input.site-map-url').attr('placeholder', $model_content.find('input.site-map-url').data('site_url'));
                }
                !$model_content.find('input.site-map-url').hasClass('show') && $model_content.find('input.site-map-url').addClass('show')
                !$model_content.hasClass('show-url') && $model_content.addClass('show-url')
            }else{
                $model_content.find('input.site-map-url').hasClass('show') && $model_content.find('input.site-map-url').removeClass('show')
                $model_content.hasClass('show-url') && $model_content.removeClass('show-url')
            }
        });

        $('#model-update-rule').click(function(){
            $model_content = $('.featherlight #add_rule_featherlight_content');

            var $rule = $model_content.find('#model-uucss-rules')
            var $url = $model_content.find('.rule-base-url')
            var $regex = $model_content.find('.rule-url-regex')
            var $regenerate = $model_content.find('#force-requeue-rule')

            if($rule.val() === "" || $url.val() === "" || $regex.val() === ""){
                $.uucssAlert('Required fields missing', 'error');
                return;
            }

            if(!isUrl($url.val())){
                $.uucssAlert('Invalid Url', 'error');
                return;
            }

            var $target = $(this);

            $target.attr('disabled', true);
            $target.val('Please wait....');

            wp.ajax.post('uucss_update_rule',{
                rule : $rule.val(),
                url : $url.val(),
                regex : $regex.val(),
                old_rule : $model_content.data('old_rule'),
                old_url : $model_content.data('old_base_url'),
                old_regex : $model_content.data('old_rule_regex'),
                requeue : $regenerate.is(':checked') ? "1" : "0",
                nonce : window.uucss.nonce
            }).then(function (i) {
                $.uucssAlert(i);
                var currentFeather = $.featherlight.current();
                if(currentFeather) currentFeather.close();
                $target.attr('disabled', false);
                $target.val('Update Rule');
            }).fail(function (i) {
                $.uucssAlert(i, 'error');
                $target.attr('disabled', false);
                $target.val('Update Rule');
            }).done(function () {
                rule_table.ajax.reload(null, false);
            })
        })

        $('#model-queue-posts-type').click(function () {
            $model_content = $('.featherlight #add_url_featherlight_content');

            if(($model_content.find('#model-requeue-post-type').val() === 'site_map' || $model_content.find('#model-requeue-post-type').val() === 'url')
                && ($model_content.find('input.site-map-url').val() === "" || $model_content.find('input.site-map-url').val() === undefined)){
                $.uucssAlert('Add a valid URL', 'error');
                return;
            }

            if(($model_content.find('#model-requeue-post-type').val() === 'site_map' || $model_content.find('#model-requeue-post-type').val() === 'url')
                && !isUrl($model_content.find('input.site-map-url').val())){
                $.uucssAlert('Add a valid URL', 'error');
                return;
            }

            var $target = $(this);

            $target.attr('disabled', true);
            $target.val('Please wait....');

            var data_ = {
                post_type : $model_content.find('#model-requeue-post-type').val(),
                job_type : $model_content.find('#model-requeue-post-type').val(),
                url : $model_content.find('input.site-map-url').val(),
                nonce : window.uucss.nonce,
            }

            wp.ajax.post('rapidload_purge_all',data_).then(function (i) {
                $.uucssAlert('Sitemap links scheduled to be added to the queue.');
                var currentFeather = $.featherlight.current();
                if(currentFeather) currentFeather.close();
                $target.attr('disabled', false);
                $target.val('Add');
            }).fail(function (i) {
                $.uucssAlert(i, 'error');
                $target.attr('disabled', false);
                $target.val('Add');
            }).done(function () {
                table.ajax.reload(null, false);
            })

            //wp.ajax.post('cpcss_purge_url',{ url : $model_content.find('input.site-map-url').val(), post_type : $model_content.find('#model-requeue-post-type').val() });
        });

        $('p.more-info-uucss-status').click(function (e) {
            e.preventDefault();
            var $info = $('.rapidload-status .uucss-status-more-info');
            if($info.css('display') === "block"){
                $info.slideUp();
            }else{
                $info.slideDown();
            }

        });

        $('#js-uucss-clear-selection').click(function (e) {
           e.preventDefault();
           $('#uucss-history tbody tr').removeClass('selected');
           $('#uucss-wrapper li.uucss-history.uucss-job-history').removeClass('multi-select');
        });

        $('#js-uucss-clear-selection-rule').click(function (e) {
            e.preventDefault();
            $('#uucss-rule-history tbody tr').removeClass('selected');
            $('#uucss-wrapper li.uucss-history.uucss-rule-history').removeClass('multi-select');
        });

        updateNotices();

        var $updateRuleForm = $('#add_rule_featherlight_content');

        /*$updateRuleForm.find('select').change(function(){
            var permalink = $updateRuleForm.find('option[data-type="'+ $(this).val() + '"]').data('permalink')
            if(permalink){
                $('#add_rule_featherlight_content input.rule-base-url').focus().val('').val(permalink);
            }
        })

        $updateRuleForm.find('input.rule-base-url').val($updateRuleForm.find('option[data-type="'+ $updateRuleForm.find('select').val() + '"]').data('permalink'));
*/

        updateSitemapUrl();
    });

    function updateNotices() {
        wp.ajax.post('rapidload_notifications', { nonce : window.uucss.nonce }).then(function (response) {
            if(response){
                window.uucss.faqs = response.faqs;
                window.uucss.public_notices = response.notifications;
            }
            showFaqs();
            showPublicNotices();
        });
    }


}(jQuery))