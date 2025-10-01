<?php
namespace GFFridge;

if (!defined('ABSPATH')) exit;

class Suggest {
  /**
   * Главный метод подбора
   * @param string[] $ingredients Пользовательские ингредиенты (сырые строки)
   * @param bool $strict Строгий режим: без недостающих ингредиентов
   * @return array{can_cook: array, almost: array}
   */
  public static function suggest_recipes(array $ingredients, bool $strict = false): array {
$user = Helpers::normalize_ingredients($ingredients);
    if (!$user) return ['can_cook' => [], 'almost' => []];

    // вытащим последние рецепты (можно расширить criteria)
    $q = new \WP_Query([
      'post_type'      => 'recipe',
      'post_status'    => 'publish',
      'posts_per_page' => 200,
      'orderby'        => 'date',
      'order'          => 'DESC',
      'no_found_rows'  => true,
    ]);

    $can  = [];
    $near = [];

    while ($q->have_posts()) {
      $q->the_post();
      $pid = get_the_ID();

      // ожидание: метаполе 'ingredients' — массив строк (как в твоём плагине рецептов)
      $recipe_ing = get_post_meta($pid, 'ingredients', true);
      if (!is_array($recipe_ing)) {
        // попробуем распарсить строку через запятую
        $recipe_ing = array_filter(array_map('trim', explode(',', (string)$recipe_ing)));
      }
$recipe_ing = Helpers::normalize_ingredients($recipe_ing);

      // матчинг
      $matches = array_intersect($recipe_ing, $user);
      $missing = array_values(array_diff($recipe_ing, $user));
      $matched_count = count($matches);

      // скоринг: 2 * совпавшие - недостающие
      $score = (2 * $matched_count) - count($missing);

      // строгий режим — только рецепты без недостающих
      if ($strict && !empty($missing)) continue;

      $item = [
        'ID'                 => $pid,
        'title'              => get_the_title(),
        'permalink'          => get_permalink(),
        'thumbnail' => Helpers::get_thumb_or_placeholder($pid),
        'excerpt'            => has_excerpt($pid) ? wp_strip_all_tags(get_the_excerpt()) : '',
        'cook_time_minutes'  => absint(get_post_meta($pid, 'cook_time_minutes', true)),
        'difficulty'         => sanitize_key(get_post_meta($pid, 'difficulty', true)), // easy|medium|hard
        'ingredients'        => $recipe_ing,
        'matched'            => array_values($matches),
        'matched_count'      => $matched_count,
        'missing_ingredients'=> $missing,
        'score'              => $score,
      ];

      if (empty($missing)) {
        $can[] = $item;
      } else {
        $near[] = $item;
      }
    }
    wp_reset_postdata();

    // сортируем: выше score, при равенстве — новее
    usort($can,  fn($a,$b) => $b['score'] <=> $a['score']);
    usort($near, fn($a,$b) => $b['score'] <=> $a['score']);

    return [
      'can_cook' => $can,
      'almost'   => $near,
    ];
  }
}
