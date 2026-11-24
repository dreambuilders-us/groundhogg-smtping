# SMTPing Email Verification for Groundhogg

This open-source WordPress plugin adds a native **Verify with SMTPing** action to Groundhogg flows. It stores verification details on the Groundhogg contact and applies one of four tags:

- `SMTPing: Safe`
- `SMTPing: Review`
- `SMTPing: Avoid`
- `SMTPing: Error`

## Maintenance status

This plugin is maintained as needed for its original user's own deployments. There is no commitment to proactive public maintenance, release schedules, compatibility updates, support, or response times. Community contributions and forks are welcome.

## Requirements

- WordPress 6.5 or later
- PHP 7.4 or later
- Groundhogg
- An SMTPing account and API key

## Installation

1. Install and activate Groundhogg.
2. Upload and activate this plugin.
3. Open **Settings → Groundhogg SMTPing**.
4. Save the site's SMTPing API key.
5. Select **Test connection**.

For deployments that keep secrets out of the WordPress options table, define the key in `wp-config.php`:

```php
define( 'GH_SMTPING_API_KEY', 'sk_live_...' );
```

## Sample Groundhogg flow

1. Start with a **Tag Applied** benchmark such as `Verification Pending`.
2. Add **Verify with SMTPing**.
3. If the contact has `SMTPing: Safe`, apply `Ready for Outreach`.
4. Otherwise, if the contact has `SMTPing: Review`, apply `Manual Review`.
5. Otherwise, if the contact has `SMTPing: Avoid`, apply `Suppressed`.
6. Remove `Verification Pending` after a completed Safe, Review, or Avoid path.

The plugin applies the SMTPing result tag automatically. A request or API failure applies `SMTPing: Error` and returns an action error, which may stop that flow run. A separate flow triggered by `SMTPing: Error` can implement review or retry policy.

Verification is not consent. A Safe result does not confirm a contact, subscribe the contact, or grant marketing permission.

## Processing model and throughput

Groundhogg runs flow events in the background. For each contact, this plugin sends one synchronous request to SMTPing's `/verify/single` endpoint and waits for the result before Groundhogg routes that contact to the next flow step. The plugin does not use SMTPing's batch endpoint and does not add an artificial delay between requests.

Throughput therefore depends on SMTPing response time, Groundhogg cron frequency and worker behavior, hosting capacity, and other queued Groundhogg activity. In one production list-hygiene run of more than 2,000 contacts, approximately 4.2 validations per minute were observed during a five-minute sample. This is an operational example, not a performance guarantee; your results may vary. Large lists may require many hours and should be allowed to run in the background while progress and the `SMTPing: Error` tag are monitored.

This GPL-licensed plugin may be modified and redistributed. For example, an implementation intended for high-volume verification could replace the single-request model with SMTPing's batch API, provided it also handles result polling, contact matching, partial failures, retries, and safe resumption of each Groundhogg flow.

## Result policy

| Band | SMTPing statuses |
| --- | --- |
| Safe | `valid`, `alias` |
| Review | `catchall`, `valid_catchall`, `unknown`, and unrecognized future statuses |
| Avoid | `invalid`, `spamtrap`, `disposable`, `blacklisted`, `complainer`, `spambot`, `inbox_full`, `typo` |
| Error | Network, authentication, HTTP, or unreadable-response errors |

## Data and privacy

When the action runs, the plugin sends the contact's email address and configured API authentication to SMTPing. SMTPing's response is stored as Groundhogg contact metadata and result tags in the site's WordPress database.

The plugin does not send contact data to any other service. Site owners remain responsible for privacy disclosures, consent practices, retention rules, SMTPing account terms, and applicable law.

## Development note

Initial development was completed on November 23, 2026, with the help of AI at the request of a nonprofit executive who is also a software developer. No individual attribution is required.

## License and warranty

This plugin is open-source software licensed under GPL-2.0-or-later.

The software is provided **as is**, without warranties or guarantees of any kind. Use it at your own risk. The authors and contributors are not liable for data loss, service interruption, deliverability decisions, compliance issues, or other damages arising from its use.
