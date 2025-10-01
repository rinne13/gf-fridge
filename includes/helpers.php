<?php
namespace GFFridge;

if (!defined('ABSPATH')) exit;

class Helpers {
  public static function norm(string $s): string {
    $s = strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
  }

  public static function normalize_ingredients(array $list): array {
    $out = [];
    foreach ($list as $item) {
      $n = self::norm((string)$item);
      if ($n !== '') $out[] = $n;
    }
    return array_values(array_unique($out));
  }

  public static function get_thumb_or_placeholder(int $post_id): string {
    if (has_post_thumbnail($post_id)) {
      $src = wp_get_attachment_image_url(get_post_thumbnail_id($post_id), 'recipe-thumbnail');
      if ($src) return $src;
    }
    if (function_exists('gf_fallback_image_url')) return gf_fallback_image_url();
    return includes_url('images/media/default.png');
  }
}

if (!defined('ABSPATH')) exit;

/** нормализация: нижний регистр, trim, ascii fold (по-простому) */
function norm(string $s): string {
  $s = strtolower(trim($s));
  $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s; // грубая транслитерация
  $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
  $s = preg_replace('/\s+/', ' ', $s);
  return trim($s);
}

/** разбор строки ингредиентов в нормализованный массив */
function normalize_ingredients(array $list): array {
  $out = [];
  foreach ($list as $item) {
    $n = norm((string)$item);
    if ($n !== '') $out[] = $n;
  }
  // уникальность
  return array_values(array_unique($out));
}

/** получить миниатюру или плейсхолдер */
function get_thumb_or_placeholder(int $post_id): string {
  if (has_post_thumbnail($post_id)) {
    $src = wp_get_attachment_image_url(get_post_thumbnail_id($post_id), 'recipe-thumbnail');
    if ($src) return $src;
  }
  // попытаемся взять из темы плейсхолдер, если есть
  if (function_exists('gf_fallback_image_url')) {
    return gf_fallback_image_url();
  }
  return includes_url('images/media/default.png');
}
