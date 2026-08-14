/* ==========================================================================
   WASD - SHARED AUTHENTICATION BEHAVIOUR

   Sign in and sign up need the same handful of things: a password visibility
   toggle, per-field error messages, one status region for whatever the server
   said, and a submit button that cannot be pressed twice.

   All of it lives here rather than being written twice in two page scripts.
   That also sidesteps a problem those page scripts had: the router swaps pages
   without reloading the document, so anything they declared at the top level
   collided with itself the second time a visitor moved between sign in and
   sign up, and the resulting syntax error took the whole script with it.

   The pages call WASDAuth.init(config) once. Re-running it on the same form is
   safe, which matters because the router re-runs page scripts on every visit.
   ========================================================================== */
(() => {
    'use strict';

    const VALID_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /* --------------------------------------------------------------- icons */

    // Kept as strings rather than fetched, so a message can be built at the
    // moment it is needed without a round trip.
    const ICON = {
        alert: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="13" height="13" fill="currentColor" aria-hidden="true"><path d="M12 2.6a1.6 1.6 0 0 1 1.4.8l8.4 15A1.6 1.6 0 0 1 20.4 21H3.6a1.6 1.6 0 0 1-1.4-2.6l8.4-15a1.6 1.6 0 0 1 1.4-.8Zm0 5.4a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0V9a1 1 0 0 0-1-1Zm0 8.2a1.15 1.15 0 1 0 0 2.3 1.15 1.15 0 0 0 0-2.3Z"/></svg>',
        check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="13" height="13" fill="currentColor" aria-hidden="true"><path d="M20.3 6.3a1 1 0 0 1 0 1.4l-9.6 9.6a1 1 0 0 1-1.4 0L4.7 12.7a1 1 0 1 1 1.4-1.4l3.9 3.9 8.9-8.9a1 1 0 0 1 1.4 0Z"/></svg>',
    };

    /* ------------------------------------------------------------ password */

    /**
     * Wires the eye button inside a password field.
     *
     * The button is type="button" on purpose. A bare <button> inside a form
     * defaults to type="submit", so showing the password would submit the form.
     */
    function bindPasswordToggle(toggle) {
        if (!toggle || toggle.dataset.bound) return;
        toggle.dataset.bound = '1';

        const input = document.getElementById(toggle.dataset.target);
        if (!input) return;

        toggle.addEventListener('click', () => {
            const nowVisible = input.type === 'password';

            input.type = nowVisible ? 'text' : 'password';
            toggle.classList.toggle('is-visible', nowVisible);
            toggle.setAttribute('aria-pressed', String(nowVisible));
            toggle.setAttribute('aria-label', nowVisible ? 'Hide password' : 'Show password');

            // Pressing the eye should not cost the user their place in the
            // field, which is what happens if focus is left on the button.
            const end = input.value.length;
            input.focus();
            input.setSelectionRange?.(end, end);
        });
    }

    /* ---------------------------------------------------------- validation */

    /** The four rules the server enforces, checked one at a time so the page
        can show which are still outstanding. */
    function passwordRules(password) {
        return {
            length: password.length >= 6,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            symbol: /[^A-Za-z0-9]/.test(password),
        };
    }

    function passwordIsStrong(password) {
        const rules = passwordRules(password);
        return rules.length && rules.upper && rules.lower && rules.number && rules.symbol;
    }

    /**
     * Paints the strength meter and ticks off the rules that are met.
     *
     * The score counts the four character classes and adds a point for real
     * length, so "Abc1!x" reads as fair rather than strong: it satisfies every
     * rule and is still six characters long.
     */
    function renderStrength(meter, password) {
        if (!meter) return;

        const rules = passwordRules(password);
        const met = ['upper', 'lower', 'number', 'symbol'].filter(k => rules[k]).length;

        let score = met;
        if (password.length >= 12) score += 1;
        if (password.length < 6) score = Math.min(score, 1);

        const levels = [
            ['', ''],
            ['is-weak', 'Weak'],
            ['is-weak', 'Weak'],
            ['is-fair', 'Fair'],
            ['is-good', 'Good'],
            ['is-strong', 'Strong'],
        ];

        const [cls, label] = levels[Math.min(score, 5)];

        meter.classList.remove('is-weak', 'is-fair', 'is-good', 'is-strong');
        if (cls && password) meter.classList.add(cls);

        const items = meter.querySelectorAll('[data-rule]');
        items.forEach(item => {
            item.classList.toggle('is-met', !!rules[item.dataset.rule]);
        });

        // The summary line does the job that a separate "Password strength"
        // label and a separate "Requirements" toggle used to do between them:
        // it names the current level, and opening it is what reveals which of
        // the five rules that level is missing.
        const summary = meter.querySelector('.strength-summary');
        if (summary) {
            summary.textContent = password ? 'Password strength: ' + label : 'Password strength';
        }
    }

    /* ------------------------------------------------------------ messages */

    /** Puts a message under one field and marks the field itself. */
    function setFieldError(input, message) {
        if (!input) return;

        const slot = document.getElementById(input.id + '-error');

        input.classList.toggle('is-invalid', !!message);
        input.setAttribute('aria-invalid', message ? 'true' : 'false');

        if (!slot) return;

        slot.innerHTML = message ? ICON.alert + '<span>' + message + '</span>' : '';
        slot.hidden = !message;
    }

    function markValid(input, valid) {
        if (input) input.classList.toggle('is-valid', valid);
    }

    /** The form-level region, for whatever the server replied. */
    function setStatus(status, message, ok) {
        if (!status) return;

        status.hidden = !message;
        status.classList.toggle('is-success', !!ok);
        status.innerHTML = message
            ? (ok ? ICON.check : ICON.alert) + '<span>' + message + '</span>'
            : '';
    }

    function clearAll(fields, status) {
        setStatus(status, '');
        fields.forEach(input => {
            setFieldError(input, '');
            markValid(input, false);
        });
    }

    /* --------------------------------------------------------------- submit */

    function setBusy(button, busy, busyLabel) {
        if (!button) return;

        if (busy) {
            button.dataset.idleLabel = button.dataset.idleLabel || button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="btn-spinner"></span>' + busyLabel;
        } else {
            button.disabled = false;
            if (button.dataset.idleLabel) button.innerHTML = button.dataset.idleLabel;
        }
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /* ----------------------------------------------------------------- init */

    /**
     * config = {
     *   form, button, status, endpoint, busyLabel, redirect,
     *   fields:   [{ input, validate(value) -> null | 'message', required }],
     *   payload:  () -> object sent as JSON,
     *   onSuccess(data) -> optional message shown before the redirect
     * }
     */
    function init(config) {
        const form = document.getElementById(config.form);
        if (!form || form.dataset.authBound) return;
        form.dataset.authBound = '1';

        const button = document.getElementById(config.button);
        const status = document.getElementById(config.status);

        const fields = config.fields.map(f => ({
            ...f,
            el: document.getElementById(f.input),
        })).filter(f => f.el);

        const inputs = fields.map(f => f.el);

        // Password plumbing: the visibility toggle and the strength meter.
        form.querySelectorAll('.password-toggle').forEach(bindPasswordToggle);

        fields.forEach(field => {
            if (field.strength) {
                const meter = document.getElementById(field.strength);
                field.el.addEventListener('input', () => renderStrength(meter, field.el.value));
                renderStrength(meter, field.el.value);
            }

            // Checked when the user leaves the field, so a mistake is caught
            // next to the field that caused it rather than at the end.
            field.el.addEventListener('blur', () => {
                const value = field.el.value;
                if (value.trim() === '') return;

                const problem = field.validate ? field.validate(value, form) : null;
                setFieldError(field.el, problem);
                markValid(field.el, !problem);
            });

            // Typing clears the complaint. Leaving an error on screen while
            // somebody is actively fixing it just nags them.
            field.el.addEventListener('input', () => {
                if (field.el.classList.contains('is-invalid')) setFieldError(field.el, '');
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearAll(inputs, status);

            // Report the first problem and put the cursor in the field that
            // caused it, rather than listing four complaints at once.
            for (const field of fields) {
                const value = field.el.value;

                if (field.required !== false && value.trim() === '') {
                    setFieldError(field.el, field.emptyMessage || 'This field is required.');
                    field.el.focus();
                    return;
                }

                const problem = field.validate ? field.validate(value, form) : null;
                if (problem) {
                    setFieldError(field.el, problem);
                    field.el.focus();
                    return;
                }
            }

            setBusy(button, true, config.busyLabel || 'Working');

            try {
                const response = await fetch(config.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken(),
                    },
                    body: JSON.stringify(config.payload()),
                });

                const data = await response.json().catch(() => null);

                if (!response.ok || !data || data.status !== 'success') {
                    setBusy(button, false);

                    // The server names the field it rejected where it can, so
                    // the message can be attached to that field instead of
                    // floating at the bottom of the form.
                    const named = data && data.field && document.getElementById(data.field);
                    if (named) {
                        setFieldError(named, data.error);
                        named.focus();
                    }

                    setStatus(status, (data && data.error) || 'Something went wrong. Try again.');
                    return;
                }

                setStatus(status, config.onSuccess ? config.onSuccess(data) : 'Signed in.', true);

                // A full navigation rather than the router: the header decides
                // "signed in" or "guest" on a real page load, and that is
                // exactly what just changed.
                window.location.href = config.redirect;
            } catch (err) {
                setBusy(button, false);
                setStatus(status, 'Could not reach the server. Check your connection and try again.');
            }
        });

        // Focus the first empty field so a returning visitor can start typing.
        const firstEmpty = inputs.find(el => el.value.trim() === '');
        if (firstEmpty && !window.matchMedia('(max-width: 520px)').matches) {
            firstEmpty.focus({ preventScroll: true });
        }
    }

    window.WASDAuth = {
        init,
        VALID_EMAIL,
        passwordRules,
        passwordIsStrong,
        setFieldError,
        setStatus,
    };
})();
