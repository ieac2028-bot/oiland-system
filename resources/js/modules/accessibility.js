const root = document.documentElement;
const trigger = document.querySelector('[data-accessibility-toggle]');
const panel = document.querySelector('[data-accessibility-panel]');

const defaults = {
    text: 'default',
    contrast: false,
    motion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    links: false,
    spacing: false,
    font: false,
};

let settings = { ...defaults };

try {
    const savedSettings = JSON.parse(localStorage.getItem('oiland-accessibility') || 'null');

    if (savedSettings && typeof savedSettings === 'object') {
        const migratedTextSize = { 0: 'default', 1: 'large', 2: 'largest' }[savedSettings.textSize];
        settings = {
            ...settings,
            ...savedSettings,
            text: ['default', 'large', 'largest'].includes(savedSettings.text) ? savedSettings.text : (migratedTextSize ?? 'default'),
        };
    }
} catch (error) {
    // Keep the controls available when storage is disabled or invalid.
}

const applySettings = () => {
    root.classList.toggle('a11y-text-large', settings.text === 'large');
    root.classList.toggle('a11y-text-largest', settings.text === 'largest');
    root.classList.toggle('a11y-high-contrast', Boolean(settings.contrast));
    root.classList.toggle('a11y-underline-links', Boolean(settings.links));
    root.classList.toggle('a11y-text-spacing', Boolean(settings.spacing));
    root.classList.toggle('a11y-plain-font', Boolean(settings.font));
    root.classList.toggle('a11y-reduce-motion', Boolean(settings.motion));

    panel?.querySelectorAll('[data-a11y-size]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.a11ySize === settings.text));
    });

    panel?.querySelectorAll('[data-a11y-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(Boolean(settings[button.dataset.a11yToggle])));
    });

    try {
        localStorage.setItem('oiland-accessibility', JSON.stringify(settings));
    } catch (error) {
        // Apply settings for this page even when they cannot be persisted.
    }
};

const closePanel = (returnFocus = false) => {
    if (!panel || !trigger) {
        return;
    }

    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');

    if (returnFocus) {
        trigger.focus();
    }
};

applySettings();

trigger?.addEventListener('click', () => {
    if (!panel) {
        return;
    }

    panel.hidden = !panel.hidden;
    trigger.setAttribute('aria-expanded', String(!panel.hidden));

    if (!panel.hidden) {
        panel.querySelector('button')?.focus();
    }
});

panel?.querySelector('[data-accessibility-close]')?.addEventListener('click', () => closePanel(true));

panel?.querySelectorAll('[data-a11y-size]').forEach((button) => {
    button.addEventListener('click', () => {
        settings.text = button.dataset.a11ySize;
        applySettings();
    });
});

panel?.querySelectorAll('[data-a11y-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const setting = button.dataset.a11yToggle;
        settings[setting] = !settings[setting];
        applySettings();
    });
});

panel?.querySelector('[data-a11y-reset]')?.addEventListener('click', () => {
    settings = { ...defaults };
    applySettings();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && panel && !panel.hidden) {
        closePanel(true);
    }
});

document.addEventListener('pointerdown', (event) => {
    if (panel && !panel.hidden && !event.target.closest('[data-accessibility]')) {
        closePanel();
    }
});

const ambientLines = [...document.querySelectorAll('[data-ambient-lines] path')];
const canUsePointerMotion = window.matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)').matches;

if (ambientLines.length > 0 && canUsePointerMotion) {
    window.addEventListener('pointermove', (event) => {
        if (event.pointerType === 'touch' || root.classList.contains('a11y-reduce-motion')) {
            return;
        }

        const pointerX = (event.clientX / window.innerWidth - 0.5) * 2;
        const pointerY = (event.clientY / window.innerHeight - 0.5) * 2;

        ambientLines.forEach((path, index) => {
            const depth = (index % 2 === 0 ? 1 : -1) * (0.45 + index * 0.16);
            path.style.setProperty('--line-pointer-x', `${-pointerX * 72 * depth}px`);
            path.style.setProperty('--line-pointer-y', `${-pointerY * 52 * depth}px`);
        });
    });
}
