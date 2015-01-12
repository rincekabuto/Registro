(function(window, document, undefined) {
    var ruleRegex = /^(.+?)\[(.+)\]$/,
        emailRegex = /^([a-zA-Z0-9_\+\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/,
        alphaNumericRegex = /^[a-z0-9]+$/i;

    var FormValidator = function(formNameOrNode, fields, callback, messages) {
        this.callback = callback || function(errors){};
        this.errors = [];
        this.fields = {};
        this.form = this._formByNameOrNode(formNameOrNode) || {};
        this.messages = messages;
        for (var i = 0, fieldLength = fields.length; i < fieldLength; i++) {
            var field = fields[i];
            if ((!field.name && !field.names) || !field.rules) {
                continue;
            }
            if (field.names) {
                for (var j = 0, fieldNamesLength = field.names.length; j < fieldNamesLength; j++) {
                    this._addField(field, field.names[j]);
                }
            } else {
                this._addField(field, field.name);
            }
        }
        var _onsubmit = this.form.onsubmit;
        this.form.onsubmit = (function(that) {
            return function(evt) {
                try {
                    return that._validateForm(evt) && (_onsubmit === undefined || _onsubmit());
                } catch(e) {}
            };
        })(this);
    },
    attributeValue = function (element, attributeName) {
        var i;
        if ((element.length > 0) && (element[0].type === 'radio' || element[0].type === 'checkbox')) {
            for (i = 0, elementLength = element.length; i < elementLength; i++) {
                if (element[i].checked) {
                    return element[i][attributeName];
                }
            }
            return;
        }
        return element[attributeName];
    };
    FormValidator.prototype._formByNameOrNode = function(formNameOrNode) {
        return (typeof formNameOrNode === 'object') ? formNameOrNode : document.forms[formNameOrNode];
    };
    FormValidator.prototype._addField = function(field, nameValue)  {
        this.fields[nameValue] = {
            name: nameValue,
            display: field.display || nameValue,
            rules: field.rules,
            id: null,
            element: null,
            type: null,
            value: null,
            checked: null
        };
    };
    FormValidator.prototype._validateForm = function(evt) {
        this.errors = [];
        for (var key in this.fields) {
            if (this.fields.hasOwnProperty(key)) {
                var field = this.fields[key] || {},
                    element = this.form[field.name];
                if (element && element !== undefined) {
                    field.id = attributeValue(element, 'id');
                    field.element = element;
                    field.type = (element.length > 0) ? element[0].type : element.type;
                    field.value = attributeValue(element, 'value');
                    field.checked = attributeValue(element, 'checked');
                    this._validateField(field);
                }
            }
        }
        if (typeof this.callback === 'function') {
            this.callback(this.errors, evt);
        }
        if (this.errors.length > 0) {
            if (evt && evt.preventDefault) {
                evt.preventDefault();
            } else if (event) {
                event.returnValue = false;
            }
        }
        return true;
    };
    FormValidator.prototype._validateField = function(field) {
        var rules = field.rules.split('|'),
            indexOfRequired = field.rules.indexOf('required'),
            isEmpty = (!field.value || field.value === '' || field.value === undefined);
        for (var i = 0, ruleLength = rules.length; i < ruleLength; i++) {
            var method = rules[i],
                param = null,
                failed = false,
                parts = ruleRegex.exec(method);
            if (indexOfRequired === -1 && method.indexOf('!callback_') === -1 && isEmpty) {
                continue;
            }
            if (parts) {
                method = parts[1];
                param = parts[2];
            }
            if (method.charAt(0) === '!') {
                method = method.substring(1, method.length);
            }
            if (typeof this._hooks[method] === 'function') {
                if (!this._hooks[method].apply(this, [field, param])) {
                    failed = true;
                }
            } else if (method.substring(0, 9) === 'callback_') {
                method = method.substring(9, method.length);
                if (typeof this.handlers[method] === 'function') {
                    if (this.handlers[method].apply(this, [field.value, param, field]) === false) {
                        failed = true;
                    }
                }
            }
            if (failed) {
                message = this.messages[method];
                this.errors.push({
                    id: field.id,
                    element: field.element,
                    name: field.name,
                    message: message,
                    rule: method
                });
                break;
            }
        }
    };
    FormValidator.prototype._hooks = {
        required: function(field) {
            var value = field.value;
            if ((field.type === 'checkbox') || (field.type === 'radio')) {
                return (field.checked === true);
            }
            return (value !== null && value !== '');
        },
        valid_email: function(field) {
            return emailRegex.test(field.value);
        },
        alpha_numeric: function(field) {
            return (alphaNumericRegex.test(field.value));
        }
    };
    window.FormValidator = FormValidator;

})(window, document);