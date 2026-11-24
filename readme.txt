=== SMTPing Email Verification for Groundhogg ===
Tags: groundhogg, email verification, smtping, deliverability
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a native “Verify with SMTPing” action to Groundhogg flows.

== Description ==

This plugin adds a Groundhogg flow action that sends a contact's email address to SMTPing for verification, stores the returned verification details on the Groundhogg contact, and applies exactly one result tag:

* SMTPing: Safe
* SMTPing: Review
* SMTPing: Avoid
* SMTPing: Error

The plugin does not treat deliverability as consent. A Safe result does not confirm an unconfirmed contact or grant marketing permission. Optionally, an Avoid result can mark a contact as Spam/unmarketable.

== Maintenance status ==

This plugin is maintained as needed for its original user's own deployments. There is no commitment to proactive public maintenance, release schedules, compatibility updates, support, or response times. Community contributions and forks are welcome.

== Processing model and throughput ==

Groundhogg runs flow events in the background. For each contact, this plugin sends one synchronous request to SMTPing's /verify/single endpoint and waits for the result before Groundhogg routes that contact to the next flow step. The plugin does not use SMTPing's batch endpoint and does not add an artificial delay between requests.

Throughput depends on SMTPing response time, Groundhogg cron frequency and worker behavior, hosting capacity, and other queued Groundhogg activity. In one production list-hygiene run of more than 2,000 contacts, approximately 4.2 validations per minute were observed during a five-minute sample. This is an operational example, not a performance guarantee; your results may vary. Large lists may require many hours and should be allowed to run in the background while progress and the SMTPing: Error tag are monitored.

This GPL-licensed plugin may be modified and redistributed. For example, a high-volume implementation could replace the single-request model with SMTPing's batch API, provided it also handles result polling, contact matching, partial failures, retries, and safe resumption of each Groundhogg flow.

== Data and privacy ==

When the verification action runs, the plugin sends the contact's email address and the configured API authentication to SMTPing. SMTPing's response is stored as Groundhogg contact metadata and result tags in the site's WordPress database.

The plugin does not send contact data to any other service. Site owners remain responsible for their privacy disclosures, consent practices, data-retention rules, SMTPing account terms, and applicable law.

== Installation ==

1. Install and activate Groundhogg.
2. Install and activate this plugin.
3. Open Settings > Groundhogg SMTPing.
4. Enter this site's SMTPing API key and test the connection.
5. In Groundhogg, create a flow that starts with a Tag Applied benchmark.
6. Add “Verify with SMTPing” as the first action.
7. Route later actions using the SMTPing: Safe, Review, Avoid, and Error tags.

For higher-security deployments, define the key in wp-config.php:

define( 'GH_SMTPING_API_KEY', 'sk_live_...' );

== Sample flow ==

One practical list-hygiene flow is:

1. Tag Applied benchmark: Verification Pending.
2. Action: Verify with SMTPing.
3. If the contact has SMTPing: Safe, apply Ready for Outreach.
4. Otherwise, if the contact has SMTPing: Review, apply Manual Review.
5. Otherwise, if the contact has SMTPing: Avoid, apply Suppressed.
6. Remove Verification Pending after a completed Safe, Review, or Avoid path.

The verification action applies the SMTPing result tag automatically. A request or API error applies SMTPing: Error and returns an action error, which may stop that flow run. A separate flow triggered by SMTPing: Error can implement review or retry policy. Do not send email merely because a contact is Safe; consent and subscription status remain separate decisions.

== Result policy ==

Safe: valid, alias
Review: catchall, valid_catchall, unknown, and any future unrecognized status
Avoid: invalid, spamtrap, disposable, blacklisted, complainer, spambot, inbox_full, typo

The plugin does not treat deliverability as consent. Safe results do not automatically confirm a contact's marketing status.

== Development note ==

Initial development was completed on November 23, 2026, with the help of AI at the request of a nonprofit executive who is also a software developer. No individual attribution is required.

== License and disclaimer ==

This plugin is open-source software licensed under GPLv2 or later.

The software is provided “as is,” without warranties or guarantees of any kind. Use it at your own risk. The authors and contributors are not liable for data loss, service interruption, deliverability decisions, compliance issues, or other damages arising from its use.

== Changelog ==

= 0.2.1 =

* Documented the single-request processing model, observed throughput, environmental variability, and options for modification.

= 0.2.0 =

* Prepared a neutral, shareable open-source release.
* Added privacy, sample-flow, development, and warranty documentation.

= 0.1.2 =

* Corrected settings and connection-test behavior.

