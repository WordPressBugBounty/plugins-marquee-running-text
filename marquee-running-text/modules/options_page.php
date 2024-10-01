<?php
function mrtext_menu_item()
{
    add_menu_page(
        "Add Marquee",
        "Add Marquee",
        "manage_options",
        "mrtext-settings",
        "mrtext_settings_page",
        "dashicons-buddicons-groups",
        100
    );
    add_submenu_page(
        "mrtext-settings", // Parent menu slug
        "Marquee Style", // Page title
        "Marquee Style", // Menu title
        "manage_options", // Capability required to access the page
        "mrtext_style", // Menu slug
        "mrtext_style_page", // Callback function to render the submenu page
    );
}

add_action("admin_menu", "mrtext_menu_item");

?>