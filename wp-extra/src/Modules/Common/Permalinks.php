<?php
namespace WPEXtra\Modules\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class Permalinks extends Base {
    
    public function __construct() {
		parent::__construct();
        add_action('update_option_wp_extra', [$this, 'sync_rewrite_rules'], 10, 2);
    }

    public function sync_rewrite_rules($old_value, $value) {
        $keys = ['slug_post_type', 'slug_taxonomy'];
        $needs_flush = false;
        foreach ($keys as $key) {
            if (($old_value[$key] ?? null) !== ($value[$key] ?? null)) {
                $needs_flush = true;
                break;
            }
        }
        if ($needs_flush) {
            flush_rewrite_rules(false);
        }
    }
    
	protected $features = [
		'external_links',
		'redirect_attachment',
		'redirect_single_post',
		'slug_post_type',
		'slug_taxonomy',
		'robots_txt',
	];

    public function external_links() {
        add_filter('the_content', [$this, 'filter_external_links'], 99);
    }

    public function filter_external_links($content) {
        if (empty($content) || !is_string($content)) {
            return $content;
        }

        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

        if (class_exists('\WP_HTML_Tag_Processor')) {
            $processor = new \WP_HTML_Tag_Processor($content);

            while ($processor->next_tag(['tag_name' => 'a'])) {
                $href = (string) $processor->get_attribute('href');
                if ($href && preg_match('#^https?://#i', $href)) {
                    $link_host = wp_parse_url($href, PHP_URL_HOST);
                    if ($link_host && strcasecmp($link_host, $site_host) !== 0) {
                        $processor->set_attribute('target', '_blank');

                        $rel = (string) $processor->get_attribute('rel');
                        $rel_parts = array_filter(array_map('trim', explode(' ', $rel)));

                        if (!in_array('nofollow', $rel_parts, true)) {
                            $rel_parts[] = 'nofollow';
                        }
                        if (!in_array('noopener', $rel_parts, true)) {
                            $rel_parts[] = 'noopener';
                        }
                        if (!in_array('noreferrer', $rel_parts, true)) {
                            $rel_parts[] = 'noreferrer';
                        }

                        $processor->set_attribute('rel', implode(' ', array_unique($rel_parts)));
                    }
                }
            }

            return $processor->get_updated_html();
        }

        // Regex fallback
        return preg_replace_callback('/<a\s+([^>]+)>/i', function($matches) use ($site_host) {
            $attrs = $matches[1];
            if (preg_match('/href=["\'](https?:\/\/[^"\']+)["\']/i', $attrs, $url_match)) {
                $link_host = wp_parse_url($url_match[1], PHP_URL_HOST);
                if ($link_host && strcasecmp($link_host, $site_host) !== 0) {
                    if (!preg_match('/target=["\']/i', $attrs)) {
                        $attrs .= ' target="_blank"';
                    }
                    if (preg_match('/rel=["\']([^"\']*)["\']/i', $attrs, $rel_match)) {
                        $rel = $rel_match[1];
                        if (strpos($rel, 'nofollow') === false) $rel .= ' nofollow';
                        if (strpos($rel, 'noopener') === false) $rel .= ' noopener';
                        if (strpos($rel, 'noreferrer') === false) $rel .= ' noreferrer';
                        $attrs = preg_replace('/rel=["\'][^"\']*["\']/i', 'rel="' . trim($rel) . '"', $attrs);
                    } else {
                        $attrs .= ' rel="nofollow noopener noreferrer"';
                    }
                }
            }
            return '<a ' . trim($attrs) . '>';
        }, $content);
    }

    public function redirect_attachment() {
        add_action('template_redirect', [$this, 'redirect_attachment_page']);
    }

    public function redirect_attachment_page() {
        if (is_attachment()) {
            global $post;
            if ($post && !empty($post->post_parent)) {
                wp_safe_redirect(esc_url(get_permalink($post->post_parent)), 301);
                exit;
            } else {
                wp_safe_redirect(esc_url(home_url('/')), 301);
                exit;
            }
        }
    }

    public function redirect_single_post() {
        add_action('template_redirect', [$this, 'search_results_return_one_post']);
    }

    public function search_results_return_one_post() {
        if (is_search()) {
            global $wp_query;
            if ($wp_query && $wp_query->post_count == 1 && $wp_query->max_num_pages == 1 && !empty($wp_query->posts[0])) {
                wp_safe_redirect(get_permalink($wp_query->posts[0]->ID));
                exit;
            }
        }
    }

    public function robots_txt() {
        add_filter('robots_txt', [$this, 'filter_robots_txt'], 99, 2);
    }

    public function filter_robots_txt($output, $public) {
        $custom = Helper::get_option('robots_txt');
        if (!empty($custom)) {
            return $custom;
        }
        return $output;
    }

    public function slug_post_type() {
        add_action('pre_get_posts', [$this, 'rempostslug_parse_request'], 1, 1);
        add_filter('post_type_link', [$this, 'rempostslug_fun'], 10, 3);
        add_filter('get_the_permalink', [$this, 'rempostslug_fun'], 10, 3);
        add_filter('the_permalink', [$this, 'rempostslug_fun'], 10, 3);
    }

    public function slug_taxonomy() {
        add_filter('request', [$this, 'remtaxslug_change_term_request'], 1, 1);
        add_filter('term_link', [$this, 'remtaxslug_term_permalink'], 10, 2);
        add_filter('get_category_link', [$this, 'remtaxslug_term_permalink'], 10, 3);
        add_filter('get_term_link', [$this, 'remtaxslug_term_permalink'], 10, 3);
        add_filter('category_link', [$this, 'remtaxslug_term_permalink'], 10, 3);
    }

    public function rempostslug_fun($post_link, $post) {
        $post_type_list = (array) Helper::get_option('slug_post_type', []);
        if (empty($post_type_list) || !($post instanceof \WP_Post)) {
            return $post_link;
        }
        if (in_array(get_post_type($post), $post_type_list, true)) {
            $post_link = trailingslashit(get_option('home')) . user_trailingslashit($post->post_name);
        }
        return $post_link;
    }

    public function rempostslug_parse_request($query) {
        $post_type_list = (array) Helper::get_option('slug_post_type', []);
        if (!$query->is_main_query() || count($query->query) !== 2 || !isset($query->query['page'])) {
            return;
        }
        if (!empty($query->query['name']) && !empty($post_type_list)) {
            $query->set('post_type', $post_type_list);
        }
    }

    public function remtaxslug_change_term_request($query) {
        $tax_names = (array) Helper::get_option('slug_taxonomy', []);
        foreach ($tax_names as $current_tax_name) {
            if (array_key_exists('attachment', $query) && $query['attachment']) {
                $include_children = true;
                $name = $query['attachment'];
            } else {
                $include_children = false;
                $name = array_key_exists('name', $query) ? $query['name'] : '';
            }

            $term = get_term_by('slug', $name, $current_tax_name);

            if (!empty($name) && !empty($term) && !is_wp_error($term)) {
                if ($include_children) {
                    unset($query['attachment']);
                    $parent = $term->parent;
                    while ($parent) {
                        $parent_term = get_term($parent, $current_tax_name);
                        if ($parent_term && !is_wp_error($parent_term)) {
                            $name = $parent_term->slug . '/' . $name;
                            $parent = $parent_term->parent;
                        } else {
                            break;
                        }
                    }
                } else {
                    unset($query['name']);
                }

                switch ($current_tax_name) {
                    case 'category':
                        $query['category_name'] = $name;
                        break;
                    case 'post_tag':
                        $query['tag'] = $name;
                        break;
                    default:
                        $query[$current_tax_name] = $name;
                        break;
                }
            }
        }

        return $query;
    }

    public function remtaxslug_term_permalink($url, $taxonomy) {
        if (is_int($taxonomy)) {
            $taxonomy_term = get_term($taxonomy);
            if (!($taxonomy_term instanceof \WP_Term)) {
                return $url;
            }
            $taxonomy_slug = $taxonomy_term->slug;
            $taxonomy_name = $taxonomy_term->taxonomy;
        } elseif (is_object($taxonomy) && isset($taxonomy->slug, $taxonomy->taxonomy)) {
            $taxonomy_slug = $taxonomy->slug;
            $taxonomy_name = $taxonomy->taxonomy;
        } else {
            return $url;
        }
        $saved_value = (array) Helper::get_option('slug_taxonomy', []);
        if (empty($saved_value)) {
            return $url;
        }
        if (in_array($taxonomy_name, $saved_value, true)) {
            $url = trailingslashit(get_option('home')) . user_trailingslashit($taxonomy_slug);
        }

        return $url;
    }

}
