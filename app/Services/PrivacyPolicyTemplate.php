<?php

namespace App\Services;

use App\Models\Apkads;

/**
 * Renders the static privacy-policy template that seeds a new apkad's policy.
 *
 * The output is HTML (not Markdown) because Filament's RichEditor stores HTML
 * directly. Each generation replaces the editor body with this skeleton; the
 * editor then customises it before publishing. There is no LLM call here on
 * purpose: the editor will rewrite specifics anyway, and a deterministic
 * template means previewing/regenerating is instant and free.
 */
class PrivacyPolicyTemplate
{
    public static function render(Apkads $apk): string
    {
        $appName = e((string) $apk->name);
        $effective = now()->toFormattedDateString();

        return <<<HTML
<h2>Privacy Policy for {$appName}</h2>
<p><em>Effective date: {$effective}</em></p>

<p>This Privacy Policy describes how the {$appName} application
(the &ldquo;App&rdquo;) handles information when you use it. By installing or
using the App you agree to the practices described below.</p>

<h3>1. Information We Collect</h3>
<p>The App may collect the following information:</p>
<ul>
    <li><strong>Device information</strong> &mdash; such as device model,
        operating system version, language, and unique device identifiers
        used by the operating system.</li>
    <li><strong>Usage data</strong> &mdash; including in-app actions, session
        duration, crash reports, and diagnostic information used to improve
        stability and performance.</li>
    <li><strong>Information you provide</strong> &mdash; if any feature of
        the App asks you to enter information, that information is processed
        as described in the relevant section of this policy.</li>
</ul>

<h3>2. How We Use Information</h3>
<p>Collected information is used to operate, maintain, and improve the App,
to diagnose technical issues, to prevent abuse, and to comply with legal
obligations.</p>

<h3>3. Sharing of Information</h3>
<p>We do not sell personal information. We may share limited information
with service providers that help us operate the App (for example, crash
reporting and analytics providers), and where required by law.</p>

<h3>4. Third-Party Services</h3>
<p>The App may rely on third-party services that have their own privacy
practices. Please review the privacy policies of any third-party services
referenced inside the App.</p>

<h3>5. Children&rsquo;s Privacy</h3>
<p>The App is not directed to children under the age that requires parental
consent in your jurisdiction. If you believe a child has provided us with
personal information, please contact us so we can remove it.</p>

<h3>6. Data Retention</h3>
<p>We retain information only as long as needed for the purposes described
in this policy or as required by law.</p>

<h3>7. Your Rights</h3>
<p>Depending on your jurisdiction you may have the right to access, correct,
or delete the personal information we hold about you, or to object to or
restrict certain processing. To exercise these rights, contact us using the
details below.</p>

<h3>8. Changes to This Policy</h3>
<p>We may update this Privacy Policy from time to time. The &ldquo;Effective
date&rdquo; at the top of this page reflects the most recent revision.
Continued use of the App after a change indicates acceptance of the updated
policy.</p>

<h3>9. Contact</h3>
<p>If you have questions about this Privacy Policy or our handling of your
information, please contact us through the support channel listed on the
App&rsquo;s store page.</p>
HTML;
    }
}
