<tr>
    <th class="sub-heading">
        <h4>JS Optimization</h4>
    </th>
</tr>
<tr>
    <th scope="row"><?php esc_html_e( 'Enable Javascript Optimization', 'unusedcss' ); ?></th>
    <td>
        <label><input id='uucss_enable_javascript' type='checkbox'
                      name='autoptimize_uucss_settings[uucss_enable_javascript]' <?php if ( ! empty( $options['uucss_enable_javascript'] ) && '1' === $options['uucss_enable_javascript'] ) {
                echo 'checked="checked"';
            } ?> value='1'>
            <i>
                Enable to optimize javascript.
            </i>
        </label>
    </td>
</tr>
<tr>
    <th>
        <?php esc_html_e( 'Load JS', 'unusedcss' ); ?>
    </th>
    <td>

        <select name="autoptimize_uucss_settings[uucss_load_js_method]" id="uucss_load_js_method">
            <option value="none" <?php if($options['uucss_load_js_method'] === 'none') {  echo 'selected'; } else {echo '';} ?>>None</option>
            <option value="defer" <?php if($options['uucss_load_js_method'] === 'defer') {  echo 'selected'; } else {echo '';} ?>>Defer</option>
            <option value="on-user-interaction" <?php if($options['uucss_load_js_method'] === 'on-user-interaction') {  echo 'selected'; } else {echo '';} ?>>On User Interaction</option>
        </select>

    </td>
</tr>
<tr>
    <th scope="row"><?php esc_html_e( 'Defer inline Javascript', 'unusedcss' ); ?></th>
    <td>
        <label><input id='defer_inline_js' type='checkbox'
                      name='autoptimize_uucss_settings[defer_inline_js]' <?php if ( ! empty( $options['defer_inline_js'] ) && '1' === $options['defer_inline_js'] ) {
                echo 'checked="checked"';
            } ?> value='1'>
        </label>
    </td>
</tr>
<tr>
    <th scope="row"><?php esc_html_e( 'Minify Javsacript', 'unusedcss' ); ?></th>
    <td>
        <label><input id='minify_js' type='checkbox'
                      name='autoptimize_uucss_settings[minify_js]' <?php if ( ! empty( $options['minify_js'] ) && '1' === $options['minify_js'] ) {
                echo 'checked="checked"';
            } ?> value='1'>
        </label>
    </td>
</tr>
<tr>
    <th scope="row"><?php esc_html_e( 'Exclude JS', 'unusedcss' ); ?>
        <span class="exclude-links has-tooltip"
              data-message="Exclude from RapidLoad hello-url/some-url">

                                    </span></th>
    <td>
        <div class="" id="uucss_excluded_js_files">
            <div class="">
                <div class="">
                    <p><textarea name="autoptimize_uucss_settings[uucss_excluded_js_files]"
                                 style="max-width: 390px; width: 100%; height: 100px"
                                 class="the-tags"
                                 aria-describedby="new-tag-post_tag-desc"><?php echo empty( $options['uucss_excluded_js_files'] ) ? '' : $options['uucss_excluded_js_files'] ?></textarea>
                    </p>
                </div>
                <p class="howto">
                    Exclude JS from RapidLoad <em> enter each file in new line </em>
                </p>
            </div>
        </div>
    </td>
</tr>