# Vasl Health — Developer Handoff
**Version:** Final · May 2026
**Package:** 31 files (30 pages + WALKTHROUGH.html)
**Questions:** hello@vaslhealth.com

---

## What's in This Package

```
vasl-handoff/
├── DEVELOPER-BRIEF.md        ← You are here
├── WALKTHROUGH.html          ← Open this first to review all 30 pages
├── site/                     ← 23 main site pages
│   ├── index.html            → /
│   ├── about.html            → /about
│   ├── ai-model.html         → /ai-model
│   ├── technology.html       → /technology
│   ├── vlap.html             → /vlap
│   ├── platform.html         → /platform
│   ├── peer-groups.html      → /peer-groups
│   ├── teletherapy.html      → /teletherapy
│   ├── for-schools.html      → /for-schools
│   ├── for-organizations.html→ /for-organizations
│   ├── outcomes.html         → /outcomes
│   ├── clinical-outcomes.html→ /clinical-outcomes
│   ├── prototype.html        → /prototype
│   ├── team.html             → /team
│   ├── careers.html          → /careers
│   ├── pricing.html          → /pricing
│   ├── blog.html             → /blog
│   ├── contact.html          → /contact
│   ├── research.html         → /research
│   ├── support.html          → /support
│   ├── privacy.html          → /privacy
│   ├── terms.html            → /terms
│   └── baa.html              → /baa
└── help/                     ← 7 Help Center articles
    ├── getting-started.html  → /help/getting-started
    ├── vlap-technology.html  → /help/vlap-technology
    ├── clinical.html         → /help/clinical
    ├── compliance.html       → /help/compliance
    ├── assessments.html      → /help/assessments
    ├── billing.html          → /help/billing
    └── technical.html        → /help/technical
```

**Technology:** Pure HTML/CSS/JavaScript. No framework. No build step. No dependencies beyond Google Fonts (CDN). Every file is self-contained.

---

## Step 1 — Review the Build

Open `WALKTHROUGH.html` in Chrome or Firefox directly from your file system — no server required. It shows all 30 pages in a numbered sidebar. Click any page to preview it, or use the ← → arrow keys to step through in order. Scroll the sidebar to see all 30.

---

## Step 2 — Hosting

**Netlify**

1. Create a GitHub repo
2. Place `/site/` contents in the repo root; place `/help/` contents in a `/help/` subfolder
3. Connect repo to [netlify.com](https://netlify.com) — no configuration needed for static HTML
4. Add `gotovasl.com` as a custom domain in Netlify → Domain Management
5. Add a `_redirects (see DEVELOPER-BRIEF.md)` to the repo root for clean URLs:

```json
{
  "cleanUrls": true,
  "trailingSlash": false
}
```

**Netlify (alternative)**

1. Drag `/site/` folder contents to [netlify.com/drop](https://netlify.com/drop)
2. Add `/help/` files into a `/help/` subfolder via the Netlify UI
3. Add custom domain
4. Add a `_redirects` file to your repo root — copy the list below:

```
/about                 /about.html                  200
/platform              /platform.html               200
/ai-model              /ai-model.html               200
/technology            /technology.html             200
/vlap                  /vlap.html                   200
/peer-groups           /peer-groups.html            200
/teletherapy           /teletherapy.html            200
/for-schools           /for-schools.html            200
/for-organizations     /for-organizations.html      200
/outcomes              /outcomes.html               200
/clinical-outcomes     /clinical-outcomes.html      200
/prototype             /prototype.html              200
/team                  /team.html                   200
/careers               /careers.html                200
/pricing               /pricing.html                200
/blog                  /blog.html                   200
/contact               /contact.html                200
/research              /research.html               200
/support               /support.html                200
/privacy               /privacy.html                200
/terms                 /terms.html                  200
/baa                   /baa.html                    200
/help/getting-started  /help/getting-started.html   200
/help/vlap-technology  /help/vlap-technology.html   200
/help/clinical         /help/clinical.html          200
/help/compliance       /help/compliance.html        200
/help/assessments      /help/assessments.html       200
/help/billing          /help/billing.html           200
/help/technical        /help/technical.html         200
```

**DNS:** Point the A record for `gotovasl.com` to the Netlify IP shown in your platform dashboard after adding the domain. SSL is provisioned automatically.

---

## Step 3 — Wire the Contact Form (Required Before Go-Live)

The contact form on `contact.html` currently shows a thank-you message on submit but does not send data anywhere. It must be connected to a form backend before launch.

**Setup with Formspree:**

1. Create an account at [formspree.io](https://formspree.io)
2. Create a new form → copy your endpoint (e.g. `https://formspree.io/f/xyzabc12`)
3. Set notification email to `hello@vaslhealth.com` in the Formspree dashboard
4. In `contact.html`, find the `handleSubmit` function in the `<script>` block and replace it:

```javascript
async function handleSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const firstName = document.getElementById('first-name').value;
  try {
    const res = await fetch('https://formspree.io/f/YOUR_FORM_ID', {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json' }
    });
    if (res.ok) {
      form.innerHTML = `
        <div style="padding:32px 0;">
          <div style="font-family:var(--mono);font-size:9px;letter-spacing:0.14em;
            text-transform:uppercase;color:var(--clay);margin-bottom:16px;">Received</div>
          <div style="font-family:var(--serif);font-size:28px;font-weight:300;
            color:var(--ink);margin-bottom:16px;letter-spacing:-0.02em;">Thanks, ${firstName}.</div>
          <p style="font-size:16px;font-weight:300;color:var(--ink-70);line-height:1.72;">
            An implementation lead will be in touch within one business day. Email
            <a href="mailto:hello@vaslhealth.com" style="color:var(--deep-green);">
            hello@vaslhealth.com</a> if you need a faster response.</p>
        </div>`;
    }
  } catch(err) {
    alert('Submission failed — please email hello@vaslhealth.com directly.');
  }
}
```

**Newsletter form** on `blog.html` uses the same pattern. Wire it to a second Formspree form or your email provider (Mailchimp, ConvertKit, etc.).

---

## Step 4 — Verify Blog Article Links

The blog landing page (`blog.html`) links to seven live article pages that are **not in this package** — they exist on the current gotovasl.com site. Do not overwrite them.

Before deploying, confirm each link href matches the actual live slug:

| Article | href in blog.html |
|---------|------------------|
| The Mental Health Equity Crisis | `blog-mental-health-equity-crisis.html` |
| Healing the Great Divide | `blog-healing-great-divide.html` |
| Bridging the Chasm | `blog-bridging-chasm.html` |
| Why Medicaid Populations Are Central | `blog-medicaid-populations.html` |
| The Future Must Be Culturally Responsive | `blog-future-culturally-responsive.html` |
| The Crisis We Keep Describing | `blog-crisis-without-solving.html` |
| The Other Diagnosis | `blog-other-diagnosis.html` |

If any live slug differs, update the `href` in `blog.html` before deploying.

---

## Step 5 — Create Accessibility Placeholder

All page footers link to `/accessibility`. Create a minimal placeholder to avoid a 404:

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Accessibility — Vasl Health</title>
</head>
<body style="font-family:sans-serif;max-width:700px;margin:80px auto;padding:0 24px;color:#1C1C1C;">
  <h1 style="font-weight:300;margin-bottom:16px;">Accessibility</h1>
  <p>Vasl Health is committed to WCAG 2.1 AA accessibility standards across all platform 
  properties. For accessibility issues or inquiries, contact 
  <a href="mailto:accessibility@vaslhealth.com">accessibility@vaslhealth.com</a>.</p>
</body>
</html>
```

Save as `accessibility.html` in your `/site/` folder.

---

## What's Purely Static (No Backend Needed)

- All 30 pages — render and animate in any modern browser with no server
- Platform prototype (`prototype.html`) — all five portal tabs run on vanilla JavaScript
- BAA execution — email-based via `mailto:legal@vaslhealth.com`, no backend
- All email channel links — plain `mailto:` links
- Google Fonts — CDN, requires internet connection

---

## Pricing (All Files Are Consistent — Update Together if Pricing Changes)

Files to update if pricing changes: `pricing.html`, `for-schools.html`, `for-organizations.html`, `help/billing.html`, `outcomes.html`

Current structure applied throughout:

| Plan | Platform Fee | PMPM (Schools/Non-Profits) | PMPM (Health Systems) | Capacity |
|------|-------------|--------------------------|----------------------|----------|
| Starter | $480/month | $3–5 | $6–8 | Up to 100 |
| Pro | $1,200/month | $3–5 | $6–8 | Up to 500 |
| Enterprise | Custom | — | $6–8 | 500+ |

Medicaid math (Pro, 500 students, $4 avg PMPM): $38,400/yr total → ~$19,200 recovery → ~$38/student/year net.

---

## Design System

All pages use the same CSS variables. Defined in each file's `<style>` block:

```css
--warm-white: #FAF7F2;   /* page background */
--parchment:  #F0EAE0;   /* alternating section bg */
--ink:        #1C1C1C;   /* primary text */
--clay:       #C4896F;   /* accent / brand highlight */
--deep-green: #2D4A3E;   /* CTA / institutional green */
--dust:       #8B7B6B;   /* labels / secondary text */
--chalk:      #E2DAD0;   /* borders / dividers */
--charcoal:   #222222;   /* dark sections / footer */

--serif: 'Newsreader', Georgia, serif
--sans:  'IBM Plex Sans', sans-serif
--mono:  'IBM Plex Mono', monospace
```

---

## Key Corrections Applied — Do Not Revert

| Was | Corrected To |
|-----|-------------|
| "Azadi Health" | "Vasl Health" |
| Nav logo "Vasl." | "Vasl Health" |
| "Vernacular Language Analysis Platform" | "Vasl Language Analysis Platform" |
| © 2024 | © 2026 |
| 28% industry retention average | 40–50% industry average |
| $14.40/student/year Medicaid net | ~$38/student/year |
| Crisis response "<15 minutes" | 90-min human clinical supervisor SLA |
| "1 licensed therapist integration" in pricing | Crisis escalation SLA |
| "Up to 5 licensed therapist integrations" | Medicaid billing documentation export |
| All fabricated studies and partnerships | Removed from research.html |
| Fake "123 Innovation Drive, SF" address | Removed — email only |
| "[Address to be provided]" placeholder | Removed — email only |
| mailto: links in all nav CTAs | Page links throughout |
| Contact form → mailto: redirect on submit | Inline confirmation (wire Formspree) |

---

## Browser Support

Chrome 100+, Firefox 100+, Safari 15+, Edge 100+ (Chromium). Internet Explorer: not supported.

---

## Contact Directory

| Purpose | Email |
|---------|-------|
| General | hello@vaslhealth.com |
| Healthcare partnerships | healthcare@vaslhealth.com |
| Education partnerships | education@vaslhealth.com |
| Clinical / compliance | clinical@vaslhealth.com |
| Legal / BAA | legal@vaslhealth.com |
| Research | research@vaslhealth.com |
| Press | press@vaslhealth.com |
| Technical support | support@vaslhealth.com |
| Privacy | privacy@vaslhealth.com |
| Accessibility | accessibility@vaslhealth.com |
