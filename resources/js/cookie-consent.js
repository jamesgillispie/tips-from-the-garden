// One privacy-choice surface for essential cookies, optional analytics and AI
// processing. vanilla-cookieconsent stores the decision in its first-party
// `cc_cookie`; authenticated choices are mirrored to the user row because
// queued jobs must never trust browser state alone.

import * as CookieConsent from 'vanilla-cookieconsent';

window.CookieConsent = CookieConsent;

const PRIVACY_URL = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const syncUrl = document.querySelector('meta[name="ai-consent-sync-url"]')?.content;
const accountUserId = document.querySelector('meta[name="ai-consent-user-id"]')?.content;
const accountAiEnabled = document.querySelector('meta[name="ai-consent-enabled"]')?.content === '1';
let needsAccountDecision = document.querySelector('meta[name="ai-consent-needs-decision"]')?.content === '1';
let browserCheckInDue = document.querySelector('meta[name="ai-check-in-due"]')?.content === '1';
let suppressBrowserSync = false;

function syncGoogleConsent() {
    if (typeof window.gtag !== 'function') return;

    window.gtag('consent', 'update', {
        analytics_storage: CookieConsent.acceptedCategory('analytics') ? 'granted' : 'denied',
    });
}

function syncRegistrationFields() {
    const recorded = CookieConsent.validConsent() ? '1' : '0';
    const ai = CookieConsent.acceptedCategory('ai') ? '1' : '0';
    const analytics = CookieConsent.acceptedCategory('analytics') ? '1' : '0';

    document.querySelectorAll('[data-consent-recorded]').forEach((input) => { input.value = recorded; });
    document.querySelectorAll('[data-ai-consent]').forEach((input) => { input.value = ai; });
    document.querySelectorAll('[data-analytics-consent]').forEach((input) => { input.value = analytics; });
}

async function syncAccountChoice({ aiChanged = false, reaffirmed = false } = {}) {
    if (!syncUrl || !csrfToken || suppressBrowserSync) return;

    try {
        const response = await fetch(syncUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                ai_enabled: CookieConsent.acceptedCategory('ai'),
                analytics_enabled: CookieConsent.acceptedCategory('analytics'),
                ai_changed: aiChanged,
                reaffirmed,
            }),
        });

        if (!response.ok) throw new Error(`Consent sync failed with ${response.status}`);

        needsAccountDecision = false;
        if (reaffirmed || aiChanged) browserCheckInDue = false;

        if (accountUserId) {
            CookieConsent.setCookieData({
                mode: 'update',
                value: { aiUserId: accountUserId },
            });
        }
    } catch (error) {
        // The database remains unchanged on failure, so never let the closed
        // banner imply that an opt-out succeeded when it did not.
        window.Flux?.toast({
            text: 'We could not save that privacy choice. Please open Privacy choices and try again.',
            variant: 'danger',
        });
        console.warn('Could not save privacy choices.', error);
    }
}

function acceptedCategoriesWithAi(enabled) {
    const accepted = CookieConsent.getUserPreferences().acceptedCategories
        .filter((category) => category !== 'necessary' && category !== 'ai');

    if (enabled) accepted.push('ai');

    return accepted;
}

function alignBrowserWithAccount(enabled) {
    if (!CookieConsent.validConsent() || CookieConsent.acceptedCategory('ai') === enabled) return;

    suppressBrowserSync = true;
    CookieConsent.acceptCategory(acceptedCategoriesWithAi(enabled));
    queueMicrotask(() => { suppressBrowserSync = false; });
}

const consentConfig = {
    revision: 1,
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
            autoClear: {
                cookies: [{ name: /^_ga/ }, { name: '_gid' }],
            },
        },
        ai: {
            enabled: false,
        },
    },

    onFirstConsent: () => {
        syncGoogleConsent();
        syncRegistrationFields();
        void syncAccountChoice({ aiChanged: true, reaffirmed: true });
    },
    onConsent: () => {
        syncGoogleConsent();
        syncRegistrationFields();
    },
    onChange: ({ changedCategories }) => {
        syncGoogleConsent();
        syncRegistrationFields();

        if (suppressBrowserSync) return;

        const aiChanged = changedCategories.includes('ai');
        void syncAccountChoice({
            aiChanged,
            reaffirmed: aiChanged || needsAccountDecision || browserCheckInDue,
        });
    },

    language: {
        default: 'en',
        translations: {
            en: {
                consentModal: {
                    title: 'Your privacy choices',
                    description:
                        'Essential cookies keep the site working. Optional analytics help us improve it, ' +
                        'and AI processing can transcribe recordings, write entries and learn your style. ' +
                        'Both optional choices are off until you say yes.',
                    acceptAllBtn: 'Allow optional features',
                    acceptNecessaryBtn: 'Use essentials only',
                    showPreferencesBtn: 'Choose what is on',
                    ...(PRIVACY_URL && {
                        footer: `<a href="${PRIVACY_URL}">Privacy policy</a>`,
                    }),
                },
                preferencesModal: {
                    title: 'Privacy choices',
                    acceptAllBtn: 'Allow all',
                    acceptNecessaryBtn: 'Use essentials only',
                    savePreferencesBtn: 'Save my choices',
                    closeIconLabel: 'Close',
                    sections: [
                        {
                            title: 'You are in control',
                            description:
                                'Choose each optional use separately. Signed-in gardeners can tune ' +
                                'transcription, writing and voice learning further in Account Settings.',
                        },
                        {
                            title: 'Strictly necessary',
                            description:
                                'Needed for signing in, security, sessions and remembering these choices. ' +
                                'These cannot be switched off.',
                            linkedCategory: 'necessary',
                        },
                        {
                            title: 'Analytics',
                            description:
                                'Helps us understand where people get stuck. If this stays off, monthly ' +
                                'AI check-ins arrive by email instead of reopening this panel.',
                            linkedCategory: 'analytics',
                        },
                        {
                            title: 'AI processing',
                            description:
                                'Allows model-backed transcription, journal writing and voice learning. ' +
                                'Raw audio stays on our server and is never sent to the writing model.',
                            linkedCategory: 'ai',
                        },
                        ...(PRIVACY_URL
                            ? [{
                                  title: 'More information',
                                  description: `Questions about your data? Read our <a href="${PRIVACY_URL}">privacy policy</a>.`,
                              }]
                            : []),
                    ],
                },
            },
        },
    },
};

// Saving an unchanged preferences panel does not trigger vanilla-cookieconsent's
// onChange callback. Capture that explicit click so it still counts as the
// monthly re-affirmation (or an existing user's first account decision).
document.addEventListener('click', (event) => {
    const button = event.target.closest?.('#cc-main [data-role="save"], #cc-main [data-role="all"], #cc-main [data-role="necessary"]');

    if (!button || (!needsAccountDecision && !browserCheckInDue)) return;

    setTimeout(() => {
        void syncAccountChoice({
            aiChanged: needsAccountDecision,
            reaffirmed: true,
        });
    }, 0);
});

CookieConsent.run(consentConfig).then(() => {
    syncGoogleConsent();
    syncRegistrationFields();

    if (syncUrl) {
        if (needsAccountDecision) {
            // A valid cookie may belong to an earlier visit or another account;
            // ask explicitly rather than importing it silently.
            if (CookieConsent.validConsent()) {
                setTimeout(() => CookieConsent.showPreferences(), 250);
            }
        } else {
            alignBrowserWithAccount(accountAiEnabled);

            if (accountUserId) {
                CookieConsent.setCookieData({
                    mode: 'update',
                    value: { aiUserId: accountUserId },
                });
            }
        }

        if (browserCheckInDue && !needsAccountDecision) {
            setTimeout(() => CookieConsent.showPreferences(), 250);
        }
    }
});

function registerAccountConsentListener() {
    window.Livewire?.on('ai-consent-updated', (payload) => {
        const detail = Array.isArray(payload) ? payload[0] : payload;
        const enabled = Boolean(detail?.aiEnabled);

        suppressBrowserSync = true;
        CookieConsent.acceptCategory(acceptedCategoriesWithAi(enabled));
        CookieConsent.setCookieData({
            mode: 'update',
            value: { aiUserId: String(detail?.userId ?? accountUserId ?? '') },
        });
        queueMicrotask(() => { suppressBrowserSync = false; });
    });
}

if (window.Livewire) {
    registerAccountConsentListener();
} else {
    document.addEventListener('livewire:init', registerAccountConsentListener, { once: true });
}
