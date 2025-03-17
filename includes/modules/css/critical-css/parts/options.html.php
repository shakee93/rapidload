<tr>
    <th scope="row"><?php esc_html_e( 'Enable Critical CSS', 'unusedcss' ); ?></th>
    <td>
        <label><input id='uucss_enable_cpcss' type='checkbox'
                      name='autoptimize_uucss_settings[uucss_enable_cpcss]' <?php if ( ! empty( $options['uucss_enable_cpcss'] ) && '1' === $options['uucss_enable_cpcss'] ) {
                echo 'checked="checked"';
            } ?> value='1' <?php if(!empty(CriticalCSS::$cpcss_other_plugins)) { echo 'disabled'; } ?>>
            <i>
                Enable to eliminate render-blocking CSS on your website and load stylesheets asynchronously.
            </i>
            <a target="_blank" href="https://rapidload.zendesk.com/hc/en-us/articles/4404507967635-Critical-CSS" style="">learn more</a>

            <?php if(!empty(CriticalCSS::$cpcss_other_plugins) && isset(CriticalCSS::$cpcss_other_plugins[0])) :

            ?>
                <p style="color: #FFC107"> <span style="line-height: 20px" class="dashicons dashicons-info-outline"></span>
                    CSS render blocking handled by <strong><?php echo esc_html( CriticalCSS::$cpcss_other_plugins[0] ); ?></strong>.
                    Disable option in <strong><?php echo esc_html( CriticalCSS::$cpcss_other_plugins[0] ); ?></strong> to use RapidLoad critical css.
                </p>
            <?php endif; ?>
        </label>
    </td>
</tr>
<tr>
    <th scope="row"><?php esc_html_e( 'Mobile Critical CSS', 'unusedcss' ); ?></th>
    <td>
        <label><input id='uucss_enable_cpcss_mobile' type='checkbox'
                      name='autoptimize_uucss_settings[uucss_enable_cpcss_mobile]' <?php if ( ! empty( $options['uucss_enable_cpcss_mobile'] ) && '1' === $options['uucss_enable_cpcss_mobile'] ) {
                echo 'checked="checked"';
            } ?> value='1' <?php if(!empty(CriticalCSS::$cpcss_other_plugins)) { echo 'disabled'; } ?>>
            <i>
                Generate separate mobile version of critical css.
            </i>
        </label>
    </td>
</tr>
<tr>
    <th scope="row"><?php esc_html_e( 'Additional CSS', 'unusedcss' ); ?></th>
    <td>
                                    <textarea style="max-width: 390px; width: 100%; height: 150px" id="uucss_additional_css"
                                              name="autoptimize_uucss_settings[uucss_additional_css]" <?php if(!empty(CriticalCSS::$cpcss_other_plugins)) { echo 'disabled'; } ?>>
                                        <?php echo empty( $options['uucss_additional_css'] ) ? '' : esc_textarea($options['uucss_additional_css']) ?></textarea>

    </td>
</tr>