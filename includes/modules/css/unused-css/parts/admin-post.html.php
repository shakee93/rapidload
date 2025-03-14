<?php defined( 'ABSPATH' ) or die(); ?>


<div id="uucss-settings">

    <div class="uucss-fields">
		<?php wp_nonce_field( 'uucss_option_save', 'uucss_nonce' ) ?>

        <p>
            <label>
                <input id="uucss_exclude" type="checkbox"
                       name="uucss_exclude" <?php echo empty( $options['exclude'] ) ? '' : 'checked=checked' ?>>
                Exclude
            </label>
        </p>

        <p>
            <textarea hidden id="uucss_safelist"
                      name="uucss_safelist"><?php echo empty( $options['safelist'] ) ? '' : esc_textarea($options['safelist']) ?></textarea>
        <div class="safelist-add">
            <label class="" for="Safelist">Safelist</label>
            <div class="input-wrap-flex">
                <select name="" id="safelist-type">
                    <option value="single">Single</option>
                    <option value="deep">Deep</option>
                    <option selected value="greedy">Greedy</option>
                </select>
                <input id="safelist-add" type="text" size="20"
                       autocomplete="off">
            </div>
            <button class="button">Add Rule</button>
        </div>
        <div class="safelist-list">
            <ul></ul>
        </div>

        </p>
        <p>
            <textarea hidden id="uucss_blocklist"
                      name="uucss_blocklist"><?php echo empty( $options['blocklist'] ) ? '' : esc_textarea($options['blocklist']) ?></textarea>
        <div class="blocklist-add uucss-tag">
            <label class="" for="Blocklist">Blocklist</label>
            <input id="blocklist-add" type="text" size="27" class="newtag"
                   autocomplete="off">
            <button class="button">Add Rule</button>
        </div>
        <div class="blocklist-list">
            <ul></ul>
        </div>
        </p>
        <div class="uucss-info-wrapper" style="	width: calc(100% - 25px)">
            <div class="info-icon">
                <span class="dashicons dashicons-info"></span>
            </div>
            <div class="info-details">
                <p>You can add rules to specify which CSS rules to be kept in the final CSS or should be removed</p>
                <p class="divider"></p>
                <p>
                    use * expressions and add one rule at a time,
                </p>
                <p class="divider"></p>
                <p>
                    examples : <br><em>my-class*</em>, <em>*my-id</em>, <em>*li*</em><br>
                </p>
            </div>
        </div>
    </div>


</div>
