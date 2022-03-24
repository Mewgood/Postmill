import { Controller } from 'stimulus';

const isFileEvent = (event) => {
    const { items } = event.dataTransfer;

    if (items.length === 0) {
        // Safari won't tell us when files are being dragged, so we just have to
        // assume this is a single file with a valid mime type.
        return true;
    }

    return items.length === 1 && items[0].kind === 'file' &&
        /^image\/(jpeg|gif|png)$/.test(items[0].type);
};

export default class extends Controller {
    static classes = ['activeOverlay']
    static targets = ['fileInput', 'overlay', 'toggle'];

    connect() {
        this.element.style.position = 'relative';

        let globalCount = 0;
        let elementCount = 0;

        document.addEventListener('dragover', (event) => {
            if (isFileEvent(event)) {
                event.preventDefault();
            }
        });

        document.addEventListener('dragenter', (event) => {
            globalCount++;

            if (isFileEvent(event)) {
                this.overlayTarget.hidden = false;
            }
        });

        document.addEventListener('dragleave', () => {
            if (--globalCount === 0) {
                this.overlayTarget.hidden = true;
            }
        });

        document.addEventListener('drop', () => {
            globalCount = elementCount = 0;
            this.overlayTarget.hidden = true;
            this.overlayTarget.classList.remove(this.activeOverlayClass);
        });

        this.element.addEventListener('dragenter', (event) => {
            elementCount++;

            if (isFileEvent(event)) {
                this.overlayTarget.classList.add(this.activeOverlayClass);
            }
        });

        this.element.addEventListener('dragleave', () => {
            if (--elementCount <= 0) {
                this.overlayTarget.classList.remove(this.activeOverlayClass);
            }
        });

        this.element.addEventListener('drop', (event) => {
            if (isFileEvent(event)) {
                event.preventDefault();

                if (this.toggleTarget) {
                    this.toggleTarget.click();
                }

                this.fileInputTarget.files = event.dataTransfer.files;
            }
        });
    }
}
