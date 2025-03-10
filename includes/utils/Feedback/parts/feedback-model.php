<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div class="uucss-plugin-uninstall-feedback-popup uucss-feedback" id="uucss_uninstall_feedback_popup">
    <div class="popup--header">
        <h5> Help us make the plugin better! </h5>
    </div><!--/.popup--header-->
    <div class="popup--body">
        <ul class="popup--form">
            <li uucss-option-id="1">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback1">
                <label for="uucss_feedback1">
                    It's a temporary deactivation. I'm just debugging an issue. </label>
            </li>
            <li uucss-option-id="2">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback2">
                <label for="uucss_feedback2">
                    Breaks the site or Plugin conflicts </label>
            </li>
            
            <li uucss-option-id="3">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback3">
                <label for="uucss_feedback3">
                    Doesn't meet my expectation</label>
            </li>
            <li uucss-option-id="4">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback4">
                <label for="uucss_feedback4">
                    I am not willing to pay for this plugin<br>
                    <!-- <a href="https://rapidload.io/referral-free-month/?utm_source=rapidload_plugin&utm_medium=plugin-feedback" target="_blank" style="font-size: 10px" id="rapidload-free-month-link">
                        Get a <strong style="display: inline;font-size: 11px">FREE</strong> month of <strong style="display: inline;font-size: 11px">RapidLoad</strong>
                    </a> -->
                </label>
            </li>
            <li uucss-option-id="5">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback5">
                <label for="uucss_feedback5">
                    Technical difficulties </label>
            </li>
            <li uucss-option-id="6">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback6">
                <label for="uucss_feedback6">
                    RapidLoad is slowing down my site </label>
            </li>
            <li uucss-option-id="7">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback7">
                <label for="uucss_feedback7">
                    Switched to an alternative plugin </label>
            </li>
            <li uucss-option-id="999">
                <input type="radio" name="uucss-deactivate-option" id="uucss_feedback999">
                <label for="uucss_feedback999">
                    Other </label>
                <textarea width="100%" rows="2" name="comments" placeholder="What can we do better?"></textarea>
            </li>
        </ul>
    </div><!--/.popup--body-->
    <div class="popup--footer">
        <div class="actions">
            <a href="#" class="info-disclosure-link">What info do we collect?</a>
            <div class="info-disclosure-content"><p>Below is a detailed view of all data that RapidLoad will receive if
                    you fill in this survey. Email address or IP addresses will not be sent.</p>
                <ul>
                    <li><strong>Plugin version </strong> <code
                                id="uucss_plugin_version"> <?php echo UUCSS_VERSION ?> </code></li>
                    <li><strong>Current website:</strong> <code> <?php echo trailingslashit(get_site_url()) ?> </code></li>
                    <li><strong>Uninstall reason </strong> <i> Selected reason from the above survey </i></li>
                </ul>
            </div>
            <div class="buttons">
                <input type="submit"
                       name="uucss-deactivate-no"
                       id="uucss-deactivate-no"
                       class="button"
                       value="Skip &amp; Deactivate">
                <input type="submit"
                       name="uucss-deactivate-yes"
                       id="uucss-deactivate-yes"
                       class="button button-primary"
                       value="Submit &amp; Deactivate"
                       data-after-text="Submit &amp; Deactivate"
                       disabled="1"></div>

        </div><!--/.actions-->
    </div><!--/.popup--footer-->
</div>
