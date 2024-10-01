<?php
function mrtext_settings_page()
{
    ?>
<div class="wrap">
    <h1><?php print esc_html("Add Marquee"); ?></h1>
</div>
<!-- Tab -->
<h2 class="nav-tab-wrapper">
    <a href="?page=mrtext-settings" class="nav-tab nav-tab-active"><?php print esc_html(
        "Add Marquee"
    ); ?></a>
    <a href="?page=mrtext_style" class="nav-tab"><?php print esc_html(
        "Marquee Style"
    ); ?></a>
</h2>
<!-- Input Form -->
<div class="container">
    <div class="left-column">
        <form method="post" action="options.php">
            <?php wp_nonce_field("update-options"); ?>
            <h2><?php print esc_html("Marquee 1"); ?></h2>
            <table class="form-table">
                <!--- Text Area Field 1 --->
                <tr>
                    <th scope="row"><label for="mrtext_text_field_1"><?php print esc_html(
                "Text Area "
            ); ?></label></th>
                    <td><textarea id="mrtext_text_field_1" name="mrtext_text_field_1" rows="5" cols="50"><?php print esc_textarea(
                        get_option("mrtext_text_field_1", "Text 1")
                    ); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="mrtext_text_field_1_link" name="mrtext_text_field_1_link"><?php print esc_html(
                        "Link"
                    ); ?></label></th>
                    <td><input type="text" id="mrtext_text_field_1_link" name="mrtext_text_field_1_link" value="<?php print esc_attr(
                        get_option("mrtext_text_field_1_link", "#")
                    ); ?>" class="color-picker">
                    </td>
                </tr>
            </table>
            <hr>
            <h2><?php print esc_html("Marquee 2"); ?></h2>
            <table class="form-table">
                <!--- Text Area Field 2 --->
                <tr>
                    <th scope="row"><label for="mrtext_text_field_2"><?php print esc_html(
                "Text Area "
            ); ?></label></th>
                    <td><textarea id="mrtext_text_field_2" name="mrtext_text_field_2" rows="5" cols="50"><?php print esc_textarea(
                        get_option("mrtext_text_field_2", "Text 2")
                    ); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="mrtext_text_field_2_link" name="mrtext_text_field_2_link"><?php print esc_html(
                        "Link"
                    ); ?></label></th>
                    <td><input type="text" id="mrtext_text_field_2_link" name="mrtext_text_field_2_link" value="<?php print esc_attr(
                        get_option("mrtext_text_field_2_link", "#")
                    ); ?>" class="color-picker">
                    </td>
                </tr>
            </table>
            <hr>
            <h2><?php print esc_html("Marquee 3"); ?></h2>
            <table class="form-table">
                <!--- Text Area Field 3 --->
                <tr>
                    <th scope="row"><label for="mrtext_text_field_3"><?php print esc_html(
                "Text Area "
            ); ?></label></th>
                    <td><textarea id="mrtext_text_field_3" name="mrtext_text_field_3" rows="5" cols="50"><?php print esc_textarea(
                        get_option("mrtext_text_field_3", "Text 3")
                    ); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="mrtext_text_field_3_link" name="mrtext_text_field_3_link"><?php print esc_html(
                        "Link"
                    ); ?></label></th>
                    <td><input type="text" id="mrtext_text_field_3_link" name="mrtext_text_field_3_link" value="<?php print esc_attr(
                        get_option("mrtext_text_field_3_link", "#")
                    ); ?>" class="color-picker">
                    </td>
                </tr>
            </table>
            <hr>
            <h2><?php print esc_html("Marquee 4"); ?></h2>
            <table class="form-table">
                <!--- Text Area Field 4 --->
                <tr>
                    <th scope="row"><label for="mrtext_text_field_4"><?php print esc_html(
                "Text Area "
            ); ?></label></th>
                    <td><textarea id="mrtext_text_field_4" name="mrtext_text_field_4" rows="5" cols="50"><?php print esc_textarea(
                        get_option("mrtext_text_field_4", "Text 4")
                    ); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="mrtext_text_field_4_link" name="mrtext_text_field_4_link"><?php print esc_html(
                        "Link"
                    ); ?></label></th>
                    <td><input type="text" id="mrtext_text_field_4_link" name="mrtext_text_field_4_link" value="<?php print esc_attr(
                        get_option("mrtext_text_field_4_link", "#")
                    ); ?>" class="color-picker">
                    </td>
                </tr>
            </table>
            <hr>
            <h2><?php print esc_html("Marquee 5"); ?></h2>
            <table class="form-table">
                <!--- Text Area Field 5 --->
                <tr>
                    <th scope="row"><label for="mrtext_text_field_5"><?php print esc_html(
                "Text Area "
            ); ?></label></th>
                    <td><textarea id="mrtext_text_field_5" name="mrtext_text_field_5" rows="5" cols="50"><?php print esc_textarea(
                        get_option("mrtext_text_field_5", "Text 5")
                    ); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="mrtext_text_field_5_link" name="mrtext_text_field_5_link"><?php print esc_html(
                        "Link"
                    ); ?></label></th>
                    <td><input type="text" id="mrtext_text_field_5_link" name="mrtext_text_field_5_link" value="<?php print esc_attr(
                        get_option("mrtext_text_field_5_link", "#")
                    ); ?>" class="color-picker">
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="page_options"
                value="mrtext_text_field_1,mrtext_text_field_2,mrtext_text_field_3,mrtext_text_field_4,mrtext_text_field_5,mrtext_text_field_1_link,mrtext_text_field_2_link,mrtext_text_field_3_link,mrtext_text_field_4_link,mrtext_text_field_5_link">
            <p class="submit"><input type="submit" name="submit" class="button-primary"
                    value="<?php esc_html_e("Save Changes", "mrtext"); ?> "></p>
        </form>
    </div>
    <div class="right-column">
        <?php
    require_once('author_profile.php');
    ?>
    </div>
<?php
}

// Callback function to render the Marquee Style page
function mrtext_style_page()
{
    ?>
<div class="wrap">
    <h1><?php print esc_html("Marquee Style"); ?></h1>
</div>
<!-- Tab -->
<h2 class="nav-tab-wrapper">
    <a href="?page=mrtext-settings" class="nav-tab"><?php print esc_html(
        "Add Marquee"
    ); ?></a>
    <a href="?page=mrtext_style" class="nav-tab nav-tab-active"><?php print esc_html(
        "Marquee Style"
    ); ?></a>
</h2>
<!-- Style Form -->
<div class="container">
    <div class="left-column">
        <form method="post" action="options.php">
            <?php wp_nonce_field("update-options"); ?>
            <div>
                <h2><?php print esc_html("Color Options"); ?></h2>
                <table class="form-table">
                    <!--- Background Color Option --->
                    <tr>
                        <th scope="row"><label for="mrtext_bg_color_option" name="mrtext_bg_color_option"><?php print esc_html(
                            "Background Color"
                        ); ?></label></th>
                        <td><input type="color" id="mrtext_bg_color_option" name="mrtext_bg_color_option" value="<?php print esc_attr(
                            get_option("mrtext_bg_color_option", "#000000")
                        ); ?>" class="color-picker"></td>
                    </tr>
                    <!--- Text Color Option --->
                    <tr>
                        <th scope="row"><label for="mrtext_color_option" name="mrtext_color_option"><?php print esc_html(
                            "Text Color"
                        ); ?></label></th>
                        <td><input type="color" id="mrtext_color_option" name="mrtext_color_option" value="<?php print esc_attr(
                            get_option("mrtext_color_option", "#ffffff")
                        ); ?>" class="color-picker"></td>
                    </tr>
                    <!--- Text Hover Color Option --->
                    <tr>
                        <th scope="row"><label for="mrtext_hover_color_option" name="mrtext_hover_color_option"><?php print esc_html(
                            "Text Hover Color"
                        ); ?></label></th>
                        <td><input type="color" id="mrtext_hover_color_option" name="mrtext_hover_color_option" value="<?php print esc_attr(
                            get_option("mrtext_hover_color_option", "#ffffff")
                        ); ?>" class="color-picker"></td>
                    </tr>
                </table>
            </div>
            <hr>
            <div>
                <h2><?php print esc_html("Typography & Others"); ?></h2>
                <table class="form-table">                    
                    <!--- Font Size --->
                    <tr>
                        <th scope="row"><label for="mrtext_font_size" name="mrtext_font_size"><?php print esc_html(
                            "Font Size"
                        ); ?></label></th>
                        <td><input type="text" id="mrtext_font_size" name="mrtext_font_size" value="<?php print esc_attr(
                            get_option("mrtext_font_size", "16px")
                        ); ?>" class="color-picker">
                        </td>
                    </tr>
                    <!--- Font Weight --->
                    <tr>
                        <th scope="row"><label for="mrtext_font_weight" name="mrtext_font_weight"><?php print esc_html(
                            "Font Weight"
                        ); ?></label></th>
                        <td><input type="text" id="mrtext_font_weight" name="mrtext_font_weight" value="<?php print esc_attr(
                            get_option("mrtext_font_weight", "500")
                        ); ?>" class="color-picker">
                        </td>
                    </tr>
                    <!--- Font Direction --->
                    <tr>
                        <th scope="row"><label for="mrtext_font_direction" name="mrtext_font_direction"><?php print esc_html(
                            "Font Direction"
                        ); ?></label></th>
                        <td><input type="text" placeholder="right or left" id="mrtext_font_direction" name="mrtext_font_direction" value="<?php print esc_attr(
                            get_option("mrtext_font_direction")
                        ); ?>" class="color-picker"></td>
                    </tr>
                    <!--- Scroll Delay --->
                    <tr>
                        <th scope="row"><label for="mrtext_scroll_delay" name="mrtext_font_scroll_delay"><?php print esc_html(
                            "Scroll Delay"
                        ); ?></label></th>
                        <td><input type="text" id="mrtext_font_scroll_delay" name="mrtext_font_scroll_delay" value="<?php print esc_attr(
                            get_option("mrtext_font_scroll_delay", "85")
                        ); ?>" class="color-picker"></td>
                    </tr>
                </table>
            </div>
            <!--- Marquee Header Show/Hide --->

            <?php $current_value = get_option("mrtext_radio", "show"); ?>
            <hr>
            <div>
                <h2><?php print esc_html("Header Marquee"); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="mrtext_radio_show">Show</label></th>
                        <td><input id="mrtext_radio_show" type="radio" name="mrtext_radio" value="show"
                                <?php checked("show", $current_value); ?>></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="mrtext_radio_show">Hide</label></th>
                        <td><input id="mrtext_radio_hide" type="radio" name="mrtext_radio" value="Hide"
                                <?php checked("Hide", $current_value); ?>></td>
                    </tr>
                </table>
            </div>
            <!--- Marquee Header Before/After --->
            <?php $current_value = get_option("mmrtext_radio_before_after", "before"); ?>
            <hr>
            <div>
                <h2><?php print esc_html("Header Marquee"); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="mrtext_radio_before">Before</label></th>
                        <td><input id="mrtext_radio_before" type="radio" name="mrtext_radio_before_after" value="before"
                                <?php checked("before", $current_value); ?>></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="mrtext_radio_show">After</label></th>
                        <td><input id="mrtext_radio_after" type="radio" name="mrtext_radio_before_after" value="after"
                                <?php checked("after", $current_value); ?>></td>
                    </tr>
                </table>
            </div>

            <input type="hidden" name="action" value="update">
            <input type="hidden" name="page_options"
                value="mrtext_bg_color_option,mrtext_color_option,mrtext_hover_color_option,mrtext_font_direction,mrtext_font_size,mrtext_font_weight,mrtext_font_scroll_delay,mrtext_radio">
            <p class="submit"><input type="submit" name="submit" class="button-primary"
                    value="<?php esc_html_e("Save Changes", "mrtext"); ?> "></p>
        </form>
    </div>
    <div class="right-column">
        <?php
    require_once('author_profile.php');
    ?>
    </div>

<?php
}