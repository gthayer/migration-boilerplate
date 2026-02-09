<?php
/**
 * CraftCMS Migration Class
 *
 * @package MigrationBoilerplate\CraftCMS
 */

namespace MigrationBoilerplate\CraftCMS;

use WP_CLI;
use wpdb;

class CraftCMS {
	/**
	 * WordPress database instance
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Legacy database instance
	 *
	 * @var wpdb
	 */
	private $legacy_db;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		// Initialize legacy database connection
		$this->init_legacy_db();
	}

	/**
	 * Initialize connection to legacy database
	 *
	 * @return void
	 */
	private function init_legacy_db() {
		// Connect to legacy database
		$this->legacy_db = new wpdb(
			DB_USER,
			DB_PASSWORD,
			'legacy',
			DB_HOST
		);
		$rows = $this->legacy_db;

		if ( $this->legacy_db->last_error ) {
			\MigrationBoilerplate\error( 'Failed to connect to legacy database: ' . $this->legacy_db->last_error );
		}
	}

	/**
	 * Get entries from Craft CMS
	 *
	 * @param int $type_id Entry type ID to fetch
	 * @param int $limit Number of entries to fetch
	 * @param int $offset Number of entries to skip
	 * @param int $legacy_id Legacy ID of the entry to fetch
	 * @return array
	 */
	public function get_entries( $type_id, $limit = 100, $offset = 0, $legacy_id = null ) {
		if ( ! empty( $legacy_id ) ) {
			$entries = $this->legacy_db->get_results(
				$this->legacy_db->prepare(
					"SELECT * FROM entries
					LEFT JOIN elements ON entries.id = elements.id
					WHERE typeId = %s
					AND elements.canonicalId IS NULL
					AND elements.id = %d
					LIMIT %d OFFSET %d",
					$type_id,
					$legacy_id,
					$limit,
					$offset
				)
			);
		} else {
			$entries = $this->legacy_db->get_results(
				$this->legacy_db->prepare(
					"SELECT * FROM entries
					LEFT JOIN elements ON entries.id = elements.id
					WHERE typeId = %s
					AND elements.canonicalId IS NULL
					LIMIT %d OFFSET %d",
					$type_id,
					$limit,
					$offset
				)
			);
		}

		if ( empty( $entries ) ) {
			\MigrationBoilerplate\error( 'No entries found in Craft CMS database.' );
			return [];
		}

		return $entries;
	}

	/**
	 * Get element for an entry
	 *
	 * @param int $entry_id Entry ID
	 * @return object|null
	 */
	private function get_element( $entry_id ) {
		return $this->legacy_db->get_row(
			$this->legacy_db->prepare(
				"SELECT * FROM elements WHERE id = %d",
				$entry_id
			)
		);
	}

	/**
	 * Get NeoBlocks content for an entry
	 *
	 * @param int $entry_id Entry ID
	 * @return array|null
	 */
	private function get_neoblocks_content( $entry_id ) {
		$neoblocks = $this->legacy_db->get_results(
			$this->legacy_db->prepare(
				"SELECT * FROM neoblocks LEFT JOIN neoblocks_owners ON neoblocks.id = neoblocks_owners.blockId WHERE neoblocks_owners.ownerId = %d ORDER BY neoblocks_owners.sortOrder ASC",
				$entry_id
			)
		);

		if ( empty( $neoblocks ) ) {
			return null;
		}

		$content = [];
		foreach ( $neoblocks as $neoblock ) {
			$neoblock_element = $this->get_element( $neoblock->id );
			$neoblock_content = $this->get_content( $neoblock_element->id );
			$content[] = $neoblock_content;
		}

		return $content;
	}

	/**
	 * Get Supertableblocks content for an entry
	 *
	 * @param int $entry_id Entry ID
	 * @return array|null
	 */
	private function get_supertableblocks_content( $entry_id ) {
		$supertableblocks = $this->legacy_db->get_results(
			$this->legacy_db->prepare(
				"SELECT * FROM supertableblocks WHERE primaryOwnerId = %d",
				$entry_id
			)
		);

		if ( empty( $supertableblocks ) ) {
			return null;
		}

		$supertable_tables =[
			'stc_accordion',
			'stc_accordioncards',
			'stc_bannerprimary',
			'stc_bannersecondary',
			'stc_bannertertiary',
			'stc_contactpages',
			'stc_ctalink',
			'stc_definitionlist',
			'stc_icongrid',
			'stc_links',
			'stc_linkssidebar',
			'stc_listcolumns',
			'stc_listsingle',
			'stc_pagehero',
			'stc_pageherosmall',
			'stc_removepadding',
			'stc_standardlistqtrcol',
			'stc_standardlistsplitcol',
		];

		$blocks = [];

		// Reduce the supertableblocks to the most recent one.
		$supertable = array_reduce( $supertableblocks, function( $carry, $item ) {
			return strtotime($carry->dateUpdated) > strtotime($item->dateUpdated) ? $carry : $item;
		} );

		foreach ( $supertable_tables as $table ) {
			$rows = $this->legacy_db->get_results(
				$this->legacy_db->prepare(
					"SELECT * FROM $table WHERE elementId = %d",
					$supertable->id
				)
			);

			if ( empty( $rows ) ) {
				continue;
			}

			if ( count( $rows ) > 1 ) {
				\WP_CLI::error( "Multiple blocks found for table: $table" );
				exit;
			}

			$blocks[$table] = $rows[0];
		}

		return $blocks;
	}
	
	/**
	 * Get content for an entry
	 *
	 * @param int $entry_id Entry ID
	 * @return object|null
	 */
	private function get_content( $entry_id ) {
		// First get the content from the content table
		$content = $this->legacy_db->get_row(
			$this->legacy_db->prepare(
				"SELECT * FROM content WHERE elementId = %d",
				$entry_id
			)
		);

		if ( ! $content ) {
			return null;
		}

		$neoblocks = $this->get_neoblocks_content( $entry_id );
		if ( $neoblocks ) {
			$content->neoblocks = $neoblocks;
		}

		$supertableblocks = $this->get_supertableblocks_content( $entry_id );
		if ( $supertableblocks ) {
			$content->supertableblocks = $supertableblocks;
		}

		return $content;
	}

	/**
	 * Get featured image for an entry
	 *
	 * @param int $entry_id Entry ID
	 * @return object|null
	 */
	private function get_featured_image( $entry_id ) {
		// First get the asset ID from the content table
		$asset_id = $this->legacy_db->get_var(
			$this->legacy_db->prepare(
				"SELECT field_featuredImage FROM content WHERE elementId = %d",
				$entry_id
			)
		);

		if ( ! $asset_id ) {
			return null;
		}

		// Then get the asset details
		return $this->legacy_db->get_row(
			$this->legacy_db->prepare(
				"SELECT * FROM assets WHERE id = %d",
				$asset_id
			)
		);
	}

	private function import_image( $image_url, $post_id, $asset_id ) {
		$overwrite = 'skip';
		if ( 'overwrite' === $overwrite || 'skip' === $overwrite ) {
			$attachment_id = (int) \MigrationBoilerplate\get_post_id_by_meta( '_craft_cms_post_id', $asset_id );

			if ( $attachment_id ) {
				return $attachment_id;
			}
		}

		$image_content = $this->get_content( $asset_id );
		$attachment_id = \MigrationBoilerplate\download_and_sideload(
			$image_url,
			$post_id,
			null,
			[
				'do_rename'    => true,
				'post_title'   => $image_content->title ?? '',
				'post_excerpt' => $caption ?? '',
			],
			300
		);

		update_post_meta( $attachment_id, '_craft_cms_post_id', $asset_id );

		return $attachment_id;
	}

	/**
	 * Compare two dates safely
	 *
	 * @param string|null $date1 First date
	 * @param string|null $date2 Second date
	 * @return bool True if date1 is more recent than date2
	 */
	private function compare_dates($date1, $date2) {
		// Handle null or empty dates
		if (empty($date1)) return false;
		if (empty($date2)) return true;
		
		try {
			// Convert to DateTime objects with timezone
			$dt1 = new \DateTime($date1, new \DateTimeZone('UTC'));
			$dt2 = new \DateTime($date2, new \DateTimeZone('UTC'));
			
			return $dt1 > $dt2;
		} catch (\Exception $e) {
			// Log the error but don't break the process
			error_log(sprintf(
				'Error comparing dates: %s and %s. Error: %s',
				$date1,
				$date2,
				$e->getMessage()
			));
			
			// Fallback to string comparison if DateTime fails
			return $date1 > $date2;
		}
	}

	/**
	 * Create WordPress post from Craft CMS entry
	 *
	 * @param object $entry Craft CMS entry
	 * @return int|WP_Error Post ID on success, WP_Error on failure
	 */
	public function create_post( $entry ) {
		// Check if the post already exists
		$post_id = 0;

		// TODO: Add this to the CLI args
		$overwrite = 'overwrite';
		if ( 'overwrite' === $overwrite || 'skip' === $overwrite ) {
			$post_id = (int) \MigrationBoilerplate\get_post_id_by_meta( '_craft_cms_post_id', $entry->id );

			if ( $post_id && 'skip' === $overwrite ) {
				return $post_id;
			}
		}

		\WP_CLI::line( "Processing entry {$entry->id}..." );

		// Get additional entry data
		$element = $this->get_element( $entry->id );
		$content = $this->get_content( $entry->id );

		$content_html = '';
		foreach ( $content->neoblocks as $neoblock ) {

			if ( $neoblock->field_headlineNoFormatting ) {
				$content_html .= '<h3>' . wp_strip_all_tags( $neoblock->field_headlineNoFormatting ) . '</h3>';
			}

			if ( $neoblock->field_contentField ) {
				$content_html .= $neoblock->field_contentField;
			}
		}

		$subheadline = wp_strip_all_tags( $content->supertableblocks['stc_bannertertiary']->field_contentField ) ?? '';

		// TODO: Seemingly these are the same, but we might want to check.
		// Title's are set in two places. See if they differ.
		// $headline    = wp_strip_all_tags( $content->supertableblocks['stc_bannertertiary']->field_headline ) ?? '';
		// if ( $headline !== $content->title ) {
		// 	\WP_CLI::error( "Headline and title differ for entry {$entry->id}." );
		// 	exit;
		// }

		$post_content = \MigrationBoilerplate\html_to_block_markup( $content_html );
		$post_content = $this->insert_content_images( $post_content );

		// Prepare post data
		$post_data = [
			'ID'            => $post_id,
			'post_title'    => $content->title ?? $entry->title,
			'post_status'   => 'publish',
			'post_content'  => $post_content,
			'post_type'     => 'post',
			'post_date'     => $entry->postDate ?? $entry->dateCreated,
			'post_date_gmt' => ! empty( $entry->postDate ) ? get_gmt_from_date( $entry->postDate ) : get_gmt_from_date( $entry->dateCreated ),
			'post_modified' => $entry->dateUpdated,
		];

		// Insert or update post
		$post_id = wp_insert_post( $post_data );
		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( "Failed to create post for entry {$entry->id}: " . $post_id->get_error_message() );
			return $post_id;
		}

		// Taxonomies.

		// Post Meta.

		// Set the legacy post ID
		update_post_meta( $post_id, '_craft_cms_post_id', $entry->id );
		return $post_id;
	}

	/**
	 * Process a single entry
	 *
	 * @param object $entry Craft CMS entry
	 * @return bool True on success, false on failure
	 */
	public function process_entry( $entry ) {
		$post_id = $this->create_post( $entry );

		if ( is_wp_error( $post_id ) ) {
			\MigrationBoilerplate\error( "Failed to create post for entry {$entry->id}: " . $post_id->get_error_message() );
			return false;
		}

		return true;
	}

	/**
	 * Insert content images into WordPress
	 *
	 * @param string $post_content The post content to insert images into
	 * @return string The post content with images inserted
	 */
	private function insert_content_images( $post_content ) {
		// Search for <figure> tags and insert them into WordPress and replace the <img> tags with the WordPress image block.
		$post_content = preg_replace_callback( '/<figure[^>]*>.*?<\/figure>/s', function( $matches ) {

			$alignment    = '';
			$figure_class = 'wp-block-image size-large';
			$image_class  = '';

			// Attempt to detect the alignment from the class attribute.
			if ( preg_match( '/<figure[^>]*style="([^"]+)"/', $matches[0], $figure_style ) ) {
				$figure_style = $figure_style[1];

				if ( strpos( $figure_style, 'float:right' ) !== false ) {
					$alignment = 'right';
				} elseif ( strpos( $figure_style, 'float:left' ) !== false ) {
					$alignment = 'left';
				}

				if ( ! empty( $alignment ) ) {
					$figure_class .= ' align' . $alignment;
				}
			}

			// Extract the image Source from the <img> tag.
			if ( preg_match( '/<img[^>]*src="([^"]+)"/', $matches[0], $image_src ) ) {
				$image_src = $image_src[1];
			}

			// Get the caption from the <figcaption> tag.
			if ( preg_match( '/<figcaption[^>]*>(.*?)<\/figcaption>/s', $matches[0], $caption ) ) {
				$caption = $caption[1];
			}

			// Get the alt from the image.
			if ( preg_match( '/<img[^>]*alt="([^"]+)"/', $matches[0], $image_alt ) ) {
				$image_alt = (string) $image_alt[1];
			}

			// Extract the asset ID from the image source.
			if (preg_match('/{asset:(\d+):url\|\|(.*?)}/', $image_src, $src_matches)) {
				$asset_id  = $src_matches[1];
				$image_url = $src_matches[2];
			}

			if ( empty( $asset_id ) || empty( $image_url ) ) {
				\WP_CLI::error( "No asset ID or image URL found for entry {$entry->id}." );
				return $matches[0];
			}

			$attachment_id = $this->import_image( $image_url, $post_id, $asset_id );

			if ( empty( $attachment_id ) ) {
				\WP_CLI::error( "Failed to import image for entry {$entry->id}." );
				return $matches[0];
			}

			$image_class .= ' wp-image-' . $attachment_id;
			
			$attributes = [
				'id'    => $attachment_id,
				'align' => $alignment,
			];

			$image_block = sprintf(
				'<!-- wp:image %s -->
				<figure class="%s">
					<img src="%s" alt="%s" class="%s"/>
					<figcaption class="wp-element-caption">%s</figcaption>
				</figure>
				<!-- /wp:image -->',
				json_encode( $attributes ),
				trim( $figure_class ),
				wp_get_attachment_url( $attachment_id ),
				! empty( $image_alt ) ? trim( $image_alt ) : '',
				trim( $image_class ),
				$caption
			);

			return $image_block;
		}, $post_content );

		return $post_content;
	}
} 