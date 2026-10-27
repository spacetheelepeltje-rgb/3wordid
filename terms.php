<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$data = current_user_from_session($conn);
$loginUrl = '/login/options.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions — 3WordID</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="css/newstyle.css">
    <style>
        .legal-wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 48px 24px 72px;
        }
        .legal-wrap .eyebrow {
            text-align: left;
            margin-bottom: 10px;
        }
        .legal-wrap h1 {
            font-size: clamp(32px, 5vw, 48px);
            margin-bottom: 10px;
        }
        .legal-lead {
            color: var(--slate);
            font-size: 16px;
            line-height: 1.55;
            max-width: 720px;
            margin: 0 0 22px;
        }
        .legal-meta {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 22px;
        }
        .flash {
            background: #fff;
            border: 1px solid #eceae4;
            border-left: 4px solid var(--gold);
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: var(--shadow);
            margin-bottom: 22px;
            color: var(--navy);
            font-size: 15px;
            line-height: 1.5;
            font-weight: 500;
        }
        .legal-card {
            background: #fff;
            border: 1px solid #eceae4;
            border-radius: 16px;
            padding: 28px 28px 8px;
            box-shadow: 0 1px 0 rgba(15,28,63,0.03);
        }
        .legal-card h2 {
            font-size: 16px;
            letter-spacing: -0.02em;
            margin: 0 0 8px;
        }
        .legal-card section {
            padding-bottom: 22px;
            margin-bottom: 18px;
            border-bottom: 1px solid #f0eee8;
        }
        .legal-card section:last-child {
            border-bottom: 0;
        }
        .legal-card p,
        .legal-card li {
            color: var(--slate);
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 10px;
        }
        .legal-card ul {
            margin: 0 0 10px 18px;
            padding: 0;
        }
        .legal-card strong { color: var(--navy); font-weight: 600; }
        .legal-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }
        .btn-ghost {
            background: #fff;
            color: var(--navy);
            border: 1px solid var(--line);
            padding: 10px 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-ghost:hover { background: #f3f1ec; }
        @media (max-width: 800px) {
            .legal-card { padding: 20px 16px 4px; }
        }
    </style>
</head>
<body>
<?php
$header_extra = '<a href="https://wa.me/31644311561?text=From 3WordID.com : " target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="background:#25D366;">Chat on WhatsApp</a>';
require __DIR__ . '/php/header.php';
?>

    <main class="legal-wrap">
        <div class="eyebrow">LEGAL</div>
        <h1>Terms and Conditions</h1>
        <p class="legal-lead">
            3WordID is a lookup tool. It links three words to a URL, a notification, or a message box. That is the whole service. Use 
            it only on that basis. <br><br> If you want to use this app professionally and have specific privacy or appearance requests contact our administrator at <a href='mailto:frits@rincker.nl'>frits@rincker.nl</a>. If you are in the EU and want to reserve a 3WordID, or a number of them, just send an email expaining your requirement, we will send payment details. We're in Beta which means the system will only become more robust and functional, not less so.
             </p>
        <p class="legal-meta">Effective 23 September 2026. Replaces any earlier terms page.</p>

        <div class="flash">
            The site and every 3WordID are provided <strong>as is</strong> and <strong>as available</strong>.
            No rights can be derived from any visit, account, payment, reservation, lookup, message, notification, or other interaction with 3WordID.com.
        </div>

        <div class="legal-card">
            <section>
                <h2>1. Who these terms bind</h2>
                <p>
                    These terms are an agreement between you and the operator of 3WordID.com (the “Operator”).
                    By visiting the site, looking up a 3WordID, creating an account, reserving words, sending or reading a message, paying, or otherwise using the service, you accept these terms.
                    If you do not accept them, do not use the site.
                </p>
            </section>

            <section>
                <h2>2. What the service is — and is not</h2>
                <p>The only purpose of 3WordID is to associate three words (a “3WordID”) with one or more of:</p>
                <ul>
                    <li>a URL;</li>
                    <li>a notification text;</li>
                    <li>a message box.</li>
                </ul>
                <p>
                    3WordID is not a domain registry, not a trademark office, not a bank, not a law firm, not an escrow, not a telecommunications provider, and not a publisher of the pages or messages users attach.
                    A 3WordID is a revocable lookup label inside this service. It is not property, not a domain name, and not an exclusive right against the world.
                </p>
            </section>

            <section>
                <h2>3. Accept the site as is</h2>
                <p>
                    You accept the site, accounts, 3WordIDs, lookups, notifications, message boxes, QR features, reseller tools, and all related functions <strong>as is</strong>, <strong>as available</strong>, and <strong>with all faults</strong>.
                </p>
                <p>
                    The Operator does not warrant that the service will be uninterrupted, accurate, secure, complete, error-free, or fit for any particular purpose.
                    Features may change, break, or be withdrawn at any time.
                    Beta functions may fail without notice.
                </p>
                <p>
                    To the maximum extent permitted by law, all implied warranties — including merchantability, fitness for a particular purpose, title, quiet enjoyment, and non-infringement — are disclaimed.
                </p>
            </section>

            <section>
                <h2>4. No rights from use</h2>
                <p>
                    <strong>No rights can be derived from any interaction with this site.</strong>
                    Creating, paying for, publicizing, or using a 3WordID does not give you ownership, a guaranteed reservation, a trademark, a licence beyond revocable use of the lookup tool, priority against the Operator, or any claim if the label is changed, localized, transferred, or deleted.
                </p>
            </section>

            <section>
                <h2>5. Free and paid accounts</h2>
                <p>
                    Free or unpaid 3WordIDs may expire, be deleted, or be taken by another user.
                    Unpaid IDs may expire after about two weeks, or sooner if the Operator so decides.
                </p>
                <p>
                    A paid account or paid credit only buys a limited, revocable chance to keep or create 3WordIDs while the Operator continues to offer that option.
                    It does not buy the words themselves.
                </p>
                <p>
                    <strong>Paid accounts overrule free accounts</strong> when both try to reserve the same 3WordID.
                    If a free or unpaid user holds a 3WordID and a paid user requests that same 3WordID, the Operator may assign it to the paid user, with or without notice to the free user.
                    Among paid users, the Operator decides allocation in its sole discretion.
                </p>
            </section>

            <section>
                <h2>6. Localization</h2>
                <p>
                    3WordIDs may be made localized. The same three words may later resolve differently by country, city, language, or other locale.
                    A global match today is not a promise of a global match tomorrow.
                    Localization may split, hide, rename, or replace existing IDs.
                </p>
            </section>

            <section>
                <h2>7. Termination at any time</h2>
                <p>
                    The Operator may, at any time and for any reason it deems necessary — or for no stated reason — refuse, suspend, transfer, localize, hide, edit, or <strong>terminate a 3WordID</strong>, an account, a message box, a notification, or access to the site.
                    No explanation is required. No compensation is due, including where money was paid, unless mandatory law says otherwise and then only to that minimum.
                </p>
            </section>

            <section>
                <h2>8. Your content and third-party URLs</h2>
                <p>
                    You are solely responsible for every URL, notification, message, and other item you attach to a 3WordID.
                    The Operator does not control destination sites, does not screen all content, and is not liable for what a lookup opens or what a message box contains.
                </p>
                <p>
                    You must not use the service for anything unlawful or for content you are not allowed to publish.
                    The Operator may remove material or labels without investigating first.
                    Messages are currently unencrypted. Do not send secrets.
                </p>
            </section>

            <section>
                <h2>9. Payments</h2>
                <p>
                    Prices, credits, Patreon, PayPal, bank transfer, and reseller kickbacks may change.
                    Payments are for access to a volunteer-scale tool, not for ownership of an identifier.
                    Unless mandatory law requires it, payments are non-refundable if an ID is later terminated, localized, overwritten by a paid reservation, or if the service changes or stops.
                </p>
            </section>

            <section id="data">
                <h2>10. Data</h2>
                <p>
                    To run the lookup tool the Operator may store data supplied by Google sign-in and data you submit: name, avatar URL, your email address, 3WordIDs, notifications, and messages.
                    Your email address is stored as a one-way hash, not as readable text.
                    The email address you log in with is used only to send what you request; it is held encrypted only during your session and is not stored in readable form.
                    Data is shared with third parties only if the Operator is legally required to do so, or as needed to host and process the service.
                    Separate consent screens may appear again from time to time.
                    Do not treat 3WordID as a confidential archive.
                </p>
                <p>
                    Messages, IP addresses and account data are logged and kept, also after an account is deactivated, so we can respond to lawful requests from law enforcement.
                    This also applies to messages sent through the public message form by people without an account: the message is shown to the owner of the 3WordID, and the message and the sender's IP address are stored and kept.
                </p>
                <p>
                    Cookies: logging in uses one strictly necessary session cookie (3WIDSESS).
                    Google Analytics, which sets _ga cookies and sends your IP address and browser details to Google, is only loaded if you click Accept in the cookie banner.
                    <a href="#cookie-settings">Change your cookie choice</a>.
                </p>
            </section>

            <section>
                <h2>11. Limitation of liability</h2>
                <p>
                    To the maximum extent permitted by law, the Operator and its owners, helpers, hosts, and suppliers are not liable for any indirect, incidental, special, consequential, exemplary, or punitive loss, or for lost profits, lost data, lost goodwill, substitute services, or business interruption, whether in contract, tort, or otherwise.
                </p>
                <p>
                    If any liability cannot be excluded, it is limited in aggregate to the amount you paid the Operator for 3WordID in the three months before the claim, or EUR 25, whichever is lower.
                </p>
                <p>
                    You use lookups, follow linked URLs, and send or read messages at your own risk.
                </p>
            </section>

            <section>
                <h2>12. Indemnity</h2>
                <p>
                    You will indemnify and hold the Operator harmless against claims, damages, and costs (including reasonable legal fees) that arise from your 3WordIDs, your URLs, your messages, your notifications, your account, or your breach of these terms.
                </p>
            </section>

            <section>
                <h2>13. Changes</h2>
                <p>
                    The Operator may change these terms at any time.
                    Continued use after a change is acceptance.
                    A notice on login or on this page is enough.
                </p>
            </section>

            <section>
                <h2>14. Law and contact</h2>
                <p>
                    These terms are governed by the laws of the Netherlands, without regard to conflict-of-law rules.
                    Courts of the Netherlands have exclusive jurisdiction, except where mandatory consumer rules give you another forum.
                </p>
                <p>
                    Contact: <a href="mailto:frits@rincker.nl">frits@rincker.nl</a>
                </p>
            </section>
        </div>

        <div class="legal-actions">
            <a class="btn btn-primary" href="<?php echo $main_url; ?>">Back to homepage</a>
            <a class="btn-ghost" href="<?php echo $loginUrl; ?>">Log in / Create ID</a>
        </div>
    </main>

<?php
$footer_note = 'No rights can be derived from use of this site. Service provided as is.';
require __DIR__ . '/php/footer.php';
?>
</body>
</html>
