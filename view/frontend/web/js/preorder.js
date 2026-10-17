/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 *
 * Saurabh Pre-Order storefront script.
 *
 * Framework-free so the same file runs on Luma (loaded via RequireJS / x-magento-init) and on Hyva
 * (loaded via a plain <script src defer>). Reads its config from [data-saurabh-preorder] elements,
 * uses no inline scripts, eval or inline styles, so it is compatible with strict Content Security Policies.
 */
(function (root, factory) {
    'use strict';

    var api = root.SaurabhPreorder || factory(root, root.document);

    root.SaurabhPreorder = api;

    if (typeof root.define === 'function' && root.define.amd) {
        root.define(['jquery'], function ($) {
            api.setJQuery($);

            return function () {
                api.initAll();
            };
        });
    } else if (root.document.readyState === 'loading') {
        root.document.addEventListener('DOMContentLoaded', api.initAll);
    } else {
        api.initAll();
    }
}(window, function (window, document) {
    'use strict';

    var jq = null,
        NOTE_CLASS = 'saurabh-preorder-note';

    /**
     * Element holding the visible button text
     */
    function textHolder(button) {
        return button.querySelector('span:not(.sr-only):not([class*="hidden"])') ||
            button.querySelector('span') || button;
    }

    /**
     * Replace the button text and keep it when other scripts reset it (e.g. Luma "Added" -> "Add to Cart")
     */
    function setButtonLabel(button, label) {
        var holder,
            observer;

        if (!button) {
            return;
        }
        holder = textHolder(button);

        if (!button.hasAttribute('data-saurabh-original')) {
            button.setAttribute('data-saurabh-original', holder.textContent.trim());
            ['title', 'aria-label'].forEach(function (attr) {
                if (button.hasAttribute(attr)) {
                    button.setAttribute('data-saurabh-original-' + attr, button.getAttribute(attr));
                }
            });
            observer = new MutationObserver(function () {
                var current = button.getAttribute('data-saurabh-label');

                if (current && holder.textContent.trim() === button.getAttribute('data-saurabh-original')) {
                    holder.textContent = current;
                }
            });
            observer.observe(holder, {childList: true, characterData: true, subtree: true});
        }

        button.setAttribute('data-saurabh-label', label || '');
        holder.textContent = label || button.getAttribute('data-saurabh-original');

        ['title', 'aria-label'].forEach(function (attr) {
            var original = button.getAttribute('data-saurabh-original-' + attr);

            if (original !== null) {
                button.setAttribute(attr, label ?
                    original.replace(button.getAttribute('data-saurabh-original'), label) : original);
            }
        });
    }

    function createNote(extraClass) {
        var note = document.createElement('div');

        note.className = NOTE_CLASS + (extraClass ? ' ' + extraClass : '');
        note.setAttribute('role', 'status');
        note.setAttribute('aria-live', 'polite');
        note.hidden = true;

        return note;
    }

    function renderNote(note, state) {
        var text = state && (state.preorder || state.blocked) ? state.note : '';

        note.textContent = text || '';
        note.hidden = !text;
        note.classList.toggle(NOTE_CLASS + '--warning', !!(state && state.blocked));
    }

    function firstMatch(selectors, scope) {
        var list = (selectors || '').split(','),
            i,
            el;

        for (i = 0; i < list.length; i++) {
            if (!list[i].trim()) {
                continue;
            }
            try {
                el = (scope || document).querySelector(list[i].trim());
            } catch (e) {
                el = null;
            }
            if (el) {
                return el;
            }
        }

        return null;
    }

    /**
     * Product page
     */
    function initProduct(config) {
        var selectors = config.selectors || {},
            form = document.querySelector(selectors.form) || document.querySelector('form[data-product-sku]'),
            button = document.querySelector(selectors.button),
            note = createNote('saurabh-preorder-note--product'),
            target = firstMatch(selectors.note, document.querySelector('.product-info-main')) ||
                firstMatch(selectors.note),
            children = config.children || {},
            timer = null;

        if (target) {
            target.insertAdjacentElement('afterend', note);
        } else if (button && button.closest('.box-tocart, .fieldset, div')) {
            button.closest('.box-tocart, .fieldset, div').insertAdjacentElement('beforebegin', note);
        } else if (form) {
            form.insertAdjacentElement('afterbegin', note);
        }

        function apply(state) {
            setButtonLabel(button, state && state.preorder ? state.label : '');
            renderNote(note, state);
        }

        function selectedChild() {
            var id,
                attrId,
                field,
                matches;

            for (id in children) {
                if (!children.hasOwnProperty(id) || !children[id].attributes) {
                    continue;
                }
                matches = true;
                for (attrId in children[id].attributes) {
                    if (children[id].attributes.hasOwnProperty(attrId)) {
                        field = (form || document).querySelector('[name="super_attribute[' + attrId + ']"]');
                        if (!field || String(field.value) !== String(children[id].attributes[attrId])) {
                            matches = false;
                            break;
                        }
                    }
                }
                if (matches) {
                    return children[id];
                }
            }

            return null;
        }

        function refreshConfigurable() {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                apply(selectedChild() || config.product);
            }, 50);
        }

        apply(config.product);

        if (config.type === 'configurable') {
            ['change', 'click', 'keyup'].forEach(function (type) {
                document.addEventListener(type, function (event) {
                    if (!form || form.contains(event.target) ||
                        (event.target.closest && event.target.closest('.swatch-opt, .product-options-wrapper'))) {
                        refreshConfigurable();
                    }
                }, true);
            });
            // Luma swatches/dropdowns update hidden inputs through jQuery events
            if (jq) {
                jq(document).on('change', '[name^="super_attribute"]', refreshConfigurable);
            }
            // Hyva configurable component
            window.addEventListener('configurable-selection-changed', function (event) {
                var index = event.detail && event.detail.productIndex;

                if (index && children[index]) {
                    apply(children[index]);
                } else {
                    refreshConfigurable();
                }
            });
        }

        if (config.type === 'grouped') {
            Object.keys(children).forEach(function (id) {
                var input = document.querySelector('[name="super_group[' + id + ']"]'),
                    row = input ? input.closest('tr, [class*="grouped-item"], li') : null,
                    cell,
                    rowNote;

                if (!row || !(children[id].preorder || children[id].blocked)) {
                    return;
                }
                cell = row.querySelector('td, .product-item-name, div') || row;
                rowNote = createNote('saurabh-preorder-note--grouped');
                cell.appendChild(rowNote);
                renderNote(rowNote, children[id]);
            });
        }
    }

    /**
     * Category / search listing
     */
    function initListing(config) {
        var items = config.items || {};

        Object.keys(items).forEach(function (id) {
            var item = items[id],
                inputs = document.querySelectorAll('input[name="product"][value="' + id + '"]'),
                priceBoxes = document.querySelectorAll('.price-box[data-product-id="' + id + '"]');

            Array.prototype.forEach.call(inputs, function (input) {
                var form = input.form || input.closest('form'),
                    button = form ? form.querySelector('button[type="submit"], .action.tocart') : null;

                if (button && item.label) {
                    setButtonLabel(button, item.label);
                }
            });

            if (!item.note) {
                return;
            }
            Array.prototype.forEach.call(priceBoxes.length ? priceBoxes : inputs, function (anchor) {
                var container = anchor.closest('.product-item-info, .product-item, form, li') || anchor.parentNode,
                    price = anchor.matches('[data-product-id]') ? anchor : container.querySelector('.price-box'),
                    note;

                if (!container || container.querySelector('.' + NOTE_CLASS)) {
                    return;
                }
                note = createNote('saurabh-preorder-note--listing');
                renderNote(note, {preorder: true, note: item.note});
                if (price) {
                    price.insertAdjacentElement('afterend', note);
                } else {
                    container.insertAdjacentElement('afterbegin', note);
                }
            });
        });
    }

    function initAll() {
        var elements = document.querySelectorAll('[data-saurabh-preorder]:not([data-saurabh-preorder-ready])');

        Array.prototype.forEach.call(elements, function (element) {
            var config;

            element.setAttribute('data-saurabh-preorder-ready', '1');
            try {
                config = JSON.parse(element.getAttribute('data-saurabh-preorder'));
            } catch (e) {
                return;
            }
            if (!config) {
                return;
            }
            if (config.listing) {
                initListing(config);
            } else {
                initProduct(config);
            }
        });
    }

    return {
        initAll: initAll,
        setButtonLabel: setButtonLabel,
        setJQuery: function ($) {
            jq = $;
        }
    };
}));
