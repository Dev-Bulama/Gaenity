# Gaeinity Community Suite

Gaeinity Community Suite is a multipurpose community plugin that brings together resources, forums, polls, live chat, and expert matchmaking for entrepreneurs. The plugin is designed to inherit the active theme's typography and colours so it feels native to any site while supplying powerful community workflows out of the box.

## Requirements

- WordPress 5.8 or newer (tested up to WordPress 6.4)
- PHP 7.4 or newer
- Elementor 3.0+ (optional – for the bundled widget)

## Getting Started

1. Upload the `gaenity-community` folder to your WordPress installation's `/wp-content/plugins/` directory or install it as a zip via **Plugins → Add New → Upload Plugin**.
2. Activate **Gaeinity Community Suite**. During activation the plugin seeds three example resources, two example discussions, and publishes a "Gaeinity Community" landing page populated with the core shortcodes. All demo content can be edited or removed like any other WordPress post.
3. Visit **Pages → Gaeinity Community** to review the starter layout. Edit it in the Block Editor, Classic Editor, or Elementor by swapping shortcodes, removing sections, or adding your own content.
4. Add additional pages or templates using the shortcodes below to embed specific sections anywhere on your site.

## Shortcodes

The plugin registers a router shortcode and individual helpers for each feature. All shortcodes automatically enqueue the front-end assets and will match your active theme styles.

| Shortcode | Purpose |
| --- | --- |
| `[gaenity_community block="community_home"]` | Intro hero with key calls-to-action and feature highlights. |
| `[gaenity_community block="resources"]` or `[gaenity_resources]` | Resource grid with download modal. |
| `[gaenity_community block="register"]` or `[gaenity_register]` | Community registration form. |
| `[gaenity_community block="login"]` or `[gaenity_login]` | Lightweight login form that respects WordPress authentication. |
| `[gaenity_community block="discussion_form"]` or `[gaenity_discussion_form]` | Member discussion submission form with industry, region, and challenge filters. |
| `[gaenity_community block="discussion_board"]` or `[gaenity_discussion_board]` | Recent discussions list pulled from the `gaenity_discussion` post type. |
| `[gaenity_community block="polls"]` or `[gaenity_polls]` | Monthly benchmark poll with member-only voting. |
| `[gaenity_community block="expert_request"]` or `[gaenity_expert_request]` | "Ask an Expert" request form. |
| `[gaenity_community block="expert_register"]` or `[gaenity_expert_register]` | Expert registration workflow. |
| `[gaenity_community block="contact"]` or `[gaenity_contact]` | Contact form with marketing opt-in. |
| `[gaenity_community block="chat"]` or `[gaenity_chat]` | Lightweight community chat stream backed by private submissions. |

The router shortcode defaults to the community home block when no `block` attribute is provided.

## Elementor Widget

The plugin adds a **Gaeinity Community Block** Elementor widget located under the "Gaeinity Community" category. Drop the widget into any Elementor layout and choose the block you would like to display from the dropdown. The widget uses the same rendering pipeline as the shortcodes so Elementor previews will match front-end output.

## Theme Styling

Front-end CSS focuses on spacing and layout only. All typography inherits the active theme, so headings, buttons, and form controls automatically match global styles. If you need to override colours or spacing further, enqueue your own stylesheet and target the `.gaenity-*` class names.

## Forms and Data

All forms submit via WordPress' `admin-post.php` endpoint and create private entries under the **Community Entries** post type. Administrators can view submissions in the dashboard and export them if needed. Discussion submissions are stored separately as pending `gaenity_discussion` posts so moderators can publish them after review.

## Troubleshooting

- **Elementor preview shows a 500 error**: Ensure you are running Elementor 3.0 or newer. The plugin automatically detects whether Elementor expects the modern `register()` API or the legacy `register_widget_type()` method to keep compatibility with older releases. Clear any server-side caching and try again.
- **Shortcodes output raw text**: Confirm the plugin is activated and that you are using straight quotes in the shortcode syntax.
- **Seed content keeps reappearing**: Remove the `gaenity_community_seeded` option from **Tools → Site Health → Debug** or via `wp option delete gaenity_community_seeded` if you want the demo content to be generated again.

## Changelog

### 2.1.0
- Added activation seeding for resources, discussions, and a starter landing page.
- Documented all shortcodes and Elementor usage in this README.
- Improved Elementor widget registration for backward compatibility.
- Added resilient date formatting for stored submissions.

### 2.0.0
- Introduced shortcode-based architecture for every community feature.
- Added Elementor widget integration and front-end assets.

## License

This project is provided as-is for the Gaenity community initiative. You are free to customise and extend it within your WordPress installation.
