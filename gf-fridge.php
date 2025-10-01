<?php
/**
 * Plugin Name: GF Fridge – What's in my Fridge
 * Description: Enter ingredients — get matching recipes. Shortcode [gf_fridge] + REST endpoints /wp-json/gf/v1/suggest and /wp-json/gf/v1/ingredients
 * Version: 1.1.0
 * Requires PHP: 8.1
 * Author: Varvara
 * Text Domain: gf-fridge
 */
if (!defined('ABSPATH')) exit;

define('GF_FRIDGE_VER', '1.1.0');
define('GF_FRIDGE_DIR', plugin_dir_path(__FILE__));
define('GF_FRIDGE_URL', plugin_dir_url(__FILE__));

require_once GF_FRIDGE_DIR . 'includes/helpers.php';
require_once GF_FRIDGE_DIR . 'includes/class-suggest.php';
require_once GF_FRIDGE_DIR . 'includes/class-ingredients-api.php';

// REST: vocabulary of ingridients
\GFFridge\Ingredients_API::register();

/**
 * Shortcode of form + results.
 * Usage: [gf_fridge]
 */
add_shortcode('gf_fridge', function($atts, $content = '') {
  $ingredients = isset($_POST['ingredients']) ? sanitize_textarea_field($_POST['ingredients']) : '';
  $strict = !empty($_POST['strict']) && $_POST['strict'] === '1';
  $results = null;

  if (!empty($ingredients)) {
    $arr = array_filter(array_map('trim', explode(',', $ingredients)));
    if ($arr) {
      $results = \GFFridge\Suggest::suggest_recipes($arr, $strict);
    }
  }

  ob_start(); ?>
  <div class="gf-fridge-scope"><!-- SCOPED AREA START -->

    <div class="fridge-form">
      <form method="post" action="">
        <?php wp_nonce_field('gf_fridge_nonce', '_gf_fridge'); ?>

        <!-- Chips input + скрытая textarea -->
        <div class="gf-chips" data-target="ingredients">
          <div class="gf-chip-list" aria-live="polite"></div>
          <input
            type="text"
            class="gf-chip-input"
            aria-label="<?php esc_attr_e('Ingredient', 'gf-fridge'); ?>"
            placeholder="<?php esc_attr_e('Type an ingredient and press Enter…', 'gf-fridge'); ?>"
          >
          <div class="gf-suggest" role="listbox" aria-label="<?php esc_attr_e('Suggestions', 'gf-fridge'); ?>"></div>
        </div>

        <textarea id="ingredients" name="ingredients" hidden><?php echo esc_textarea($ingredients); ?></textarea>
        <p class="help-text">
          <?php esc_html_e('Use Enter to add as a tag. We also auto-suggest from existing recipes.', 'gf-fridge'); ?>
        </p>

        <div class="checkbox-wrapper">
          <label>
            <input type="checkbox" name="strict" value="1" <?php checked($strict, true); ?> />
            <?php esc_html_e('Strict match (only show recipes I can make with exactly these ingredients)', 'gf-fridge'); ?>
          </label>
        </div>

        <button type="submit" class="submit-button"><?php esc_html_e('Find Recipes', 'gf-fridge'); ?></button>
      </form>
    </div>

    <?php if ($results !== null): ?>
      <?php if (!empty($results['can_cook'])): ?>
        <section class="results-section">
          <h2>✅ <?php esc_html_e('You can cook now!', 'gf-fridge'); ?></h2>
          <div class="recipes-grid">
            <?php foreach ($results['can_cook'] as $r): ?>
              <div class="recipe-card">
                <?php if ($r['thumbnail']): ?>
                  <a href="<?php echo esc_url($r['permalink']); ?>">
                    <img src="<?php echo esc_url($r['thumbnail']); ?>" alt="<?php echo esc_attr($r['title']); ?>" />
                  </a>
                <?php endif; ?>
                <div class="recipe-content">
                  <h3><a href="<?php echo esc_url($r['permalink']); ?>"><?php echo esc_html($r['title']); ?></a></h3>
                  <div class="recipe-badges">
                    <span class="badge" style="background:#c8e6c9;color:#2e7d32;">
                      <?php echo esc_html($r['matched_count']); ?>/<?php echo esc_html(count($r['ingredients'])); ?> <?php esc_html_e('ingredients', 'gf-fridge'); ?>
                    </span>
                    <?php if ($r['cook_time_minutes']): ?>
                      <span class="badge time">⏱ <?php echo esc_html($r['cook_time_minutes']); ?> min</span>
                    <?php endif; ?>
                    <?php if ($r['difficulty']): ?>
                      <span class="badge difficulty <?php echo esc_attr($r['difficulty']); ?>">
                        <?php $dots=['easy'=>'•','medium'=>'• •','hard'=>'• • •']; echo esc_html($dots[$r['difficulty']] ?? ''); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <?php if ($r['excerpt']): ?><p class="excerpt"><?php echo esc_html($r['excerpt']); ?></p><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if (!empty($results['almost']) && !$strict): ?>
        <section class="results-section">
          <h2>🔸 <?php esc_html_e('Almost there! (Missing a few ingredients)', 'gf-fridge'); ?></h2>
          <div class="recipes-grid">
            <?php foreach ($results['almost'] as $r): ?>
              <div class="recipe-card">
                <?php if ($r['thumbnail']): ?>
                  <a href="<?php echo esc_url($r['permalink']); ?>">
                    <img src="<?php echo esc_url($r['thumbnail']); ?>" alt="<?php echo esc_attr($r['title']); ?>" />
                  </a>
                <?php endif; ?>
                <div class="recipe-content">
                  <h3><a href="<?php echo esc_url($r['permalink']); ?>"><?php echo esc_html($r['title']); ?></a></h3>
                  <div class="recipe-badges">
                    <span class="badge" style="background:#fff3e0;color:#e65100;">
                      <?php echo esc_html($r['matched_count']); ?>/<?php echo esc_html(count($r['ingredients'])); ?> <?php esc_html_e('ingredients', 'gf-fridge'); ?>
                    </span>
                    <?php if ($r['cook_time_minutes']): ?>
                      <span class="badge time">⏱ <?php echo esc_html($r['cook_time_minutes']); ?> min</span>
                    <?php endif; ?>
                    <?php if ($r['difficulty']): ?>
                      <span class="badge difficulty <?php echo esc_attr($r['difficulty']); ?>">
                        <?php $dots=['easy'=>'•','medium'=>'• •','hard'=>'• • •']; echo esc_html($dots[$r['difficulty']] ?? ''); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($r['missing_ingredients'])): ?>
                    <div class="missing-ingredients">
                      <strong><?php esc_html_e('Missing:', 'gf-fridge'); ?></strong>
                      <?php echo esc_html(implode(', ', array_slice($r['missing_ingredients'], 0, 3))); ?>
                      <?php if (count($r['missing_ingredients']) > 3) echo esc_html('…'); ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($r['excerpt']): ?><p class="excerpt"><?php echo esc_html($r['excerpt']); ?></p><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if (empty($results['can_cook']) && empty($results['almost'])): ?>
        <p class="no-results"><?php esc_html_e('No recipes found with those ingredients. Try adding eggs and rice!', 'gf-fridge'); ?></p>
      <?php endif; ?>
    <?php endif; ?>

  </div><!-- SCOPED AREA END -->

  <?php
  return ob_get_clean();
});


add_action('wp_enqueue_scripts', function () {
  if (!is_singular()) return;
  global $post;
  if (!$post || !has_shortcode($post->post_content, 'gf_fridge')) return;

  // JS
  wp_enqueue_script(
    'gf-fridge-chips',
    GF_FRIDGE_URL . 'assets/js/fridge-chips.js',
    [],
    GF_FRIDGE_VER,
    true
  );

  // CSS 
  $theme_handle = 'gf-recipes-theme-style'; 
  wp_enqueue_style(
    'gf-fridge-chips',
    GF_FRIDGE_URL . 'assets/css/fridge-chips.css',
    [$theme_handle],
    GF_FRIDGE_VER
  );

  // JS
  wp_localize_script('gf-fridge-chips', 'GF_FRIDGE', [
    'rest'  => esc_url_raw( rest_url('gf/v1') ),
    'nonce' => wp_create_nonce('wp_rest'),
    'i18n'  => [
      'placeholder' => __('Type an ingredient and press Enter…', 'gf-fridge'),
      'add' => __('Add', 'gf-fridge'),
      'remove' => __('Remove', 'gf-fridge'),
    ]
  ]);
}, 20);
