<?php

defined( 'ABSPATH' ) or die();

global $post;
?>

<style>

    li#wp-admin-bar-autoptimize-uucss {
        display: inline-block !important;
        padding: 12px 0 5px !important;
        background: #01579B !important;
        color: white;
        cursor: ;
    }

    .uucss-stats a.rapidload {
        line-height: 0 !important;
        display: inline-block !important;
        margin-top: 2px !important;
        margin-left: -10px !important;
        cursor: pointer !important;
        height: 0 !important;
    }

    .uucss-stats .hidden {
        display: none;
    }

    li#wp-admin-bar-autoptimize-uucss a{
        cursor: default;
        height: auto;
    }

    li#wp-admin-bar-autoptimize-uucss a:hover, a.ab-item:hover{
        color : rgba(240, 245, 250, 0.7) !important;
    }

    .uucss-stats {
        line-height: 0 !important;
    }

    span.uucss-stats__size {
        font-size: .7em !important;
    }

    .uucss-stats__actions div {
        display: inline-block;
        padding: 0 7px !important;
        background: #009688;
        line-height: 1.5 !important;
        border-radius: 3px !important;
        margin-right: 3px !important;
        cursor: pointer;
    }


</style>
<div class="uucss-stats">
    <script>
        if (!window.uucss) {
            window.uucss = {}
        }

        window.uucss.nonce = '<?php echo esc_js(current_user_can('manage_options') ? wp_create_nonce( 'uucss_nonce' ) : ''); ?>';
    </script>
    <a class="rapidload" href="<?php echo esc_url(admin_url( 'options-general.php?page=rapidload' )) ?>">RapidLoad</a>
    <div class="uucss-stats__stats">
        <span class="uucss-stats__size">Size: <?php echo esc_html($this->size()); ?></span>
    </div>
    <div class="uucss-stats__actions">

		<?php if ( $post ) {
			$exists =  new RapidLoad_Job([
                'url' => get_permalink( $post )
            ]);
            ?>
            <div id="button-uucss-clear" <?php if ( ! isset($exists->id) ) echo 'class="hidden"' ?> title="clear page cache">remove
            </div>
            <div id="button-uucss-purge" <?php if ( isset($exists->id) ) echo 'class="hidden"' ?> title="generate page cache">optimize
            </div>
		<?php } ?>
        <div id="button-uucss-clear-all" title="remove all RapidLoad optimizations">remove all</div>
    </div>

</div>

<script>

    (function ($) {


        <?php if($post) : ?>

        $('#button-uucss-purge').click(function (e) {
            e.preventDefault();
            var $this = $(this);

            $this.text('loading...');

            var data = {
                url: '<?php echo esc_url(get_permalink($post)) ?>',
                nonce: window.uucss.nonce,
                args: {
                    post_id: <?php echo esc_js($post->ID) ?>
                }
            }

            wp.ajax.post('rapidload_purge_all', data).done(function (d) {
                $this.text('generate')
                $this.hide();
                $('#button-uucss-clear').css('display', 'inline-block')
            }).fail(function () {
                $this.text('failed!')
            });
        });

        $('#button-uucss-clear').click(function (e) {
            e.preventDefault();

            var $this = $(this);

            $this.text('loading...');
            wp.ajax.post('rapidload_purge_all', {
                clear: true,
                url: '<?php echo esc_url(get_permalink($post)) ?>',
                nonce: window.uucss.nonce,
                args: {
                    post_id: <?php echo esc_js($post->ID) ?>
                }
            }).done(function (d) {
                $this.text('clear')
                $this.hide();
                $('#button-uucss-purge').css('display', 'inline-block')
            })

        });

        <?php endif ?>

        $('#button-uucss-clear-all').click(function (e) {
            e.preventDefault();

            var $this = $(this);

            $this.text('loading...');
            wp.ajax.post('rapidload_purge_all', {
                clear: true,
                nonce: window.uucss.nonce,
                url: null,
            }).done(function (d) {
                $this.text('cleared all')
                $('#button-uucss-purge').css('display', 'inline-block')
                $('#button-uucss-clear').hide()
                $('.uucss-stats__size').text('Total Size : 0.00KB')

            })

        });

    })(jQuery)

</script>