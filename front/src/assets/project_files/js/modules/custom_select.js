export default class CustomSelect {
    constructor(selector, config) {
        if (!selector) {
            console.error('Селектор не передан');
            return false;
        }
        this.selector = selector;
        this.elements = document.querySelectorAll(selector);
        if (!this.elements.length) {
            return false;
        }
        this.defaults = {
            openClass: 'open',
            selectedClass: 'selected',
            disabledClass: 'disabled',
            hideClass: 'v_hidden',
            creatable: {
                listWrap: {
                    tagName: 'div',
                    className: ['select'],
                    parentSelector: selector
                },
                scrollWrap:{
                    tagName: 'div',
                    className: ['select__wrap'],
                    parentSelector: '.select'
                },
                list: {
                    className: ['select__list'],
                    tagName: 'ul',
                    parentSelector: '.select__wrap'
                },
                listItem: {
                    className: ['select__item'],
                    tagName: 'li',
                    parentSelector: '.select__list'
                },
                selectedItem: {
                    className: ['select__item_selected'],
                    tagName: 'span',
                    parentSelector: '.select'
                }
            },
        }
        this.config = Object.assign(this.defaults, config);
        this.selector = selector;
        this.changeEvent = new Event('change', {bubbles: true, cancelable: true})
        this.elements.forEach(element => {
            this.initialize(element);
        });
        document.body.addEventListener('click', e => {
            if (!e.target.classList.contains(this.selector) && !e.target.closest(this.selector)) {
                this.elements.forEach(element => {
                    element.classList.remove(this.config.openClass);
                });
            }
        });
    }

    initialize(element) {
        const select = element.querySelector(`select:not([class=${this.config.hideClass}])`);
        if(!select) return true;
        const options = select.querySelectorAll('option'),
            selectClasses = Array.from(select.classList),
            optionSelected = select.querySelector('option[selected]') || options[0];

        select.classList.add(this.config.hideClass);
        for (let key in this.config.creatable) {
            let tagName = this.config.creatable[key]['tagName'],
                classNames = this.config.creatable[key]['className'],
                parentEl = (key === 'listWrap') ? element : element.querySelector(this.config.creatable[key]['parentSelector']);
            if (key === 'listItem') {
                options.forEach(el => {
                    let item = this.createElement(tagName, parentEl, classNames, el.value, el.innerText, el.selected, el.disabled);
                    item.addEventListener('click', e => this.selectItem(e, select, element));
                });
            } else if (key === 'selectedItem') {
                this.createElement(tagName, parentEl, classNames, '', optionSelected?.innerText);
            }else if(key === 'listWrap'){
                classNames = classNames.concat(selectClasses);
                this.createElement(tagName, parentEl, classNames);
            }else {
                this.createElement(tagName, parentEl, classNames);
            }
        }
        const selectedItem = element.querySelector('.' + this.config.creatable.selectedItem.className);
        selectedItem.addEventListener('click', e => this.toggleClass(e, element));
    }

    reset(element) {
        const item = element.querySelector('.' + this.config.creatable.listItem.className),
            click = new Event('click');
        item.dispatchEvent(click);
    }

    update(target){
        const options = target.querySelectorAll('option');
        const wrapper = target.closest(this.selector);
        const list = wrapper.querySelector('.'+this.config.creatable.list.className);
        const tagName = this.config.creatable.listItem.tagName;
        const classNames = this.config.creatable.listItem.className;
        const clickEvent =  new Event('click');
        list.innerHTML = '';
        options.forEach(el => {
            let item = this.createElement(tagName, list, classNames, el.value, el.innerText, el.selected, el.style.display);
            item.addEventListener('click', e => this.selectItem(e, target, wrapper));
            if(el.selected){
                item.dispatchEvent(clickEvent);
            }
        });
    }

    toggleClass(e, element) {
        const opens = document.querySelectorAll('.' + this.config.openClass);
        if (opens.length) {
            opens.forEach(el => {
                if (el !== element) {
                    el.classList.remove(this.config.openClass);
                }
            });
        }
        element.classList.toggle(this.config.openClass);
    }

    selectItem(e, select, element) {
        const customSelectItems = element.querySelectorAll('.' + this.config.creatable.listItem.className),
            selectedItem = element.querySelector('.' + this.config.creatable.selectedItem.className);
        customSelectItems.forEach(elem => {
            elem.classList.remove(this.config.selectedClass);
        });

        if (selectedItem) {
            selectedItem.innerText = e.target.innerText;
        }
        select.value = e.target.dataset.value;
        e.target.classList.add(this.config.selectedClass);
        element.classList.remove(this.config.openClass);
        select.dispatchEvent(this.changeEvent);
    }

    createElement(tagName, parentEl, classNames, val, text, select, disabled, display) {
        const elem = document.createElement(tagName);
        classNames ? elem.classList.add(...classNames) : '';
        val ? elem.dataset.value = val : '';
        text ? elem.innerHTML = text : '';
        display ? elem.style.display = display : '';
        disabled ? elem.classList.add(this.config.disabledClass) : '';
        select ? elem.classList.add(this.config.selectedClass) : '';
        parentEl ? parentEl.appendChild(elem) : '';
        return elem;
    }
}