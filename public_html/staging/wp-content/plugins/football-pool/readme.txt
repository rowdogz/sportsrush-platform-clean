=== Football Pool ===
Contributors: AntoineH
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=S83YHERL39GHA
Tags: pool, football, prediction, sports, game
Tested up to: 6.6
Stable tag: 2.12.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin adds a fantasy sports pool to your blog. Play against other users, predict outcomes of matches and earn points.

== Description ==
This plugin adds a fantasy sports pool to your blog. Visitors of your website can predict outcomes of matches and earn extra points with bonus questions. Every player can view scores and charts of the other pool contenders.

This plugin installs some custom tables in the database and ships with match information for the FIFA 2022 World Cup, but it can be easily populated with the match info for other championships or sports. Please note that deactivating this plugin may also delete all the plugin's data from the database, so please make sure the 'keep data on uninstall' option on the settings page is enabled if you don't want to lose your data (it is enabled by default since version 2.3.1).

I originally coded this pool in PHP as a standalone website for the UEFA 2000 championship and rewrote the damn thing several times for every European Championship and World Cup since. Every year I added new features. In 2012 I decided to rewrite it as a WordPress plugin and uploaded it to the plugin directory. I hope you like it.

A special thank you to all the users of the plugin that donated some money! And also to the translators that found time to translate the many labels in this plugin. And thanks to all the users that reported bugs and helped improving the plugin.

**Features**

* Users can predict match outcomes.
* Automatic calculation of the pool ranking. Or define your own custom ranking for a group of matches.
* You can add bonus questions for extra fun (single answer and multiple choice).
* Add your own teams and match info to use the plugin for another (national) competition.
* Import or export the game schedule.
* Automatic calculation of championship standing.
* Configurable scoring options.
* Use the built in pages and/or shortcodes to add the pool to your blog.
* Use different leagues for your users (optional).
* Users have charts where their scores are plotted over time. And they can compare themselves to other players. (Only available if Highcharts chart API is downloaded separately, see Help for details).
* Several widgets and shortcodes to display info from the championship or the pool.
* Extra info pages for venues and teams.
* Add your own functionality via filters and actions (see help page in the admin or [this post](https://wordpress.org/support/topic/extension-plugins-for-the-plugin-using-hooks "Football Pool Support Forum") in the forum for some examples).
* WP-CLI support for calculating the user ranking (much faster than a calculation via the admin).
* WP-CLI support for importing match results via a csv file.

**Documentation**
The plugin has a help file in the admin that contains a lot of information. But if you like a step by step tutorial, I can recommend the following: Janek from WP Simple Hacks website made a very nice [guide about my plugin](https://wpsimplehacks.com/how-to-create-a-football-pool-site-with-wordpress/). It even has a video where he explains how to set up the plugin.

**Other things**

* This plugin requires WordPress 4.8 or higher, PHP 7.4 or higher and jQuery 1.4.3 or higher.
* If you want to use the charts feature, please download the [Highcharts API](http://www.highcharts.com/download) (see "Installation" or the plugin's Help page in the WordPress admin for details).

If you find bugs, please contact me via the [support forum](http://wordpress.org/support/plugin/football-pool). If you like the plugin, please rate it on the [plugin page on WordPress.org](http://wordpress.org/extend/plugins/football-pool/).

== Installation ==
To use your own custom translation see the <a href="http://wordpress.org/extend/plugins/football-pool/faq/">FAQ</a> for more information on translating the plugin.

1. Upload `football-pool.zip` in the plugin panel (Plugins &raquo; Add New &raquo; Upload Plugin) or unzip the file and upload the folder `football-pool` to the `/wp-content/plugins/` directory on your server.
2. Activate the plugin through the `Plugins` panel in WordPress.
3. Edit the plugin configuration via the admin menu.
4. Optional: add the pages for the pool to your menu, or use some other method to link to the pages.
5. Optional: add the "Football pool" widgets to your sidebar.
6. Optional: add bonus questions.
7. Optional: 'upgrade' existing users in your blog to pool players.
8. If you want to use the charts feature please download the [Highcharts API](http://www.highcharts.com/) and put the `highcharts.js` file in the following path: `/wp-content/plugins/highcharts-js/highcharts.js`. Make sure you use the classic js file including the styling. If you use the theme-less version, then you'll also need to include the highcharts.css code in your theme.

After the pool has been set up, all you have to do is monitor the users that subscribe and fill in the right scores for the matches and the right answers for the bonus questions.

== Frequently Asked Questions ==

= Wow, there are a lot of options. Do I need to change them? =
You can, but it's not necessary. With default settings the plugin should be fine. You can play around with the options before you start the pool.

= The ranking calculation shows an estimated time left of several hours. Why? =
The calculation of the total amount of time left is based on the time a single step took to complete and this is multiplied by the total number of steps remaining for the calculation. If the step sizes (which can be set in the wp-config.php) of the first calculations are much larger than the calculation steps that follow, then at first the total time calculation may be too high.

Or maybe you just have a huge database of users, rankings and matches. If that is the case, then the calculation could be right and the total time for the calculation just takes ages to complete. As a reference, I tested with 2000 users, 50 matches, a couple of bonus questions and 3 custom rankings on my laptop and that calculation took approx. 45 minutes to complete with default step sizes. And when doing that same calculation on the command line using WP CLI, it took less than 10 minutes to complete.

= Do you have a theme that I can use with the plugin? =
No. I'm not a designer, so I don't have the skills to make one.

= I installed the plugin, but there are no matches. What happened? =
Since version 2.0.0 the plugin does not add matches on first install. But it does contain an example match schedule as an exported csv file. Go to the Matches admin page and do an import of a schedule file ("Import matches") if you want to use this example file.

= Do I need the "Predictions" page? =
Yes and no. The plugin needs this page to display predictions of users. So don't delete it. But you can remove it from your menu (WordPress Admin &raquo; Appearance &raquo; Menus).
Some themes or WordPress configurations automatically put all top level pages in the navigation. See information from the theme maker on how to make a custom menu or how to exclude pages from the menu.

= I want to use the plugin for a national competition. Is that possible? =
Yes. There are two ways to do this:
1. Upload a game schedule in the admin. Make sure you understand the required format; you can find an example in the plugin's /data/schedules folder.
2. Use the admin screens to add all the teams, groups, match types, matches, etc.

And, of course, choose a theme or make one yourself that fits your competition or blog.

= The charts are gone! What happened? =
I had to remove the required library because of WordPress plugin license policies. If you want to enable the charts then see the Help page in the WordPress admin for details on how to install the required library.
Also, please double-check that you didn't enable the 'simple calculation method' in the plugin options. This calculation method does not calculate and store all historic data, which makes it faster, but with the downside of not being able to render charts.

= I don't see my blog users as players of the pool. =
Go to the WordPress Admin &raquo; Football Pool &raquo; Users screen and check if these users are added in a league (if you are using leagues). Newly registered users are automatically added, but users that already existed in your blog have to be updated in the admin screen. In order to make them a player in the pool add them to a league and save. If you delete a league, then the users in that league must be placed in another league.
If you're not using leagues, then make sure the users are not removed from the pool via the Users screen.

= Is there a translation available? =
See <a href="https://translate.wordpress.org/projects/wp-plugins/football-pool/language-packs/">this page</a> for the available language packs.

If you want to make your own translation, please visit the <a href="https://translate.wordpress.org/projects/wp-plugins/football-pool/">translate.wordpress.org</a> site and view the possibilities for your language. You can also use an editor like Poedit (http://www.poedit.net/) to create the translations and upload the results to the aforementioned website. Make sure you reach out to a <a href="https://translate.wordpress.org/projects/wp-plugins/football-pool/contributors/">PTE</a> for your language to get your translations approved. Or if there is none, then you can apply for the job yourself. Also see the <a href="https://make.wordpress.org/polyglots/handbook/translating/first-steps/">first steps</a> page for more information on the general translation process within the WordPress universe.

If you have a custom translation, you can put the translation files in the wp-content/uploads/football-pool/languages dir (create it, if it doesn't exist yet) and use <a href="https://www.dropbox.com/s/o6q48rg09aunyj0/football-pool-use-custom-translation.php?dl=0">this extension plugin</a> to load it.

The default content for the rules page is in the `rules-page-content-*locale*.txt` file (e.g. `rules-page-content-nl_NL.txt`) and is not handled by the polyglot. If you've made your own translation and mail it to me, I'll add it to the plugin and give you the credits.

= I installed the plugin, but it does not look like your screenshots. =
That's correct. The plugin has some basic styling to position or size elements, but it will not change your entire blog or automagically fit perfect in your website. You will have to adjust the styling yourself to make it look good in your site. Change your theme to overwrite/change the style of the plugin, or use a plugin to add extra custom stylesheets. Please do not change the CSS in the plugin folder; if you ever update the plugin, all your hard work will be gone.

== Localizations ==

If someone wants to help translate the plugin in another language, or help keeping the existing translations up-to-date, please visit the plugin's page on <a href="https://translate.wordpress.org/projects/wp-plugins/football-pool">translate.wordpress.org</a>. Please read the information on the website carefully if you want your translation work to be reviewed and approved: <a href="https://make.wordpress.org/polyglots/handbook/translating/after-your-contribution/">After your contribution</a>.

A big shout-out to all the <a href="https://translate.wordpress.org/projects/wp-plugins/football-pool/contributors/">translation contributors and editors</a> that helped translating the plugin so far. Thank you!

The FAQ contains information on how to use a custom translation. I will keep including the pot language file in the plugin as a starting point for custom translations.

== Shortcodes ==
The plugin has the following shortcodes. See help page in the admin for extra info.

* fp-predictions
* fp-predictionform
* fp-matches
* fp-match-scores
* fp-question-scores
* fp-next-matches
* fp-last-matches
* fp-user-score
* fp-user-ranking
* fp-ranking
* fp-countdown
* fp-group
* fp-link
* fp-register
* fp-totopoints
* fp-fullpoints
* fp-goalpoints
* fp-diffpoints
* fp-jokermultiplier
* fp-plugin-option
* fp-league-info
* fp-chart-settings/fp-stats-settings
* fp-user-list
* fp-money-in-the-pot
* fp-last-calc-date
* fp-next-match-form

== Incompatible plugins & themes ==

The following plugins have been reported as not compatible with the Football Pool plugin. If you have a solution and/or are the author of the plugin you can contact me on wordpressfootballpool [at] gmail [dot] com. If you're having problems with another plugin that is not in the list, please let me know.

Basically, every caching solution should be tested with care.

* DB Cache Reloaded Fix (v2.3)
* Cimy User Extra Fields (v2.6.1) when using the email confirmation option
* Easy Timer (for football pool version 2.3.8 and below)
* Theme Gadgetry (ThemeFuse framework)
* memcached

Some themes prevent the plugin from displaying its content. See <a href="https://wordpress.org/support/topic/theme-compatibility-73/#post-17811227">this post on the forum</a> for a tip on how to resolve this.

== Screenshots ==
1. Predict matches via a form in your WordPress site
2. Score charts of multiple players
3. Match predictions and scores per user
4. Group rankings
5. User ranking
6. Football Pool is packed with several widgets
7. Admin Screen: plugin options
8. Admin Screen: change match outcomes
9. Admin Screen: add a shortcode via the classic editor

== Upgrade Notice ==
= 2.12.0 =
Please back up your database before updating!!

= 2.11.0 =
Minimum PHP requirement has changed to 7.4.0.

= 2.10.0 =
After upgrading to version 2.10.0 a ranking calculation is needed (e.g. from the options page). Please back up your database before updating!!

== Changelog ==
= 2.12.2 =
* Bug fix: some parts of the code did not cast the league to int when passing it to the get_pool_ranking_limited() function. Thanks to Colin for reporting.

= 2.12.1 =
* Bug fix: Accidentally removed tags around title for the widgets. This has been restored. Thanks to @spaniole for reporting this.

= 2.12.0 =
* New: Goal difference bonus now has a setting that defines on which scores the bonus applies (existing installs will keep using the old rules until you change the setting).
* New: Match predictions view on the statistics page now has some extra CSS classes on the table row to show how the score was build up (e.g. toto score plus goal bonus).
* New: Added a print stylesheet for the audit log to remove some clutter from the print of the page.
* New: Extra option to enable or disable showing the admin answer for closed bonus questions on the prediction form.
* New: Added option to send an email notification when a new shoutbox message was saved. Uses the default wp_mail() function.
* Update: Changed the maximum amount of characters for the shoutbox messages to 65,535 (but default setting is still a max of 150 chars).
* Update: When a shoutbox message save fails, it will show a message for the user and keep their text in the input field to try again.
* Update: Also added the `today` CSS class to the matches view.
* Tweak: Some clean-up in the widget classes.
* Tweak: Fixed some deprecation warnings (explode(): Passing null to parameter #2).
* Tweak: Added some wp_doing_cron() checks.
* Tweak: Some code reformatting.
* Bug fix: In rare occasions the first chart for the statistics page was defined as a line chart, where it should have been a column chart. Thanks to @pekos for reporting the bug.
* Bug fix: Removed a XSS vulnerability in the bonus questions admin.
* Bug fix: Removed a XSS vulnerability from the teams page and matches page.

= 2.11.10 =
* Bug fix: Removed a XSS vulnerability in the bonus questions admin.

= 2.11.9 =
* Update: Added the Copa América 2024 championship schedule.
* Bug fix: Removed a bug in the CSV importer.

= 2.11.8 =
* Bug fix: Removed some PHP8 language constructs that caused a critical error on PHP7 installs. Thanks to @batigol09 and @ryan944 for reporting.

= 2.11.7 =
* Update: Also the Football Pool admin roles (football pool admin, match editor and question editor) get the WP toolbar when logged in. The users can set their personal preference (show or hide) in their profile.
* Update: Added some extra classes to the match cards on the prediction form: `no-prediction` for matches with no prediction, `today` for matches that are on the current day.
* Bug fix: Fixed an "Undefined index" notice in the CLI command for creating test users when there are no matches.

= 2.11.6 =
* Update: Fixed the venues for the final rounds in the UEFA EURO 2024 championship schedule.

= 2.11.5 =
* Update: Added the UEFA EURO 2024 championship schedule.
* Bug fix: Shortcode [fp-countdown] now exits gracefully when it is set to countdown to a match, but no first or next match is found. Thanks Frans Jansen for reporting this error.

= 2.11.4 =
* New: [fp-next-match-form] shortcode to show a form of only the next match(es).
* Tweak: Small change in the test data CLI method.
* Tweak: Bumped the jQuery version for the TinyMCE dialog to 3.7.1.
* Bug fix: Removed a XSS vulnerability from some shortcodes (low priority).

= 2.11.3 =
* Updated: Removed admin icon from plugin assets and added SVG base64 URI in menu definition.
* Tweak: Removed deprecated warning for dynamic property in Football_Pool_Pool class.
* Tweak: Some refactoring of code.
* Tweak: Stop loading of plugin when doing cron actions (only needed if I ever decide to add cron actions).
* Bug fix: In some edge cases the setting "Fix incomplete predictions" could cause points to be awarded when both scores for a match are missing. Thanks fimo66 and Markus Höcker for reporting the bug.

= 2.11.2 =
* Tweak: Some refactoring of code.
* Updated: Changed the required PHP version to 7.4.
* Bug fix: League dropdown in the user admin caused a fatal error in certain cases. Thanks Ron Robinson for reporting the issue.

= 2.11.1 =
* Bug fix: fp-matches caused a fatal error for empty parameters. Thanks @wongjowo for reporting the issue.

= 2.11.0 =
* New: Audit log in the admin for the administrator to view all saves that users did in their predictions.
* New: Added cache group to WP object cache calls to be able to exclude it from persistent caching plugins.
* New: Option to consider a null value for an incomplete prediction (e.g. only home score entered) as valid and default the missing value to 0.
* New: Shortcode [fp-last-matches] that displays the last started matches before a certain date. Similar parameters as [fp-next-matches].
* New: Added parameters to the [fp-user-list] to limit the output with the 'num' parameter and to display only the latest registrations (based on and ordered by the WP_User's user_registered field) with the 'latest' parameter.
* New: Added 'is_favorite' parameter to teams. This parameter is used to add an extra CSS class to matches.
* Updated: AJAX saves on the frontend are now disabled by default and I made it a setting in the options screen (but it can also still be changed via the FOOTBALLPOOL_FRONTEND_AJAX constant in the wp-config).
* Updated: Changed the required PHP version to 7.3.
* Updated: Removed deprecated code for loading custom MO files. This should be done via the 'override_load_textdomain' filter. See FAQ for more info.
* Updated: Removed league ID from the sorting of the ranking.
* Tweak: Added different admin screen option 'items per page' settings for bonus questions and user answers.
* Tweak: Removed the old wp_enqueue_media check for WP versions lower than 3.5 (this is no longer needed).
* Tweak: Fixed some deprecated warnings (tested in PHP 8.1).
* Tweak: Toast on the prediction form now has a default z-index of 10.
* Tweak: Minor changes to the bonus question CSS.
* Bug fix: The countdown shortcode for the first match removed one element of the matches array and this caused trouble for other elements in the plugin that use the same array reference. Thanks @angelo079 for reporting and @shuhads for helping to sort this out.
* Bug fix: Removed duplicate calculation buttons when removing multiple match types at once.
* Bug fix: Fixed the Bonus question pie chart. Thanks @fimo66 for reporting the issue.

= 2.10.3 =
* Updated: Changed the match schedule because some matches had the wrong UTC time (last games of the group phase and in the final rounds).

= 2.10.2 =
* Updated: Changed the match schedule because the first couple of lines had the wrong year.

= 2.10.1 =
* Updated: Changed the match schedule because FIFA changed the Qatar vs. Ecuador match date.
* New: Added a setting to also show the actual result on the prediction form.
* New: Added a setting to be able to disable the 'unsaved changes check' on the prediction form.
* New: Shortcode [fp-user-score] now also supports the "use_querystring" parameter.
* New: Added a setting to disable the automatic selection of the logged on user for the compare function in the charts.

= 2.10.0 =
* New: Multiple joker support (for the entire pool or per match type). I also renamed the Joker to 'multiplier'.
* New: Multiplier, bonus question answers and match predictions are now automatically saved via AJAX calls on the front-end (on change). AJAX saves can be disabled via the `FOOTBALLPOOL_FRONTEND_AJAX` constant in the wp-config if you do not like the new asynchronous method.
* New: The default delimiter for CSV files (match import) is changed to a comma. If you want to keep using the old delimiter (semicolon), you can override this setting in the wp-config (see help page for details).
* New: If you want to use an alternative date format in a matches CSV file, you can now define constant `FOOTBALLPOOL_CSV_DATE_FORMAT` in your wp-config file. The constant uses the date format convention of PHP's DateTime object and applies to both import and export files.
* New: CLI command 'football-pool test-data' that creates test users in your database with random predictions (for testing purposes).
* New: Added support for the WP Personal Data Exporter tool. Users can request to export their personal data which now will also include their Football Pool data (league name, match predictions and bonus question answers).
* New: Added support for the WP Personal Data Eraser tool. When the option is set to true (defaults to false) the plugin will also erase predictions and question answers for a user when using WP's Personal Data Eraser tool.
* Tweak: Renamed the [fp-scores] shortcode to [fp-match-scores]. Old name is deprecated and will remain available for a couple of versions before I will remove it.
* New: [fp-match-scores] shortcode can now also show the row total via the 'show_total' parameter.
* New: [fp-match-scores] shortcode can now also output the user's prediction per match (instead of or next to the points).
* New: [fp-question-scores] to show a matrix of users and the scores they got on questions (uses same principles as the [fp-match-scores] shortcode for matches).
* New: [fp-last-calc-date] to show the date and time of the last ranking calculation.
* New: Two new admin roles (match editor and question editor).
* New: Bonus question output now also has a filter that can be used in an extension. Also, the code for user view and prediction page is combined into one output function (to get the same HTML structure for the question blocks).
* New: Bonus question statistics view now also has a template that can be overwritten with a filter.
* New: Added 'joker_used' indicator to the score history tables.
* New: Added constant `FOOTBALLPOOL_TOP_PLAYERS` that you can set in the wp-config if you want to show a different number of players in the default statistics page (default is 5).
* Tweak: Bumped the jQuery version for the TinyMCE dialog to 3.6.0.
* Tweak: Some small additions to the help page.
* Tweak: Updated the icon font that comes with the plugin (also some class names have changed).
* Tweak: Optimized the ranking query for big data sets when selecting ranking for a small league (sub set of users).
* Tweak: Changed all colors in the admin to match the new admin WP 5.7 color palette.
* Tweak: Added floating 'back to top' button to all admin pages and a scroll progressbar.
* Tweak: Minor changes to the match table CSS.
* Tweak: Clean up of bonus question CSS.
* Tweak: Added maxWidth and maxHeight to the ColorBox modal that is used for displaying team photos on the front-end.
* Tweak: Question's answer-before-date is now also localized on the front-end.
* Tweak: Score calculation now throws a fatal error when the default ranking is missing in the database.
* Tweak: Increased the max int sizes for the counter columns in the scorehistory tables.
* Bug fix: Fixed a compatibility problem with the Max Mega Menu plugin (thanks Holger for reporting this).
* Bug fix: User profile page showed the wrong active league for the user.
* Bug fix: Match types could be deleted when there were still matches linked to it. This resulted in orphaned matches in the database. Thanks Andreas Neubrech for reporting this.
* Bug fix: When a logged in user, but not a player in the pool, visited the stats page, then an empty page was shown, instead of the top X players.
* Bug fix: When a logged in user, but not a player in the pool, visited the user page, an incorrect page was shown.
* Bug fix: When score date is automatically filled by the plugin, then the question was not included in the calcution when immediately starting the calculation. Thanks fimo66 for reporting this.
* Bug fix: Fixed some translations. Thanks digiblogger for reporting this.
* Clean-up: I removed all old translation files from the package. Contents of the translation files were imported to the WordPress translation website and can be maintained from there. The POT-file is still available as start point for custom translations, or you can download a PO language file from the translation website.
* Clean-up: I removed the logout widget from the plugin since WordPress comes with its own widget.

= 2.9.7 =
* Updated: Prepared the widgets for the new Widget blocks admin that will be introduced in WP 5.8.
* Tweak: Changed the moment when the admin menu gets initiated.
* Bug fix: When a joker is used and activated, the joker icon should be disabled on the form. This did not work when using the date descending sort for matches or when the 'only open matches' plugin was activated (thanks Roy te Lindert for reporting).
* Bug fix: In some cases the v2.9.0 db update script was not executed (thanks @potjekak for reporting).

= 2.9.6 =
* Bug fix: Flex layout for bonus questions was broken on the user page with a combination of certain settings and linked questions (thanks Frans Jansen for reporting).
* Bug fix: Score date input for bonus questions showed the current date when you saved the form with an empty score date (thanks fimo66 for reporting).
* Tweak: Changed the group standing rules to make it easier to override the sorting manually, because the rules of the UEFA for the UEFA 2020 championship did not match the general rules in the code (thanks af3 for reporting).

= 2.9.5 =
* Updated: EURO 2020 schedule.

= 2.9.4 =
* Bug fix: Football Pool widgets couldn't be saved anymore (thanks to dar26ber and Ernst for reporting).

= 2.9.3 =
* Tweak: Database optimization for scorehistory table. Retrieving data from the table is now much faster, which should improve the performance of the ranking page, widget and shortcode.
* Tweak: Renamed some indices in the database for more consistency.
* Bug fix: Shoutbox widget not showing an input for new messages for logged in users.
* Bug fix: Plugin labelled some dates in format "Y-m-d H:i:s" as invalid for the import. Check was updated to also support this format (thanks to Kristin for supplying the data that helped me detect this problem).
* Bug fix: Setting the matches sort method to an option with match type first in the plugin options caused a database query to fail. Result was an empty prediction form (thanks to Kristin and sopanstha for identifying and helping to solve this problem).
* Bug fix: Teams class did not declare the comments property.

= 2.9.2 =
* Bug fix: Fixed error in the calculation step 'compute_ranking'. Larger data sets gave a problem in the AJAX JSON handling.
* Bug fix: TinyMCE dialog for adding shortcodes showed only one user-defined ranking in the ranking selector.
* Bug fix: Fixed display of form with shortcode [fp-predictionform] with the use of the match type parameter.
* Tweak: Refactored some code.

= 2.9.1 =
* Changed PHP version requirement to PHP 5.6 or higher.
* Tweak: Added "open" or "closed" CSS class to bonus questions to indicate their status.
* Bug fix: The check for joker saves was not working correctly when using invisible match types or the [fp-predictionform] shortcode with only a subset of matches.
* Bug fix: League detection bug in Football_Pool_Pool class constructor.

= 2.9.0 =
* New: Added sorting method options for bonus questions.
* Tweak: Updated styling for bonus questions and we now show the admin answer next to the user answer.
* Tweak: Updated the standard styling for the prediction table (classic layout only) a bit to make sure all elements are visible. Still needs to be changed to match your theme's layout.
* Bug fix: Option "user_page_show_predictions_only" did not work for bonus questions ('Undefined index: answer').
* Bug fix: Option "user_page_show_correct_question_answer" did not work for linked bonus questions.
* Bug fix: 'Undefined index: league_id' warning on the ranking page when switching between leagues enabled and leagues disabled without doing a recalculation.
* Bug fix: Fixed the "An active PHP session was detected" warning in the Site Health scan (thanks fimo66 for reporting).
* Bug fix: User predictions table showed a zero in the score column for users that did not have a prediction when option "Always show predictions" is enabled (should be left blank).
* Bug fix: CLI command 'import' failed with an error in test mode when a match id was not found.

= earlier versions =
* Full changelog can be found [here](https://plugins.svn.wordpress.org/football-pool/trunk/changelog.txt "Changelog file").