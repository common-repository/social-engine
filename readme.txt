=== Social Engine: Schedule Social Media Posts ===
Contributors: TigrouMeow
Tags: social, media, scheduling, facebook, instagram
Donate link: https://www.patreon.com/meowapps
License: GPLv3
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.7.0

Schedule and automate posts across social networks. Unlimited features and extensibility. Works with X, Facebook, Instagram, Pinterest, LinkedIn.

== Description ==
Take your social media game to the next level with Social Engine - the ultimate scheduling tool for your content and photos! And it's completely free! Unlike other tools like TweetDeck, Buffer, Falcon, and ContentCal, Social Engine has no limitations on scheduling posts. You have full control of the system once it's installed on your WordPress, and you can even customize it to your needs with easy-to-use coding.

Currently, it supports:

- Instagram
- Facebook
- X (Twitter)
- Mastodon
- LinkedIn (Pro Version)
- Pinterest (Pro Version)

Current features:

- Plan your SNS Posts with a pretty UI
- Create Post with Photos
- Automatically schedule your WP Posts
- Select images which has never been published on SNS
- Revive your old content
- Draft Support (Pro Version)
- Statistics, such views and likes (Pro Version)
- Revive Posts or Pages (Pro Version)

*Consider the Pro Version for additional one-to-one support, and to help me developing it. This plugin is, as you can guess, quite a lot of work! Many companies are running expensive platforms for the same services. I think they are way too expensive and not always made, so this is my attempt to make things cheaper and better. I need you!*

== No third-party service 💕 ==
Say goodbye to third-party services with Social Engine. Enjoy no limitations, no hidden costs (except for the optional Pro version), and the security of keeping your data to yourself. Plus, you can customize and automate everything to fit your specific needs. Note that you'll need to create your own "Facebook App" (and the same goes for Twitter, Instagram, etc.) It's easy and straightforward, and if you need any help, just check out my tutorial or ask a friend for assistance! 🤓

=== Extensibility ===
We provide actions, filters and an API so that you can extend the plugin the way you like. Please let us know what you would like to do, and we'll give you everything you need. Please check the section about extensibility in the [documentation](https://meowapps.com/social-engine/tutorial/#extensibility-api).

== Installation ==

1. Upload the plugin to your WordPress.
2. Activate the plugin through the 'Plugins' menu.

== Changelog ==

= 0.7.0 (2024/10/17) =
* Fix: Avoid missing the Featured Image when adding posts automatically.
* Fix: Publish immediately if there aren't any defaults in auto-schedule.
* Update: Various minor enhancements and fixes.

= 0.6.9 (2024/09/18) =
* Update: Schedule Posts for today if no defaults.
* Fix: A bunch of fixes (avoid re-renders and request conflicts, fixed empty template, etc).
* ✨ Social Engine is packed with features, very easy to use and basically free! Compared to other services, it's a bargain. However, it is not well-known. For this adventure to continue, please leave a nice review for [Social Engine on WordPress](https://wordpress.org/support/plugin/social-engine/reviews/#new-post). That will help a lot, thank you! 💖

= 0.6.8 (2024/08/01) =
* Update: Template works with Auto Schedule.
* Fix: Minor UI issues.

= 0.6.7 (2024/07/07) =
* Add: Templates for Social Posts.

= 0.6.6 (2024/06/28) =
* Fix: Fixed the Create Social Post button.
* Fix: Fixed empty times for Default Schedules.
* Fix: Various other minor fixes.

= 0.6.5 (2024/06/21) =
* Fix: Minor fixes.

= 0.6.4 (2024/06/15) =
* Add: Bearer Token for API.
* Update: Code cleanup, UI enhancements, minor fixes.

= 0.6.3 (2024/05/24) =
* Fix: Removed warnings.

= 0.6.2 (2024/02/02) =
* Add: Current file extension detail in Instagram post exceptions for clearer error reporting.
* Update: Modified Facebook embedded link handling to its own property, setting groundwork for other platforms.
* Fix: Improved error handling for requests and display on Linkedin user info for better user feedback.

= 0.6.1 (2024/01/20) =
* Fix: Handle AI Engine errors.
* Add: Public REST API (works the same was as with AI Engine).
* Update: Enhancements for LinkedIn and UI.

= 0.5.9 (2024/01/03) =
* Fix: Undefined post_content.
* 💫 Happy New Year!

= 0.5.8 (2023/12/25) =
* Add: Support for Facebook preview link integration.
* Fix: Issue with content exceeding limits in social post creation modal.

= 0.5.7 (2023/11/29) =
* Add: "Create Social Post" for CPTs.
* Fix: MediaSelector in "Create Social Post".
* Update: Link in posts with empty excerpts.
* Fix: StyledNekoModal and NekoModal warnings.

= 0.5.6 (2023/11/20) =
* Add: AI Vision support (requires AI Engine).
* Update: Enhanced the UI a bit.

= 0.5.5 (2023/10/23) =
* Fix: Updated and corrected outdated modals' buttons and post drafting issues.
* Add: Introduced links to Notion's documentation for enhanced user guidance.

= 0.5.4 (2023/10/10) =
* Fix: Fixed modals overlapping.
* Fix: LinkedIn.
* Update: Various enhancements, as well as tiny fixes that could cause issues.

= 0.5.0 (2023/09/12) =
* Add: New filter sclegn_post_data.
* Fix: Calendar display and draggable.
* Fix: LinkedIn Auth and Post.

= 0.4.9 (2023/04/30) =
* Fix: Twitter API v2 support.
* Update: Better handling of Twitter errors.
* Info: PHP 8.1.9 minimum is now required.

= 0.4.8 (2023/04/30) =
* Add: Blue badge support for Twitter.
* Add: Warnings when the content is too long.

= 0.4.7 (2023/03/28) =
* Add: Link to the URL for the Revive function.
* Add: New UI framework, lighter and better.

= 0.4.6 (2023/02/21) =
* Add: We can now create post for multiple accounts at the same time.

= 0.4.5 (2023/02/09) =
* Add: Support for Mastodon! My first post on Mastodon was made through Social Engine: https://piaille.fr/@meow/109834132065562645. Cool, right!? 🥳

= 0.4.4 (2023/02/08) =
* Add: Ligther version, less files.

= 0.4.2 (2023/02/04) =
* Add: Can now create Social Post directly from the Posts/Pages list.

= 0.4.1 (2023/01/27) =
* Fix: Revive function gives better instructions and warnings if needed.
* Fix: Some UI elements could be hidden by others.

= 0.4.0 (2023/01/14) =
* Fix: When the text was too long, it was overriding the placeholder.
* Fix: The counter of characters was annoying when the text was too long.
* Fix: The common dashboard was broken.

= 0.3.8 (2022/12/26) =
* Fix: Should be able to see today when we hide the previous days.
* Fix: Various JS fixes.

= 0.3.7 (2022/12/09) =
* Fix: Revive function now uses the correct date based on the settings.

= 0.3.6 (2022/11/30) =
* Add: Possibibility to find which images haven't been already uploaded.

= 0.3.5 (2022/11/26) =
* Add: Support for WordPress 6.1.
* Update: Latest packages and everything!

= 0.3.2 (2022/09/27) =
* Add: Handle errors from services better.
* Update: Bigger lightbox.
* Add: Revive (to create Social Posts automatically based on the current content)

= 0.3.1 (2022/09/06) =
= Update: The statistics data is stored more constantly.

= 0.3.0 (2022/08/31) =
* Fix: Stats for Facebook.

= 0.2.9 (2022/08/22) =
* Add: Better logs (especially for Twitter).
* Fix: It was not possible to edit at the same time similar content.

= 0.2.8 (2022/08/04) =
* Fix: Better refresh of the Published Social Posts.
* Update: More UI enhancements.

= 0.2.7 (2022/08/01) =
* Fix: The statistics are back.
* Update: Many enhancements in the UI.

= 0.2.5 (2022/07/25) =
* Update: Many enhancements in the UI.

= 0.2.3 (2022/07/11) =
* Add: Option to automatically schedule social posts for blog posts.

= 0.2.1 (2022/07/04) =
* Add: Calendar view.
* Update: Enhanced UI.

= 0.2.0 (2022/06/23) =
* Updated: Fix many things in the UI.

= 0.1.9 (2022/06/17) =
* Update: Removed the + within the main UI, and added a "New Post" button in the sidebar.
* Add: A modal to create social post.

= 0.1.8 (2022/06/14) =
* Info: Lot of fixes and improvements. We are still working on a beta, to make the UI more and more powerful. We will be working on new exciting features when everything is stable and nice. It's going to be awesome!

= 0.1.6 (2022/05/17) =
* Add: Gallery Post for Instagram.
* Add: Settings for Posting Times (more to come in the UI to make this really user-friendly).
* Update: Improved the way the posts are loading.
* Fix: Issue when uploading images and using the Uploader.
* Add: Draft is now an option disabled by default.

= 0.1.5 (2022/05/03) =
* Fix: LinkedIn.
* Fix: Number of characters depending on the service.

= 0.1.4 (2022/04/21) =
* Fix: There was issues with LinkedIn.

= 0.1.3 (2022/02/21) =
* Add: More icons in the admin, for clarity.

= 0.1.2 (2022/01/25) =
* Fix: Fresh builds with enhanced Neko UI framework.

= 0.1.1 (2022/01/25) =
* Add: UI improvements.
* Add: Sort the posts ASC or dESC.

= 0.1.0 (2022/01/21) =
* Add: Many UI improvements.
* Add: Support for Instagram.

= 0.0.9 (2021/12/07) =
* Add: The day can be locked now (that way, we can mark a day as "done" when preparing the posts).

= 0.0.8 (2021/12/03) =
* Update: Better icons and buttons.
* Add: Show parts of the content in the bar when posts are minimized.

= 0.0.7 (2021/11/22) =
* Info: Pro Version released on the store.

= 0.0.6 (2021/11/02) =
* Fix: So many more fixes and enhancements.

= 0.0.3 (2021/10/12) =
* Fix: A lot of them!
* Add: Support for LinkedIn.

= 0.0.2 (2021/09/14) =
* Info: First release.