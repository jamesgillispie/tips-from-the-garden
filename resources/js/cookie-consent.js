// Cookie consent banner (orestbida/vanilla-cookieconsent v3).
//
// Why this exists: before we drop a single analytics cookie we have to ask.
// This gates the *analytics* category so nothing tracking-related runs until a
// visitor opts in. The "necessary" category is read-only (session, CSRF, the
// consent cookie itself) — those are always on because the site can't work
// without them.
//
// GA4 / GTM wiring lives in Google Consent Mode v2 (see the layout's <head>):
// we boot with `analytics_storage: 'denied'` and only flip it to 'granted'
// here, the moment the visitor accepts. Add the GTM container id to `.env`
// (GTM_ID) and the tag fires consent-aware with zero further code.

import * as CookieConsent from 'vanilla-cookieconsent';

// Expose the API so a "Cookie settings" link anywhere can reopen the modal:
//   <button onclick="CookieConsent.showPreferences()">Cookie settings</button>
window.CookieConsent = CookieConsent;

// Link the banner to a privacy/cookie policy page once one exists. Leave null
// to hide the link rather than ship a 404.
const PRIVACY_URL = null;

// Push the analytics decision into Google Consent Mode. Harmless before GTM is
// added — the call just queues on dataLayer until a container reads it.
function syncGoogleConsent() {
    if (typeof window.gtag !== 'function') return;
    const granted = CookieConsent.acceptedCategory('analytics');
    window.gtag('consent', 'update', {
        analytics_storage: granted ? 'granted' : 'denied',
    });
}

CookieConsent.run({
    guiOptions: {
        consentModal: { layout: 'box wide', position: 'bottom left' },
        preferencesModal: { layout: 'box' },
    },

    categories: {
        necessary: {
            enabled: true,
            readOnly: true,
        },
        analytics: {
            enabled: false,
            // Sweep GA's cookies back out if consent is withdrawn.
            autoClear: {
                cookies: [{ name: /^_ga/ }, { name: '_gid' }],
            },
        },
    },

    onFirstConsent: syncGoogleConsent,
    onConsent: syncGoogleConsent,
    onChange: syncGoogleConsent,

    language: {
        default: 'en',
        translations: {
            en: {
                consentModal: {
                    title: '🌱 A quick note about cookies',
                    description:
                        'We use a couple of essential cookies to keep the site working, ' +
                        'and — only if you say yes — analytics cookies to learn what’s ' +
                        'helpful so we can make this better.',
                    acceptAllBtn: 'Accept all',
                    acceptNecessaryBtn: 'Reject all',
                    showPreferencesBtn: 'Manage preferences',
                    ...(PRIVACY_URL && {
                        footer: `<a href="${PRIVACY_URL}">Privacy policy</a>`,
                    }),
                },
                preferencesModal: {
                    title: 'Cookie preferences',
                    acceptAllBtn: 'Accept all',
                    acceptNecessaryBtn: 'Reject all',
                    savePreferencesBtn: 'Save my choices',
                    closeIconLabel: 'Close',
                    sections: [
                        {
                            title: 'How we use cookies',
                            description:
                                'You’re in control. Essential cookies always stay on so ' +
                                'the site works; everything else is your call.',
                        },
                        {
                            title: 'Strictly necessary',
                            description:
                                'Needed for the site to function — signing in, keeping ' +
                                'your session, and remembering this cookie choice. These ' +
                                'can’t be switched off.',
                            linkedCategory: 'necessary',
                        },
                        {
                            title: 'Analytics',
                            description:
                                'Helps us understand which tips land and where people ' +
                                'get stuck, so we can improve the garden. Off until you ' +
                                'turn it on.',
                            linkedCategory: 'analytics',
                        },
                        ...(PRIVACY_URL
                            ? [
                                  {
                                      title: 'More information',
                                      description: `Questions about your data? Read our <a href="${PRIVACY_URL}">privacy policy</a>.`,
                                  },
                              ]
                            : []),
                    ],
                },
            },
        },
    },
});
