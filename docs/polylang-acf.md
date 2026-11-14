# Polylang Free and ACF block content

The supported multilingual integration is Polylang Free. The theme does not rely on Polylang Pro APIs or on an ACF translation panel.

## Requirements and translation model

- Polylang Free manages Polish and English pages, posts, selected custom post types, and their translation links.
- ACF Pro remains required by this theme because its blocks and repeaters use ACF Pro field types. The ACF license is separate from Polylang.
- ACF block attributes are stored in each page's Gutenberg `post_content`. Translate a page by creating its Polylang translation, copying its block structure and values, then editing visitor-facing text in the translated page. The translated content is stored independently.
- Reusable blocks (`wp_block`) that appear on a translated page must also have language-specific copies. Update the translated page's reusable-block references to those copies; do not edit a Polish reusable block to translate it.
- Polylang Free does not provide the Pro XLIFF/DeepL workflow or automatic ACF block translation. `wpml-config.xml` can describe supported custom-field handling, but it does not replace a manual translation workflow for Gutenberg block attributes. The removed `pll_acf_default_translation_setting` filter was Pro-specific and is intentionally not used.

## Field translation guidance

Translate visitor-facing copy, labels, accessibility text, CTA titles, and mail subjects/bodies in each translated block. Preserve structural values such as anchor IDs, CSS classes, repeater ordering, and route identifiers unless the translated destination requires a language-specific link. Copy image IDs and language-neutral facts when appropriate; translate alt text. Keep destination URLs language-aware and verify internal links after creating the translation.

With Polylang Free, these are editor instructions, not per-field settings enforced by an ACF integration. Avoid enabling synchronized custom-field values for ACF block data. ACF values saved as ordinary post meta require a separately reviewed `wpml-config.xml` policy if a future workflow needs to copy them automatically.

## Polish content migration

`polish-block-content.json` maps the existing Twig copy and exported Intro values to ACF fields. Existing Hero and Intro values remain in the WP Git Sync export. New fields for other blocks must be populated in their Polish blocks before release; this map is documentation and is not automatically imported.

For each translated page:

1. Keep the Polish page and its saved block order, content, and anchors (`rejs`, `o-patronce`, and `dolacz`) intact.
2. Create a language-linked page translation in Polylang Free and copy the complete page block structure, including ACF block attributes.
3. Translate visitor-facing ACF values, block text, alt text, button labels, and mail text in the English copy. Keep each source block intact; create English copies for reusable blocks and change only the English page's references.
4. Preserve shared image selections, route codes, years, names, and numeric facts as appropriate. Check URLs resolve to the English versions where available.
5. Preview both languages and check formatted Hero/Intro text, escaping, image alternatives, CTA destinations, anchors, and mailto encoding.

Hero's existing `headline` field is a textarea with the same ACF key, preserving saved values containing `<em>` and `<br>`. Intentionally formatted Hero/Intro values pass through `wp_kses_post`; ordinary field output is escaped.

## Block retention and image treatment

`about-us.twig`, `with-us.twig`, and `mission.twig` remain distinct registered blocks with separate field groups because there is no authoritative live-content inventory proving them unused. `view/blocks/intro.twig` remains untouched; the default block scanner uses `views/blocks`. The Intro background image is decorative and has `alt=""`. About's three archive images have editable image selectors and alt text.

## Verification boundary

Polylang Free 3.9.2 is active in the local Docker WordPress clone with Polish as the default language, English added, all existing translatable content assigned to Polish, and an English translation linked to the static homepage. The English clone was rendered at `/en/home/`; it has independent translated reusable blocks while preserving the Polish originals. Untranslated news posts and sitewide navigation/contact copy still appear in Polish because those entries do not yet have English translations.

The local stack uses a production database snapshot and is not production. The available production sync is read-only, so it cannot install plugins or update production content. Before release, an owner with production WordPress write access must install/activate Polylang Free, set Polish as default, assign existing content to Polish, create/link the English homepage and its reusable-block translations, and verify the production frontend.
