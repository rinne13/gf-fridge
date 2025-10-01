<?php
namespace GFFridge;

if (!defined('ABSPATH')) exit;

class Ingredients_API {
  public static function register() {
    add_action('rest_api_init', [__CLASS__, 'routes']);
  }

  public static function routes() {
    register_rest_route('gf/v1', '/ingredients', [
      'methods'  => 'GET',
      'callback' => [__CLASS__, 'get_all'],
      'permission_callback' => '__return_true',
    ]);
  }

  public static function get_all(\WP_REST_Request $req) {
    // соберём до 1000 рецептов; при желании можно пагинацию
    $q = new \WP_Query([
      'post_type'      => 'recipe',
      'post_status'    => 'publish',
      'posts_per_page' => 1000,
      'no_found_rows'  => true,
      'fields'         => 'ids',
    ]);

    $set = [];
    foreach ($q->posts as $pid) {
      $val = get_post_meta($pid, 'ingredients', true);
      // поддержим строку "яйца, рис" и массив ["яйца","рис"]
      $list = is_array($val) ? $val : array_filter(array_map('trim', explode(',', (string)$val)));
      foreach ($list as $raw) {
        $norm = Helpers::norm((string)$raw);
        if ($norm !== '') $set[$norm] = true;
      }
    }
    wp_reset_postdata();

    $items = array_keys($set);
    sort($items, SORT_STRING);
    return new \WP_REST_Response([
      'ingredients' => $items,
      'count' => count($items),
    ], 200);
  }
}
