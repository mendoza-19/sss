let lanternManagerModal = undefined;

class ModalManager {
    modals;
    discardClose;
    displayType;
    modalZIndex;
    modalShowing;
    constructor() {
        this.discardClose = false;
        this.displayType = 'flex';
        this.modalZIndex = 0;
        this.modals = [];
        this.modalShowing = [];

        document.querySelector('body').addEventListener('click', (event) => this.hiddenLastModal(event, this));
    }

    addModal(id = '') {
        if (id !== '') {
            let modal = document.getElementById(id);
            if (modal) {
                modal.addEventListener('click', (event) => {
                    this.discardClose = true;
                    if (lanternManagerModal !== this) {
                        lanternManagerModal = this;
                    }
                });
                modal.classList.add('modal');
                modal.classList.add('modal-container');
                this.modals.push(modal);
            } else {
                console.error('Modal not found');
            }
        } else {
            console.error('Modal id is required');
        }
    }

    showModalId(id = '') {
        if (id !== '') {
            let modal = this.modals.find(m => m.id === id);
            if (modal && this.modalShowing.find(m => m.id === id) == undefined) {
                modal.style.display = this.displayType;
                modal.style.position = 'fixed';
                modal.style.zIndex = this.modalZIndex + 2000;
                this.modalZIndex++;
                this.modalShowing.push(modal);
                this.discardClose = true;
            } else {
                console.error('Modal not found');
            }
        } else {
            console.error('Modal id is required');
        }
    }

    hiddenModalId(id = '') {
        if (id !== '') {
            let modal = this.modals.find(m => m.id === id);
            if (modal && this.modalShowing.find(m => m.id === id) !== undefined) {
                modal.style.display = "none";
                this.modalShowing = this.modalShowing.filter(m => m.id !== id);
                for (let i = (modal.style.zIndez - 20000); i < this.modalShowing.length; i++) {
                    this.modalShowing[i].style.zIndex = this.modalShowing[i].style.zIndex - 1;
                }
                this.modalZIndex--;
            } else {
                console.error('Modal not found');
            }
        } else {
            console.error('Modal id is required');
        }
    }

    hiddenLastModal(event) {
        if (this.modalShowing.length > 0) {
            let modal = this.modalShowing;
            modal = modal[modal.length - 1];
            if (modal && !this.discardClose) {
                modal.style.display = "none";
                this.modalShowing = this.modalShowing.filter(m => m.id !== modal.id);
                this.modalZIndex--;
            } else if (this.discardClose) {
                this.discardClose = false;
            } else {
                console.error('Modal not found');
            }
        }
    }

    hiddenAllModals() {
        this.modals.forEach(modal => {
            modal.style.display = "none";
        });
        this.modalShowing = [];
        this.modalZIndex = 0;
    }

    eventClickCloseModal(event) {
        if (event) {
            let starElement = event.target;
            while (starElement.classList.contains('modal') == false && starElement != document.body) {
                starElement = starElement.parentElement;
            }
            if (starElement && starElement != document.body) {
                starElement.style.display = "none";
                this.modalShowing = this.modalShowing.filter(m => m.id !== starElement.id);
                for (let i = (starElement.style.zIndez - 20000); i < this.modalShowing.length; i++) {
                    this.modalShowing[i].style.zIndex = this.modalShowing[i].style.zIndex - 1;
                }
                this.modalZIndex--;
            }
        }
    }

    /*addModal(modal) {
        this.modals.push(modal);
    }

    removeModal(modal) {
        this.modals = this.modals.filter(m => m !== modal);
    }

    getModals() {
        return this.modals;
    }

    getModal(modal) {
        return this.modals.find(m => m === modal);
    }

    getModalIndex(modal) {
        return this.modals.findIndex(m => m === modal);
    }

    getModalPosition(modal) {
        return this.modals.indexOf(modal);
    }

    getModalPosition(modal) {
        return this.modals.indexOf(modal);
    }*/
}