<?php
if (! defined('ABSPATH')) { exit; }
$location_modes = array(
    'physical' => __('Physical location', 'ajnanda'),
    'service_area' => __('Service-area business', 'ajnanda'),
    'regional_national' => __('Regional or national business', 'ajnanda'),
    'none' => __('No public location', 'ajnanda'),
);
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ajnanda_save_search_ai_profile">
    <?php wp_nonce_field('ajnanda_save_search_ai_profile'); ?>
    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Organization identity', 'ajnanda'); ?></h2>
        <p class="description"><?php esc_html_e('This is AJNanda’s canonical public identity for future schema, discovery files, and integrations. WordPress Site Identity values are used as defaults.', 'ajnanda'); ?></p>
        <table class="form-table" role="presentation">
            <tr><th><label for="search_ai_profile_name"><?php esc_html_e('Organization or business name', 'ajnanda'); ?></label></th><td><input class="regular-text" type="text" id="search_ai_profile_name" name="search_ai_profile_name" value="<?php echo esc_attr($profile['name']); ?>"></td></tr>
            <tr><th><label for="search_ai_profile_alternate_name"><?php esc_html_e('Alternate or legal name', 'ajnanda'); ?></label></th><td><input class="regular-text" type="text" id="search_ai_profile_alternate_name" name="search_ai_profile_alternate_name" value="<?php echo esc_attr($profile['alternate_name']); ?>"></td></tr>
            <tr><th><label for="search_ai_profile_description"><?php esc_html_e('Description', 'ajnanda'); ?></label></th><td><textarea class="large-text" rows="4" id="search_ai_profile_description" name="search_ai_profile_description"><?php echo esc_textarea($profile['description']); ?></textarea></td></tr>
            <tr><th><label for="search_ai_profile_organization_type"><?php esc_html_e('Organization type', 'ajnanda'); ?></label></th><td><select id="search_ai_profile_organization_type" name="search_ai_profile_organization_type"><?php foreach (AJNanda_Search_AI_Site_Profile::organization_types() as $value => $label) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($profile['organization_type'], $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td></tr>
            <tr><th><label for="search_ai_profile_industry"><?php esc_html_e('Industry', 'ajnanda'); ?></label></th><td><input class="regular-text" type="text" id="search_ai_profile_industry" name="search_ai_profile_industry" value="<?php echo esc_attr($profile['industry']); ?>"></td></tr>
            <tr><th><?php esc_html_e('Logo', 'ajnanda'); ?></th><td><input type="hidden" id="search_ai_profile_logo_id" name="search_ai_profile_logo_id" value="<?php echo esc_attr($profile['logo_id']); ?>"><button type="button" class="button" id="ajnanda_profile_logo_button"><?php esc_html_e('Choose Logo', 'ajnanda'); ?></button> <button type="button" class="button-link-delete" id="ajnanda_profile_logo_remove"><?php esc_html_e('Remove', 'ajnanda'); ?></button><div id="ajnanda_profile_logo_preview"><?php if ($profile['logo_url']) : ?><img src="<?php echo esc_url($profile['logo_url']); ?>" alt="" style="max-width:240px;height:auto;margin-top:12px;"><?php endif; ?></div><p class="description"><?php esc_html_e('Defaults to the WordPress custom logo.', 'ajnanda'); ?></p></td></tr>
        </table>
    </div>
    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Contact and location', 'ajnanda'); ?></h2>
        <p class="description"><?php esc_html_e('Only enter contact information that may be published as machine-readable site information.', 'ajnanda'); ?></p>
        <table class="form-table" role="presentation">
            <tr><th><label for="search_ai_profile_website"><?php esc_html_e('Website URL', 'ajnanda'); ?></label></th><td><input class="regular-text" type="url" id="search_ai_profile_website" name="search_ai_profile_website" value="<?php echo esc_url($profile['website']); ?>"></td></tr>
            <tr><th><label for="search_ai_profile_phone"><?php esc_html_e('Phone', 'ajnanda'); ?></label></th><td><input class="regular-text" type="text" id="search_ai_profile_phone" name="search_ai_profile_phone" value="<?php echo esc_attr($profile['phone']); ?>"></td></tr>
            <tr><th><label for="search_ai_profile_email"><?php esc_html_e('Public email', 'ajnanda'); ?></label></th><td><input class="regular-text" type="email" id="search_ai_profile_email" name="search_ai_profile_email" value="<?php echo esc_attr($profile['email']); ?>"></td></tr>
            <tr><th><label for="search_ai_profile_location_mode"><?php esc_html_e('Location model', 'ajnanda'); ?></label></th><td><select id="search_ai_profile_location_mode" name="search_ai_profile_location_mode"><?php foreach ($location_modes as $value => $label) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($profile['location_mode'], $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><p id="ajnanda_location_explanation" class="description"></p></td></tr>
            <tr data-location-field="address"><th><label for="search_ai_profile_address_street"><?php esc_html_e('Address', 'ajnanda'); ?></label></th><td><input class="regular-text" type="text" id="search_ai_profile_address_street" name="search_ai_profile_address_street" value="<?php echo esc_attr($profile['stored_address']['street']); ?>"></td></tr>
            <tr data-location-field="address"><th><label for="search_ai_profile_address_city"><?php esc_html_e('City', 'ajnanda'); ?></label></th><td><input type="text" id="search_ai_profile_address_city" name="search_ai_profile_address_city" value="<?php echo esc_attr($profile['stored_address']['city']); ?>"></td></tr>
            <tr data-location-field="address"><th><label for="search_ai_profile_address_state"><?php esc_html_e('State/region', 'ajnanda'); ?></label></th><td><input type="text" id="search_ai_profile_address_state" name="search_ai_profile_address_state" value="<?php echo esc_attr($profile['stored_address']['state']); ?>"></td></tr>
            <tr data-location-field="address"><th><label for="search_ai_profile_address_postal"><?php esc_html_e('ZIP/postal code', 'ajnanda'); ?></label></th><td><input type="text" id="search_ai_profile_address_postal" name="search_ai_profile_address_postal" value="<?php echo esc_attr($profile['stored_address']['postal']); ?>"></td></tr>
            <tr data-location-field="address"><th><label for="search_ai_profile_address_country"><?php esc_html_e('Country', 'ajnanda'); ?></label></th><td><input type="text" id="search_ai_profile_address_country" name="search_ai_profile_address_country" value="<?php echo esc_attr($profile['stored_address']['country']); ?>"></td></tr>
            <tr data-location-field="service-areas"><th><label for="search_ai_profile_service_areas"><?php esc_html_e('Service areas', 'ajnanda'); ?></label></th><td><textarea class="large-text" rows="4" id="search_ai_profile_service_areas" name="search_ai_profile_service_areas"><?php echo esc_textarea(implode("\n", $profile['stored_service_areas'])); ?></textarea><p class="description"><?php esc_html_e('One city, region, state, or country per line.', 'ajnanda'); ?></p></td></tr>
            <tr><th><label for="search_ai_profile_identity_urls"><?php esc_html_e('Social profiles and identity links', 'ajnanda'); ?></label></th><td><textarea class="large-text" rows="5" id="search_ai_profile_identity_urls" name="search_ai_profile_identity_urls"><?php echo esc_textarea(implode("\n", $profile['identity_urls'])); ?></textarea><p class="description"><?php esc_html_e('One full public profile URL per line.', 'ajnanda'); ?></p></td></tr>
        </table>
    </div>
    <?php submit_button(__('Save Site Profile', 'ajnanda')); ?>
</form>
<script>
(function () {
    var locationModel = document.getElementById('search_ai_profile_location_mode');
    var locationExplanation = document.getElementById('ajnanda_location_explanation');
    function updateLocationFields() {
        if (!locationModel) { return; }
        var mode = locationModel.value;
        document.querySelectorAll('[data-location-field="address"]').forEach(function (row) { row.hidden = mode !== 'physical'; });
        document.querySelectorAll('[data-location-field="service-areas"]').forEach(function (row) { row.hidden = mode === 'none'; });
        var messages = {
            physical: <?php echo wp_json_encode(__('The structured address will be treated as a public machine-readable location.', 'ajnanda')); ?>,
            service_area: <?php echo wp_json_encode(__('The address is retained but not published; service areas describe where the business operates.', 'ajnanda')); ?>,
            regional_national: <?php echo wp_json_encode(__('The address is retained but not published; use service areas for regional or national coverage.', 'ajnanda')); ?>,
            none: <?php echo wp_json_encode(__('No address or service area will be exposed through the canonical Site Profile. Previously entered values are retained if you change this later.', 'ajnanda')); ?>
        };
        locationExplanation.textContent = messages[mode] || '';
    }
    if (locationModel) { locationModel.addEventListener('change', updateLocationFields); updateLocationFields(); }

    var choose = document.getElementById('ajnanda_profile_logo_button');
    var remove = document.getElementById('ajnanda_profile_logo_remove');
    var input = document.getElementById('search_ai_profile_logo_id');
    var preview = document.getElementById('ajnanda_profile_logo_preview');
    if (!choose || !input || typeof wp === 'undefined' || !wp.media) { return; }
    choose.addEventListener('click', function () {
        var frame = wp.media({ title: <?php echo wp_json_encode(__('Choose Site Profile Logo', 'ajnanda')); ?>, library: { type: 'image' }, multiple: false });
        frame.on('select', function () { var item = frame.state().get('selection').first().toJSON(); input.value = item.id; preview.innerHTML = '<img src="' + item.url + '" alt="" style="max-width:240px;height:auto;margin-top:12px;">'; });
        frame.open();
    });
    remove.addEventListener('click', function () { input.value = '0'; preview.innerHTML = ''; });
})();
</script>
