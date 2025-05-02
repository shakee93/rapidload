<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<h2>Slash load times and boost PageSpeed scores by loading only CSS you need.</h2>
<div class="content"
     style="background-image: url('<?php echo esc_url(RAPIDLOAD_PLUGIN_URL . 'assets/images/intro.svg') ?>')">
    <div class="uucss-row">
        <div class="left-section">
            <p>
                <strong>Slow load times</strong> are the <strong>#1</strong> reason for <strong>high
                    bounce
                    rates</strong> and one of the root causes of poor <strong>Google
                    Rankings</strong>.
            </p>
            <p>
                Up to 95% of each CSS file in Wordpress themes and plugins is loaded and never used.
                That translates to anywhere from 1 to 4 additional seconds of load time.
                <strong>That's up to 4 additional seconds to load completely unnecessary
                    code!</strong>
            </p>
            <p>
                By analyzing each page and excluding unnecessary CSS, RapidLoad instantly reduces
                load
                times by <strong> up to 50%, boosts Boosts Google PageSpeed scores,</strong> and
                <strong>improves user
                    experience.</strong> Best of all, it's totally automated. No combing through
                countless files, no matching up styles to elements - just enable RapidLoad and see
                the
                benefits immediately!
            </p>
            <p>
                When the plugin is activated. you will start seeing results
                <strong>automatically</strong> when you or the users start visiting the pages.
            </p>
        </div>
        <div class="right-section" style="visibility: hidden">
            <img>
        </div>
    </div>
    <div class="uucss-row flex-direction-column">
        <a href="https://rapidload.io/?utm_source=rapidload_plugin&utm_medium=plugin_onboard" class="uucss-activate" target="_blank">Get RapidLoad</a>
        <div class="connect-action-buttons">
            <a href="<?php echo esc_url(RapidLoad_Base::activation_url( 'authorize' )) ?>" class="connect">Connect</a>
            <a href="#" class="connect-with-license">Connect with License Key</a>
        </div>
    </div>
</div>