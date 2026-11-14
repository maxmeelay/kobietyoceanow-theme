# Plan: Make Twig copy editable and translation-ready

## Goal

Replace visitor-facing text embedded in Twig with ACF-managed content, while allowing each language to have its own translated value. Polish is the current source copy; it must not be baked into Twig as the permanent fallback for every language.

## Audit scope and findings

This audit covers every `.twig` file in the repository (eight files):

| Template | Current content and migration scope |
| --- | --- |
| `view/blocks/intro.twig` | Separate minimal template containing the literal `Intro`. The locked `palmiak/timber-acf-wp-blocks` 1.15 package scans `views/blocks` by default, and this theme does not override its template-directory filter, so this file is not currently registered as a block. No reference to it was found in the inspected content exports; confirm against any content sources outside those exports, then document its removal if unused. |
| `views/blocks/about-us.twig` | Static section eyebrow/title/intro, team heading and eight member names, continuation section labels and text, CTA label and hard-coded internal URL. |
| `views/blocks/about.twig` | Static archive/section labels, image alt text, heading, three paragraphs, CTA label and hard-coded internal URL. Images and their paths are also hard-coded, though not text fields. |
| `views/blocks/hero.twig` | `pre_headline` and `headline` already use ACF. The home breadcrumb label and root URL are hard-coded. The existing `headline` field is type `text`, while stored page exports contain `<em>` and `<br>` markup; correct the field/rendering contract before changing output. Hero blocks appear in multiple exported pages and a post. |
| `views/blocks/intro.twig` | ACF fields `background_image`, `pre_headline`, `headline`, `text`, `button_primary`, and `button_secondary` drive the background image, copy, and two links. Four metric values and labels are static. The home-page export (`page/2.md`) contains stored Polish values for these ACF fields and links to `/#rejs` and `/#o-patronce`. A commented video contains a browser fallback string; decide whether the video is removed or made configurable before counting it as live copy. |
| `views/blocks/join.twig` | Static heading and three cards: titles, descriptions and CTA labels; card destinations and mailto subjects/bodies are hard-coded and include Polish. |
| `views/blocks/mission.twig` | Static date eyebrow, title with emphasized phrase, summary, four timeline entries (codes, years, labels and locations) and quotation. |
| `views/blocks/with-us.twig` | Same visible about/team/continuation copy as `about-us.twig`; migrate consistently and investigate whether these duplicate blocks are both used. |

`views/blocks/intro.twig` and `view/blocks/intro.twig` are distinct files in distinct directories; do not conflate them. The repository already contains active ACF JSON field groups for `acf/intro` (`acf-json/group_6a6df59088d15.json`) and `acf/hero` (`acf-json/group_6a6dfc970d181.json`), plus two post-type exports. The Intro group defines `background_image` (image), `pre_headline` (text), `headline` and `text` (textarea), and `button_primary` and `button_secondary` (link). The Hero group defines `pre_headline` and `headline` as text fields. The field-group JSON does not specify per-language translation modes. No ACF field groups for the other audited blocks, theme localization strings, or WPML/Polylang configuration were found in the inspected repository files.

WP Git Sync content exports provide an initial, incomplete usage inventory: `page/2.md` stores the home `acf/intro` values and references reusable block 840; `wp_block/840.md` contains `acf/about`; `page/560.md` contains `acf/join`; and Hero blocks appear in multiple page exports and `post/552.md`. No `acf/mission` or `acf/with-us` references were found in the inspected `page/`, `post/`, or `wp_block/` exports. Treat these as repository snapshot evidence, not proof of current live-site usage or non-usage.

The block library derives the registered block slug from the template filename and scans `views/blocks` by default; the directory list can be changed by the `timber/acf-gutenberg-blocks-templates` filter. No such override was found in this theme. Consequently, `view/blocks/intro.twig` does not currently collide with `views/blocks/intro.twig`; if the singular directory is added to that filter, both filenames would produce the `intro` slug. `composer.json` lists Timber and `timber-acf-wp-blocks`, but this alone does not establish which multilingual plugin will be used or how its ACF integration is configured.

## Decisions for Polylang Free

1. Use Polylang Free for language management and page/post translation links. ACF Pro remains a separate theme requirement for ACF blocks and repeaters.
2. ACF block attributes live in each page's Gutenberg content. Translate manually by copying the page structure and editing the translated page's values. Polylang Free does not supply the Pro XLIFF/DeepL workflow or Pro ACF translation integration; do not use `pll_acf_default_translation_setting`.
3. Mine the WP Git Sync exports for block references and stored field values, then confirm usage against any available live content not represented in the exports. Current exports show `acf/about`, `acf/join`, `acf/intro`, and `acf/hero`; they do not show `acf/mission` or `acf/with-us`. Check whether `about-us.twig` or `with-us.twig` is referenced outside the exports before consolidating. The minimal `view/blocks/intro.twig` is not scanned by the default block-library directory configuration; confirm there are no external references before removing it.
4. Reusable blocks referenced by translated pages need independent translated copies and language-specific references. Preserve the Polish originals. Translate visitor-facing prose and labels; copy images and language-neutral facts where appropriate, translate alt text, and use language-specific destinations where needed.

## Content and field design

- Create explicit, stable ACF field names grouped by block and semantic purpose; avoid one generic field shared across unrelated templates.
- Use text fields for short labels/headings, textarea or WYSIWYG only where formatting is required, URL/link fields for destinations, image fields for images, and repeaters for team members, cards, timeline entries, and metrics.
- Preserve meaningful rich text such as the emphasized portion of the mission headline using a safe structured field or controlled formatting. Do not depend on translated copy containing arbitrary HTML.
- Define rich-text handling explicitly for every existing and migrated field: plain text must be escaped, while intentionally formatted content must pass through an allowlisted sanitizer (for example, `wp_kses_post` in the PHP rendering path or an equivalent established safe filter). Existing Hero values contain `<em>` and `<br>` despite `headline` being registered as a text field; update that field contract and verify existing values before changing its Twig rendering. Do not rely on the current `<em>` replacement alone as the sanitization policy.
- Move hard-coded internal paths to link fields or language-aware page references so translated buttons resolve to the corresponding translated destination. Move mailto subject/body text into translatable fields or construct language-specific values from editable copy; keep recipient addresses configurable and non-translated unless needed.
- Keep section anchor IDs such as `rejs`, `o-patronce`, and `dolacz` stable when moving or conditionally rendering their sections, or update stored links that target them. The home-page export currently contains `/#rejs` and `/#o-patronce`.
- When migrating the membership mailto from `views/blocks/join.twig`, recover and preserve the intended message rather than copying its malformed-looking `%członkiem` escape verbatim.
- Make image alt text editable and translatable. Store image selections as ACF image fields rather than hard-coded upload URLs where practical.
- Document translation intent for every field: translate visitor-facing copy and accessibility text; copy/shared language-neutral values; use language-specific URLs where destinations vary by locale. Polylang Free does not enforce these as ACF field modes.
- Keep CSS classes, SVG path data, layout markup, and purely decorative numbering out of content fields unless editors need to change them. Treat visible timeline codes and metric values as content only if editors should control them.
- Preserve or deliberately replace the `.intro`-scoped responsive CSS in `functions.php` when changing or consolidating the Intro block.

## Implementation sequence

1. Inventory block references and stored ACF values in the WP Git Sync `page/`, `post/`, and `wp_block/` exports. Use those values, including `page/2.md`'s Polish Intro values, as migration inputs; record that exports may not represent all live content.
2. Confirm active blocks against the live content sources available to the project. In particular, verify `about-us.twig`, `with-us.twig`, `mission.twig`, and the minimal `view/blocks/intro.twig`; the latter is outside the block library's default `views/blocks` scan path and is not referenced in the inspected exports.
3. Use Polylang Free's page translation workflow. Do not assume per-field ACF controls or Pro-only XLIFF/DeepL support.
4. Document intended field behavior for editors rather than claiming Polylang Free enforces ACF field modes. Update the existing Hero and Intro field groups rather than duplicating them. Define field groups for the remaining canonical blocks with labels, field names, types, required status, and conditional/repeater structure in the implementation notes.
5. Populate or map Polish values from existing saved ACF block data and current Twig copy before changing rendering, so the Polish pages can be compared without content loss.
6. Update Twig templates to render ACF values, links, images, and repeaters. Keep HTML structure and styling in Twig; escape plain text and apply the explicitly selected allowlist sanitizer to formatted content. Correct the Hero `headline` field type/rendering contract before altering its current markup output.
7. Replace hard-coded internal URLs and Polish mailto text with language-aware/configurable values. Review the home breadcrumb, alt attributes, mailto encoding, anchor targets, and any live fallback copy.
8. Consolidate duplicate templates only after confirming usage and migration of existing block data. Keep section IDs stable or update stored links when sections move. Remove the placeholder `view/blocks/intro.twig` only after checking sources beyond the inspected exports and documenting the decision; preserve or replace its `.intro`-scoped CSS deliberately.
9. Add or update ACF field registration/export artifacts according to the project's chosen deployment method, and document the required multilingual plugin configuration.
10. Create a translated test page, enter translations for every block, and verify frontend output, translated internal links and anchors, fallback behavior, editor usability, escaping/sanitization, and image alt text in each configured language.

## Acceptance criteria

- No visitor-facing Polish prose or UI labels remain hard-coded in active Twig templates; language-neutral identifiers and deliberately shared facts are documented exceptions.
- Translation guidance documents which visitor-facing fields should be translated and which structural/shared values should be copied; it does not claim Polylang Free enforces per-field ACF modes.
- Polish content matches the current page after migration, with no lost copy or broken links.
- A second-language page can independently translate headings, prose, labels, CTA copy, alt text, and language-dependent links without editing Twig.
- Repeater items (team, cards, metrics, timeline) can be translated and reordered as intended.
- Duplicate/placeholder blocks have a documented keep, consolidate, or remove decision.
- All active template references resolve to defined fields, and frontend/editor checks pass for Polish and at least one additional language.

## Risks and open implementation details

- Polylang Free is selected. Its manual page translation workflow must be followed; do not assume Pro field controls, XLIFF, or machine translation.
- Duplicate blocks may already be used on pages. Consolidating them without checking stored content can break existing pages.
- Current hard-coded Polish URLs and mailto query strings need language-aware handling; translating only visible labels would leave journeys and form content in Polish.
- Existing stored Hero headlines contain markup while the ACF field is registered as text; changing escaping or field type without preserving and sanitizing that markup can alter output.
- In-page links depend on section IDs, and Intro has scoped CSS in `functions.php`; template consolidation must account for both.
- Avoid assigning translation work to ACF options/global fields unless the content is intentionally shared across pages; block-level content should follow the translated page/block model.
