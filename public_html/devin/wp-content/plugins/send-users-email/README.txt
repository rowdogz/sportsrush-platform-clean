=== Send Users Email - Email Subscribers, Email Marketing Newsletter ===
Contributors: paretodigital, metalfreek
Donate link: https://sendusersemail.com/?utm_source=wp_repo&utm_medium=link&utm_campaign=donate_link
Tags: email users, email subscribers, email system users, send email, email all users
Requires at least: 5.7
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send Users Email provides a way to send email to all system users either by selecting individual users or user roles.

== Description ==

This plugin simplifies email communication on your WordPress site. With the free version, you can send personalized emails to individual users or roles, style emails with custom CSS, and log sent emails for 15 days.

The [PRO version](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=pro_version) adds powerful features like an email queue system to stay within provider limits, email scheduling, user groups, templates, and extended email logs. Whether you're managing large email lists or need more control over your communication, the PRO version offers the tools to boost your efficiency and improve email delivery.

This plugin uses `wp_mail` function to send emails. Any other E-Mail plugin that tap on `wp_mail` functions works with this plugin.

⚡ [PRO Version](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=pro_version) (Free Trial) for emailing groups, roles and batch campaigns

📚 [Documentation](https://sendusersemail.com/docs/how-to-install/?utm_source=wp_dir&utm_medium=link&utm_campaign=docs) | 🌟 [PRO Features](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=features#pricing) | 🔥 [Get PRO](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=get_pro)

== Free Version Features ==

- **Send Emails to Users**: Send emails directly to users on your WordPress site.
- **Send to Individual Users**: Select specific users to send personalized emails.
- **Send to User Roles**: Send emails to entire user roles, such as administrators, subscribers, or contributors.
- **Personalized Emails with Placeholders**: Use placeholders to automatically insert personalized details, like the recipient’s name, into your emails.
- **Custom CSS for Email Styling**: Add custom CSS to your emails to match your brand's style and improve presentation.
- **Social Icon Links**: Easily add social media icons with links to your emails.
- **Error Logging**: If any errors occur during email sending, they are logged for troubleshooting.
- **Email Logs (15 Days)**: The content of sent emails is stored for 15 days, giving you access to recent communication history.

== PRO Features ==

- **All Free Features Included**: Access all the functionalities available in the free version.
- **Email Queue System**: Send emails in batches to stay within your email provider's limits and improve delivery rates.
- **Queue Scheduling**: Schedule emails to be sent at a later time or date, giving you more flexibility in managing campaigns.
- **User Groups**: Create and manage user groups to send targeted emails to specific segments of your audience.
- **Email Templates**: Save time by creating and reusing email templates for commonly sent messages.
- **Add your own template**: Add your own HTML/CSS email template and use it as your default template.
- **Pre-designed Email Styles**: Choose from well-crafted, ready-to-use email styles with various color schemes, no CSS required.
- **Subject Line Placeholders**: Personalize email subject lines with placeholders for higher engagement.
- **Default Email Styles**: Set a default email style for consistency across your communications.
- **Optional Queue Default Setting**: Set whether the email queue should be used by default for all emails.
- **Extended Email Logs (90 Days)**: Logs are retained for 90 days, adjustable in settings, giving you a longer email history for auditing and review.
- **Clutter-Free UI**: Enjoy an optimized, user-friendly interface designed to improve your workflow.
- **Improved Email Deliverability**: Add your own SMPT server or a third-party SMTP server to improve email deliverability


📚 [Documentation](https://sendusersemail.com/docs/how-to-install/?utm_source=wp_dir&utm_medium=link&utm_campaign=docs) | 🌟 [PRO Features](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=features#pricing) | 🔥 [Get PRO](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=get_pro)

**Also check out our other plugins on WordPress.org**:
- YASR - [Star Rating Plugin for WordPress](https://wordpress.org/plugins/yet-another-stars-rating/)
- [Google Reviews Plugin for WordPress](https://wordpress.org/plugins/embedder-for-google-reviews/)

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/send-users-email` directory, or upload the plugin zip file by going to Upload Plugin section on your WordPress dashboard
2. Activate the plugin through the 'Plugins' screen in the WordPress dashboard

== Frequently Asked Questions ==

= Can I select individual users? =

Absolutely. Go to the `Email Users` page of the plugin and select the user you want to email.

= Can I choose multiple roles? =

Yes. You are able to choose one or multiple roles at a time and email them.

= When are emails sent? =

Emails are processed immediately and there is no delay. However, depending on your hosting or mail service provides, there might be a slight delay in delivery. The PRO version however has an option to send emails via queue, so they do not get sent all at once.

= I am using Gmail as email service provider (or any other provider) and users are not receiving emails. =

This plugin only acts as a bridge between your site and your email service provider. It's up to the email provider to deliver or block sent emails. If your delivery is not consistent, please contact your email provider support or hosting to see if you are hitting their limit. The plugin does attempt to let you know when your emails are not sent. If this happens, please check your logs or email service provider usage for any issues.

= I have many users in my system and many are not getting the emails? =

Since, processing is happening immediately, a low `max execution time` in your server's PHP settings might terminate the process. Try increasing the value for max execution time. You can do this yourself or contact your hosting provider to do it on your behalf. With PRO version of the plugin, you can avoid this issue by adding your emails to the sending queue so that they are sent in multiple batches with the help of WP cron.

= I have an issue/question/suggestion/request? =

Please post refer to our [support form](https://sendusersemail.com/support/?utm_source=wp_repo&utm_medium=link&utm_campaign=faq_support_link).

= Is there a way to try out the plugin before I install it on my website? =

Absolutely. Try it out at [https://tastewp.org/plugins/send-users-email/](https://tastewp.org/plugins/send-users-email/). Please note that this service doesn't allow outgoing email so you will just be trying out the interface and general idea of the features.

= Does the plugin work with all email providers? =

It works with most major providers, but you should check with your email service if they have specific sending limits.

= Can I personalize the emails I send? =

Yes, both the free and [PRO version](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=pro_version) allow email personalization using placeholders.

= What happens if an email fails to send? =

Errors will be logged for you to review, so you can identify and address any issues.

= What is the benefit of using the email queue system in the PRO version? =

The queue system ensures your emails are sent gradually, preventing your account from being flagged for sending too many emails at once and improving delivery success.

= Can I schedule emails to be sent later with the free version? =

Scheduling is available only in the [PRO version](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=pro_version).

= What’s the difference between the free version’s log retention and the PRO version? =

The free version logs emails for 15 days, while the [PRO version](https://sendusersemail.com/?utm_source=wp_dir&utm_medium=link&utm_campaign=pro_version) keeps logs for 90 days (adjustable).

== Screenshots ==

1. Admin dashboard providing basic overview of users in the system.
2. Send email to individual users
3. Send email by selecting roles
4. Settings area (01)
5. Settings area (02)
6. Tags for email personalization

== Changelog ==

= 1.6.2 (2025-06-26) =
* Bugfix: user group editing
* Feature: Support default WooCommerce templates
* Feature: Unsubscribe option

= 1.6 (2025-06-19) =
* New feature descriptions
* Hotfix for template tag replacement

= 1.5.15 (2025-05-27) =
* Hotfix for escaping html in emails

= 1.5.14 (2025-05-22) =
* Hotfix for custom CSS output
* Compliance with wp.org standards
* Fremius SDK upgrade

= 1.5.13 (2025-04-02) =
* Hotfix for compatibility with PHP 7.4

= 1.5.11 (2025-03-27) =
* Added fields for custom email title and tagline

= 1.5.9 (2024-11-20) =
* Added preview mode for email template

= 1.5.8 (2024-10-30) =
* Added feature to bulk add users to groups

= 1.5.7 (2024-10-24)
* Freemius SDK update

= 1.5.6 (2024-10-23) =
* CSS harmonization
* Beta features for SMTP settings

= 1.5.5 =
* Freemius SDK update

= 1.5.4 (2024-07-07) =
* Compatibility check with latest WP

= 1.5.3 (2024-07-07) =
* Bug fixes
* Freemius SDK update

= 1.5.2 (2024-04-11) =
* Bug fixes
* Freemius SDK update

= 1.5.1 (2024-01-15) =
* Freemius SDK update

= 1.5.0 (2023-12-06) =
* Freemius SDK update
* Bug fixes

= 1.4.4 (2023-11-14) =
* Bug fixes
* WordPress version stability test
* Freemius SDK update

= 1.4.3 (2023-10-05) =
* Officially support PHP 8.0 (should work on higher version as well but not fully tested yet)

= 1.4.2 (2023-09-05) =
* Freemius SDK update
* WordPress compatibility check with version 6.3

= 1.4.1 (2023-07-05) =
* Freemius SDK update

= 1.4.0 (2023-06-16) =
* Added feature to log error if wp_mail fails to send email
* Added feature to log sent email of last 15 days
* Bug fix: Email content image alignment not working fixed
* Freemius SDK update

= 1.3.9 (2023-05-10) =
* Validation added to check if Email from/reply to email and name are set
* Max execution time warning relocated
* Bug fixes: Caption shortcode removed from mail content
* Freemius SDK update

= 1.3.8 (2023-04-23) =
* Cleanup user interface
* Max execution time warning added
* Freemius SDK update
* Minor bug fixes

= 1.3.7 (2023-04-21) =
* Freemius SDK update
* Minor bug fixes

= 1.3.6 (2023-04-15) =
* Freemius SDK update
* User Email page, add render slow warning if there are many users
* Minor bug fixes

= 1.3.5 (2023-03-01) =
* Added ability to hide table columns on user email page
* Minor bug fixes

= 1.3.4 (2023-02-06) =
* Minor bug fix

= 1.3.3 (2023-01-11) =
* Bug fix: Single and double quote escaping fix on email subject

= 1.3.2 (2023-01-06) =
* Bug fix: Paragraph break and line break issue fix removing excess spacing

= 1.3.1 (2022-12-25) =
* UX improvement to better report failed email send attempt
* Feature to add Social media link on email template
* Bug fix: New line to break tag addition

= 1.3.0 (2022-12-21) =
* UX improvement
* Minor bug fixes

= 1.2.1 (2022-12-10) =
* UX improvement for error/success message
* user_id placeholder added

= 1.2.0 (2022-12-06) =
* Pro Version release
* Freemius integration
* Minor bug fixes

= 1.1.2 (2022-10-26) =
* Settings page access bug fix and UX improvements

= 1.1.1 (2022-10-24) =
* Minor bug fix on roles capability feature

= 1.1.0 (2022-10-24) =
* Added support to select roles to use send users email

= 1.0.6 (2022-09-10) =
* Added HTML tag support in email footer

= 1.0.5 (2022-07-31) =
* Added username column to users display table

= 1.0.4 (2022-06-19) =
* Added filter to user selection with ID range

= 1.0.3 (2022-05-28) =
* Added ability for users to style email template
* minor bug fixes

= 1.0.2 (2022-02-12) =
* Username placeholder added to email template
* Email From/Reply-To settings added

= 1.0.1 (2021-11-17) =
* Settings bug fix and style changes

= 1.0.0 (2021-10-01) =
* Initial release

== Credits ==
* [unDraw](https://undraw.co/) - Illustrations
* [Bootstrap](https://getbootstrap.com/) - UI
* [DataTables](https://datatables.net/) - Tables