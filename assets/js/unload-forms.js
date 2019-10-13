// warn when navigating away from a page where the user has entered data into
// certain form fields.

const FIELDS = [
    'textarea',
    'input:not([type])',
    'input[type="text"]',
    'input[type="url"]',
    'input[type="file"]',
].map(f => '.form ' + f).join(', ');

const widgetsChanged = new Set();
let hasBeforeUnloadListener = false;

function onBeforeUnload(event) {
    event.preventDefault();
    event.returnValue = "Leave the page? You'll lose your changes.";

    return event.returnValue;
}

function onChange(event) {
    const fieldEl = event.target.closest(FIELDS);

    if (!fieldEl) {
        return;
    }

    // todo: need a better way to check nothing was changed
    if (fieldEl.value !== '') {
        widgetsChanged.add(fieldEl);
    } else if (widgetsChanged.has(fieldEl)) {
        widgetsChanged.delete(fieldEl);
    }

    if (!hasBeforeUnloadListener && widgetsChanged.size > 0) {
        addEventListener('beforeunload', onBeforeUnload);

        hasBeforeUnloadListener = true;
    } else if (hasBeforeUnloadListener && widgetsChanged.size === 0) {
        removeEventListener('beforeunload', onBeforeUnload);

        hasBeforeUnloadListener = false;
    }
}

function onSubmit(event) {
    if (event.target.closest('.form')) {
        removeEventListener('beforeunload', onBeforeUnload);

        hasBeforeUnloadListener = false;
    }
}

addEventListener('change', onChange);
addEventListener('input', onChange);
addEventListener('submit', onSubmit);
